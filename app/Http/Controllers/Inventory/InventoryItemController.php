<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InventoryAlert;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\StockMovement;
use App\Services\BookkeepingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function __construct(private readonly BookkeepingService $bookkeeping) {}

    public function index(Request $request): View
    {
        $tenant = auth()->user()->tenant;

        $query = InventoryItem::where('inventory_items.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->with('category')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'ilike', '%' . $request->search . '%')
                      ->orWhere('sku', 'ilike', '%' . $request->search . '%')
                      ->orWhere('description', 'ilike', '%' . $request->search . '%');
                });
            })
            ->when($request->filled('category'), fn($q) => $q->where('category_id', $request->category))
            ->when($request->filled('stock_status'), function ($q) use ($request) {
                match ($request->stock_status) {
                    'out'  => $q->where('current_stock', '<=', 0),
                    'low'  => $q->whereColumn('current_stock', '<=', 'restock_level')
                               ->where('current_stock', '>', 0)
                               ->where('restock_level', '>', 0),
                    'ok'   => $q->where(function ($q) {
                                   $q->whereColumn('current_stock', '>', 'restock_level')
                                     ->orWhere('restock_level', '<=', 0);
                               }),
                    default => null,
                };
            })
            ->when($request->boolean('inactive'), fn($q) => $q->where('is_active', false), fn($q) => $q->where('is_active', true));

        $items = $query->orderBy('name')->paginate(25)->withQueryString();

        $categories = InventoryCategory::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->orderBy('name')
            ->get();

        $stats = [
            'total'      => InventoryItem::where('tenant_id', $tenant->id)->withoutGlobalScope('tenant')->where('is_active', true)->count(),
            'low_stock'  => InventoryItem::where('tenant_id', $tenant->id)->withoutGlobalScope('tenant')->where('is_active', true)
                                ->whereColumn('current_stock', '<=', 'restock_level')->where('current_stock', '>', 0)->where('restock_level', '>', 0)->count(),
            'out_stock'  => InventoryItem::where('tenant_id', $tenant->id)->withoutGlobalScope('tenant')->where('is_active', true)->where('current_stock', '<=', 0)->count(),
            'stock_value'=> InventoryItem::where('tenant_id', $tenant->id)->withoutGlobalScope('tenant')->where('is_active', true)
                                ->selectRaw('SUM(current_stock * avg_cost) as total')->value('total') ?? 0,
        ];

        $unseenAlerts = InventoryAlert::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->whereNull('seen_at')
            ->with('item')
            ->orderByRaw("type = 'out_of_stock' desc")
            ->get();

        return view('inventory.items.index', compact('items', 'categories', 'stats', 'unseenAlerts'));
    }

    public function create(): View
    {
        $this->authorize('create', InventoryItem::class);

        $tenantId = auth()->user()->tenant_id;

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = InventoryUnit::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.items.create', compact('categories', 'units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', InventoryItem::class);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'sku'           => ['nullable', 'string', 'max:50'],
            'category_id'   => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'unit'          => ['required', 'string', 'max:30'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'cost_price'    => ['required', 'numeric', 'min:0'],
            'restock_level' => ['required', 'numeric', 'min:0'],
            'opening_stock' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tenantId      = auth()->user()->tenant_id;
        $openingStock  = (float) ($validated['opening_stock'] ?? 0);
        $costPrice     = (float) $validated['cost_price'];

        $tenant = auth()->user()->tenant;

        DB::transaction(function () use ($validated, $tenantId, $openingStock, $costPrice, $tenant) {
            $item = InventoryItem::create([
                'tenant_id'     => $tenantId,
                'category_id'   => $validated['category_id'] ?? null,
                'name'          => $validated['name'],
                'sku'           => $validated['sku'] ?? null,
                'description'   => $validated['description'] ?? null,
                'unit'          => $validated['unit'],
                'selling_price' => $validated['selling_price'],
                'cost_price'    => $costPrice,
                'avg_cost'      => $costPrice,
                'current_stock' => $openingStock,
                'restock_level' => $validated['restock_level'],
                'is_active'     => true,
                'created_by'    => auth()->id(),
            ]);

            if ($openingStock > 0) {
                StockMovement::create([
                    'tenant_id'       => $tenantId,
                    'item_id'         => $item->id,
                    'type'            => 'opening',
                    'quantity'        => $openingStock,
                    'unit_cost'       => $costPrice,
                    'running_balance' => $openingStock,
                    'notes'           => 'Opening stock',
                    'created_by'      => auth()->id(),
                ]);

                // Post GL: Dr Inventory (1200) / Cr Owner's Equity (3001)
                $accounts = Account::where('tenant_id', $tenantId)
                    ->withoutGlobalScope('tenant')
                    ->whereIn('code', ['1200', '3001'])
                    ->pluck('id', 'code');

                if ($accounts->has('1200') && $accounts->has('3001')) {
                    $openingValue = round($openingStock * $costPrice, 2);

                    $this->bookkeeping->postJournalEntry(
                        $tenant,
                        [
                            'reference'        => 'OPEN-INV-' . $item->id,
                            'transaction_date' => now()->toDateString(),
                            'type'             => 'opening_balance',
                            'description'      => "Opening stock: {$item->name}",
                        ],
                        [
                            [
                                'account_id'  => $accounts['1200'],
                                'entry_type'  => 'debit',
                                'amount'      => $openingValue,
                                'description' => "Opening stock: {$item->name}",
                            ],
                            [
                                'account_id'  => $accounts['3001'],
                                'entry_type'  => 'credit',
                                'amount'      => $openingValue,
                                'description' => "Opening stock: {$item->name}",
                            ],
                        ]
                    );
                }
            }
        });

        return redirect()->route('inventory.items.index')->with('success', 'Item created successfully.');
    }

    public function show(InventoryItem $inventoryItem): View
    {
        $this->authorize('view', $inventoryItem);

        $inventoryItem->load('category');

        $movements = StockMovement::where('item_id', $inventoryItem->id)
            ->with('creator')
            ->orderByDesc('created_at')
            ->paginate(20);

        $pendingRestocks = $inventoryItem->restockRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->with('requester', 'approver')
            ->latest()
            ->get();

        return view('inventory.items.show', compact('inventoryItem', 'movements', 'pendingRestocks'));
    }

    public function edit(InventoryItem $inventoryItem): View
    {
        $this->authorize('update', $inventoryItem);

        $tenantId = auth()->user()->tenant_id;

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = InventoryUnit::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.items.edit', compact('inventoryItem', 'categories', 'units'));
    }

    public function update(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('update', $inventoryItem);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'sku'           => ['nullable', 'string', 'max:50'],
            'category_id'   => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'unit'          => ['required', 'string', 'max:30'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'cost_price'    => ['required', 'numeric', 'min:0'],
            'restock_level' => ['required', 'numeric', 'min:0'],
            'is_active'     => ['boolean'],
        ]);

        $inventoryItem->update($validated);

        return redirect()->route('inventory.items.show', $inventoryItem)
            ->with('success', 'Item updated successfully.');
    }

    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('delete', $inventoryItem);

        $inventoryItem->delete();

        return redirect()->route('inventory.items.index')->with('success', 'Item removed from catalog.');
    }

    public function adjustStock(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('adjust', $inventoryItem);

        $validated = $request->validate([
            'type'     => ['required', 'in:adjustment_in,adjustment_out'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'notes'    => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $inventoryItem) {
            $isIn      = $validated['type'] === 'adjustment_in';
            $direction = $isIn ? 1 : -1;
            $qty       = (float) $validated['quantity'];
            $avgCost   = (float) $inventoryItem->avg_cost;
            $newStock  = round((float) $inventoryItem->current_stock + ($direction * $qty), 3);

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Adjustment would result in negative stock (' . number_format($inventoryItem->current_stock, 3) . ' in stock).',
                ]);
            }

            StockMovement::create([
                'tenant_id'       => $inventoryItem->tenant_id,
                'item_id'         => $inventoryItem->id,
                'type'            => $validated['type'],
                'quantity'        => $qty,
                'unit_cost'       => $avgCost,
                'running_balance' => $newStock,
                'notes'           => $validated['notes'],
                'created_by'      => auth()->id(),
            ]);

            $inventoryItem->update(['current_stock' => $newStock]);

            // Post GL: Dr/Cr Inventory (1200) against COGS (5001)
            // adjustment_in:  Dr 1200 Inventory, Cr 5001 COGS (stock gain)
            // adjustment_out: Dr 5001 COGS,      Cr 1200 Inventory (shrinkage/loss)
            $value = round($qty * $avgCost, 2);
            if ($value > 0) {
                $accounts = Account::where('tenant_id', $inventoryItem->tenant_id)
                    ->withoutGlobalScope('tenant')
                    ->whereIn('code', ['1200', '5001'])
                    ->pluck('id', 'code');

                if ($accounts->has('1200') && $accounts->has('5001')) {
                    $this->bookkeeping->postJournalEntry(
                        $inventoryItem->tenant,
                        [
                            'reference'        => 'ADJ-' . $inventoryItem->id . '-' . now()->format('YmdHis'),
                            'transaction_date' => now()->toDateString(),
                            'type'             => 'adjustment',
                            'description'      => ($isIn ? 'Stock gain' : 'Stock loss') . ": {$inventoryItem->name}",
                        ],
                        [
                            [
                                'account_id'  => $accounts['1200'],
                                'entry_type'  => $isIn ? 'debit' : 'credit',
                                'amount'      => $value,
                                'description' => ($isIn ? 'Inventory gain' : 'Inventory reduction') . ": {$inventoryItem->name}",
                            ],
                            [
                                'account_id'  => $accounts['5001'],
                                'entry_type'  => $isIn ? 'credit' : 'debit',
                                'amount'      => $value,
                                'description' => ($isIn ? 'COGS reversal (stock gain)' : 'Inventory shrinkage') . ": {$inventoryItem->name}",
                            ],
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Stock adjusted successfully.');
    }
}
