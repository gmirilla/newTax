<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Services\BookkeepingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryImportController extends Controller
{
    private const SESSION_KEY = 'inventory_import_payload';
    private const MAX_ROWS    = 500;

    public function __construct(private readonly BookkeepingService $bookkeeping) {}

    // ── Upload form ───────────────────────────────────────────────────────────

    public function index(): View
    {
        $this->authorize('create', InventoryItem::class);

        $hasPending = session()->has(self::SESSION_KEY);

        return view('inventory.import.index', compact('hasPending'));
    }

    // ── Sample CSV download ───────────────────────────────────────────────────

    public function sample(): Response
    {
        $this->authorize('create', InventoryItem::class);

        $lines = [
            'name,sku,category,item_type,unit,selling_price,cost_price,opening_stock,restock_level,description',
            '"Widget A","WID-001","Electronics","product","piece","5000","3200","50","10","A great widget"',
            '"Blue Pen","PEN-BLU","Stationery","product","piece","150","80","200","50",""',
            '"Steel Rod 6m","STL-ROD-6","Raw Materials","raw_material","piece","12000","9500","100","20","Manufacturing input"',
            '"Bottled Juice 50cl","JCE-50CL","Beverages","finished_good","bottle","850","420","200","50","Manufactured product"',
            '"Plain T-Shirt","TSH-WHT-M","Clothing","product","piece","4500","2800","0","10","No opening stock example"',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory_import_sample.csv"',
        ]);
    }

    // ── Parse & preview (no DB writes) ────────────────────────────────────────

    public function preview(Request $request): View|RedirectResponse
    {
        $this->authorize('create', InventoryItem::class);

        $request->validate([
            'file' => ['required', 'file', 'max:2048',
                       'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel'],
        ]);

        $file = $request->file('file');

        // Server-side MIME re-check — browser Content-Type is user-controlled
        $realMime = mime_content_type($file->getRealPath());
        if (! in_array($realMime, ['text/csv', 'text/plain', 'application/csv', 'application/octet-stream'])) {
            return back()->withErrors(['file' => 'File must be a plain CSV. Detected: ' . $realMime]);
        }

        $handle = fopen($file->getRealPath(), 'r');

        // Strip UTF-8 BOM that Excel adds when saving as CSV
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rawHeaders = fgetcsv($handle);
        if (! $rawHeaders) {
            fclose($handle);
            return back()->withErrors(['file' => 'Could not read CSV headers. Ensure the file is not empty.']);
        }

        $headers  = array_map(fn($h) => strtolower(trim($h)), $rawHeaders);
        $required = ['name', 'selling_price', 'cost_price'];
        $missing  = array_diff($required, $headers);

        if (! empty($missing)) {
            fclose($handle);
            return back()->withErrors([
                'file' => 'Missing required column(s): ' . implode(', ', $missing)
                        . '. Download the sample template for the expected format.',
            ]);
        }

        $tenantId = auth()->user()->tenant_id;

        // Load all active categories for this tenant — avoids N+1 during row processing
        $categoryMap = InventoryCategory::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->mapWithKeys(fn($c) => [strtolower($c->name) => $c->id]);

        // Existing SKUs for duplicate detection (lowercase for case-insensitive match)
        $existingSkus = InventoryItem::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->whereNotNull('sku')
            ->pluck('sku')
            ->mapWithKeys(fn($s) => [strtolower($s) => true]);

        $rows     = [];
        $rowNum   = 1;
        $seenSkus = [];

        while (($rawRow = fgetcsv($handle)) !== false) {
            $rowNum++;

            // Skip blank lines
            if (count(array_filter($rawRow, fn($v) => trim($v) !== '')) === 0) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);
                return back()->withErrors([
                    'file' => 'File exceeds the ' . self::MAX_ROWS . '-row limit. Split into smaller batches.',
                ]);
            }

            // Map columns by header name (tolerant of extra/missing columns)
            $col = [];
            foreach ($headers as $i => $header) {
                $col[$header] = isset($rawRow[$i]) ? trim($rawRow[$i]) : '';
            }

            [$parsedRow, $skuKey] = $this->parseRow($col, $rowNum, $categoryMap, $existingSkus, $seenSkus);

            if ($skuKey !== null) {
                $seenSkus[$skuKey] = true;
            }

            $rows[] = $parsedRow;
        }

        fclose($handle);

        if (empty($rows)) {
            return back()->withErrors(['file' => 'The file contains no data rows.']);
        }

        $valid      = array_filter($rows, fn($r) => empty($r['errors']) && ! $r['is_duplicate']);
        $totalValue = (float) array_sum(array_map(
            fn($r) => $r['opening_stock'] * $r['cost_price'],
            $valid
        ));

        // Store only clean rows in session — signed by tenant so commit() can cross-check
        session([self::SESSION_KEY => [
            'tenant_id'   => $tenantId,
            'rows'        => array_values($valid),
            'total_value' => round($totalValue, 2),
        ]]);

        return view('inventory.import.preview', [
            'rows'       => $rows,
            'validCount' => count($valid),
            'dupeCount'  => count(array_filter($rows, fn($r) => $r['is_duplicate'])),
            'errorCount' => count(array_filter($rows, fn($r) => ! empty($r['errors']))),
            'totalValue' => $totalValue,
        ]);
    }

    // ── Commit ────────────────────────────────────────────────────────────────

    public function commit(Request $request): RedirectResponse
    {
        $this->authorize('create', InventoryItem::class);

        $payload = session(self::SESSION_KEY);

        if (! $payload || empty($payload['rows'])) {
            return redirect()->route('inventory.import.index')
                ->with('error', 'No pending import found. Please upload your file again.');
        }

        $tenantId = auth()->user()->tenant_id;

        // Session tenant must match — prevents session data being consumed by a different tenant
        if ((int) $payload['tenant_id'] !== (int) $tenantId) {
            session()->forget(self::SESSION_KEY);
            abort(403);
        }

        $rows       = $payload['rows'];
        $totalValue = (float) $payload['total_value'];
        $imported   = 0;

        // GL account per item type: products/semi-finished → 1200, raw materials → 1201, finished goods → 1202
        $glCodeByType = [
            'product'       => '1200',
            'semi_finished' => '1200',
            'raw_material'  => '1201',
            'finished_good' => '1202',
        ];

        try {
            DB::transaction(function () use ($rows, $tenantId, $totalValue, $glCodeByType, &$imported) {
                $needsGl = $totalValue > 0;
                $glAccounts = collect();

                if ($needsGl) {
                    // Compute opening value per GL account code
                    $valueByCode = [];
                    foreach ($rows as $row) {
                        if ((float) $row['opening_stock'] > 0) {
                            $code = $glCodeByType[$row['item_type'] ?? 'product'] ?? '1200';
                            $valueByCode[$code] = round(($valueByCode[$code] ?? 0) + ($row['opening_stock'] * $row['cost_price']), 2);
                        }
                    }

                    $requiredCodes = array_unique(array_merge(array_keys($valueByCode), ['3001']));
                    $glAccounts = Account::where('tenant_id', $tenantId)
                        ->withoutGlobalScope('tenant')
                        ->whereIn('code', $requiredCodes)
                        ->pluck('id', 'code');

                    $missing = array_diff($requiredCodes, $glAccounts->keys()->toArray());
                    if (! empty($missing)) {
                        throw ValidationException::withMessages([
                            'gl' => 'GL accounts missing from Chart of Accounts: ' . implode(', ', $missing)
                                  . '. Add them under Accounts and try again, or set opening stock to 0 for all items.',
                        ]);
                    }
                }

                $importRef = 'IMPORT-' . now()->format('Ymd-His');

                foreach ($rows as $row) {
                    $item = InventoryItem::create([
                        'tenant_id'     => $tenantId,
                        'category_id'   => $row['category_id'],
                        'name'          => $row['name'],
                        'sku'           => $row['sku'],
                        'description'   => $row['description'] ?: null,
                        'item_type'     => $row['item_type'],
                        'unit'          => $row['unit'],
                        'selling_price' => $row['selling_price'],
                        'cost_price'    => $row['cost_price'],
                        'avg_cost'      => $row['cost_price'],
                        'current_stock' => $row['opening_stock'],
                        'restock_level' => $row['restock_level'],
                        'is_active'     => true,
                        'created_by'    => auth()->id(),
                    ]);

                    if ($row['opening_stock'] > 0) {
                        StockMovement::create([
                            'tenant_id'       => $tenantId,
                            'item_id'         => $item->id,
                            'type'            => 'opening',
                            'quantity'        => $row['opening_stock'],
                            'unit_cost'       => $row['cost_price'],
                            'running_balance' => $row['opening_stock'],
                            'notes'           => "Opening stock — {$importRef}",
                            'created_by'      => auth()->id(),
                        ]);
                    }

                    $imported++;
                }

                // GL entries: one debit line per inventory sub-account, single equity credit
                if ($needsGl && $glAccounts->isNotEmpty()) {
                    $accountLabels = [
                        '1200' => 'Inventory',
                        '1201' => 'Raw Materials Inventory',
                        '1202' => 'Finished Goods Inventory',
                    ];
                    $debitLines = [];
                    foreach ($valueByCode as $code => $amount) {
                        $debitLines[] = [
                            'account_id'  => $glAccounts[$code],
                            'entry_type'  => 'debit',
                            'amount'      => $amount,
                            'description' => ($accountLabels[$code] ?? 'Inventory') . " opening stock — {$importRef}",
                        ];
                    }

                    $this->bookkeeping->postJournalEntry(
                        auth()->user()->tenant,
                        [
                            'reference'        => $importRef,
                            'transaction_date' => now()->toDateString(),
                            'type'             => 'opening_balance',
                            'description'      => "Opening inventory import — {$imported} item" . ($imported !== 1 ? 's' : ''),
                        ],
                        array_merge($debitLines, [
                            [
                                'account_id'  => $glAccounts['3001'],
                                'entry_type'  => 'credit',
                                'amount'      => round($totalValue, 2),
                                'description' => "Opening equity offset — {$importRef}",
                            ],
                        ])
                    );
                }
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        session()->forget(self::SESSION_KEY);

        $message = number_format($imported) . ' item' . ($imported !== 1 ? 's' : '') . ' imported successfully.';
        if ($totalValue > 0) {
            $message .= ' Opening stock value of ₦' . number_format($totalValue, 2) . ' posted to the ledger (Dr Inventory / Cr Owner\'s Equity).';
        }

        return redirect()->route('inventory.items.index')->with('success', $message);
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function cancel(): RedirectResponse
    {
        $this->authorize('create', InventoryItem::class);

        session()->forget(self::SESSION_KEY);

        return redirect()->route('inventory.import.index')
            ->with('success', 'Import cancelled.');
    }

    // ── Row parser ────────────────────────────────────────────────────────────

    private function parseRow(
        array $col,
        int $rowNum,
        \Illuminate\Support\Collection $categoryMap,
        \Illuminate\Support\Collection $existingSkus,
        array $seenSkus,
    ): array {
        $errors      = [];
        $isDuplicate = false;
        $skuKey      = null;

        // name
        $name = strip_tags($col['name'] ?? '');
        if ($name === '') {
            $errors[] = 'Name is required';
        } elseif (mb_strlen($name) > 150) {
            $errors[] = 'Name must be 150 characters or fewer';
        }

        // sku
        $sku = strip_tags($col['sku'] ?? '');
        if ($sku === '') {
            $sku = null;
        } else {
            if (mb_strlen($sku) > 50) {
                $errors[] = 'SKU must be 50 characters or fewer';
            }
            $skuLower = strtolower($sku);
            if ($existingSkus->has($skuLower)) {
                $isDuplicate = true;
            } elseif (isset($seenSkus[$skuLower])) {
                $errors[] = "SKU \"{$sku}\" appears more than once in this file";
            } else {
                $skuKey = $skuLower;
            }
        }

        // selling_price
        $sellingPrice = filter_var($col['selling_price'] ?? '', FILTER_VALIDATE_FLOAT);
        if ($sellingPrice === false || $sellingPrice < 0) {
            $errors[]     = 'Selling price must be a number ≥ 0';
            $sellingPrice = 0;
        }

        // cost_price
        $costPrice = filter_var($col['cost_price'] ?? '', FILTER_VALIDATE_FLOAT);
        if ($costPrice === false || $costPrice < 0) {
            $errors[]  = 'Cost price must be a number ≥ 0';
            $costPrice = 0;
        }

        // opening_stock
        $openingStock = filter_var($col['opening_stock'] ?? '0', FILTER_VALIDATE_FLOAT);
        if ($openingStock === false || $openingStock < 0) {
            $errors[]     = 'Opening stock must be a number ≥ 0';
            $openingStock = 0;
        }

        // restock_level
        $restockLevel = filter_var($col['restock_level'] ?? '0', FILTER_VALIDATE_FLOAT);
        if ($restockLevel === false || $restockLevel < 0) {
            $errors[]     = 'Restock level must be a number ≥ 0';
            $restockLevel = 0;
        }

        // category — matched by name; unrecognised names silently uncategorise
        $categoryName = strip_tags($col['category'] ?? '');
        $categoryId   = $categoryName !== ''
            ? ($categoryMap->get(strtolower($categoryName)))
            : null;
        $categoryFound = $categoryName === '' || $categoryId !== null;

        // unit — free-text, defaults to 'piece'
        $unit = strip_tags($col['unit'] ?? '');
        if ($unit === '') $unit = 'piece';
        $unit = mb_substr($unit, 0, 30);

        // description
        $description = strip_tags($col['description'] ?? '');
        if (mb_strlen($description) > 1000) {
            $errors[]    = 'Description must be 1000 characters or fewer';
            $description = mb_substr($description, 0, 1000);
        }

        // item_type — defaults to 'product' if blank or unrecognised
        $validItemTypes = ['product', 'raw_material', 'finished_good', 'semi_finished'];
        $itemType = strtolower(trim($col['item_type'] ?? ''));
        if (! in_array($itemType, $validItemTypes)) {
            $itemType = 'product';
        }

        return [
            [
                'row'            => $rowNum,
                'name'           => $name,
                'sku'            => $sku,
                'category_id'    => $categoryId,
                'category_name'  => $categoryName,
                'category_found' => $categoryFound,
                'item_type'      => $itemType,
                'unit'           => $unit,
                'selling_price'  => $sellingPrice,
                'cost_price'     => $costPrice,
                'opening_stock'  => $openingStock,
                'restock_level'  => $restockLevel,
                'description'    => $description,
                'is_duplicate'   => $isDuplicate,
                'errors'         => $errors,
            ],
            $skuKey,
        ];
    }
}
