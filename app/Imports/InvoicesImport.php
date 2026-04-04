<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tenant;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class InvoicesImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public array  $errors   = [];
    public int    $imported = 0;
    public int    $skipped  = 0;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly InvoiceService $invoiceService
    ) {}

    /**
     * Expected columns (header row):
     *
     *  invoice_number   | optional — auto-generated if blank
     *  customer_name    | required — matched case-insensitively; created if missing
     *  invoice_date     | required — YYYY-MM-DD or DD/MM/YYYY
     *  due_date         | required — same formats
     *  reference        | optional
     *  vat_applicable   | yes/no/1/0  (default: yes)
     *  wht_applicable   | yes/no/1/0  (default: no)
     *  wht_rate         | numeric     (default: 0)
     *  discount_amount  | numeric     (default: 0)
     *  notes            | optional
     *  terms            | optional
     *  item_description | required
     *  item_quantity    | required numeric
     *  item_unit_price  | required numeric
     *  item_vat         | yes/no/1/0  (default: same as vat_applicable)
     *
     * Multiple rows with the same invoice_number are treated as one invoice
     * with multiple line items.
     */
    public function collection(Collection $rows): void
    {
        // Group rows by invoice_number (or a generated key for rows without one)
        $groups = [];
        $autoKey = 0;

        foreach ($rows as $index => $row) {
            $row = $row->toArray();

            // Normalise keys — strip whitespace, lowercase
            $row = array_combine(
                array_map(fn($k) => strtolower(trim(str_replace(' ', '_', $k))), array_keys($row)),
                array_values($row)
            );

            $num = trim($row['invoice_number'] ?? '');
            if ($num === '') {
                $num = '__auto__' . (++$autoKey);
            }

            $groups[$num][] = ['row' => $row, 'line' => $index + 2]; // +2: 1-based + header
        }

        foreach ($groups as $invoiceNum => $lines) {
            $this->processGroup($invoiceNum, $lines);
        }
    }

    private function processGroup(string $invoiceNum, array $lines): void
    {
        $header   = $lines[0]['row'];
        $lineNum  = $lines[0]['line'];
        $isAuto   = str_starts_with($invoiceNum, '__auto__');

        // ── Validate header fields ───────────────────────────────────────────
        $customerName = trim($header['customer_name'] ?? '');
        if ($customerName === '') {
            $this->errors[] = "Row {$lineNum}: customer_name is required.";
            $this->skipped++;
            return;
        }

        $invoiceDate = $this->parseDate($header['invoice_date'] ?? '');
        if (!$invoiceDate) {
            $this->errors[] = "Row {$lineNum}: invalid invoice_date '{$header['invoice_date']}'. Use YYYY-MM-DD or DD/MM/YYYY.";
            $this->skipped++;
            return;
        }

        $dueDate = $this->parseDate($header['due_date'] ?? '');
        if (!$dueDate) {
            $this->errors[] = "Row {$lineNum}: invalid due_date '{$header['due_date']}'. Use YYYY-MM-DD or DD/MM/YYYY.";
            $this->skipped++;
            return;
        }

        // ── Validate line items ──────────────────────────────────────────────
        $itemsData = [];
        foreach ($lines as ['row' => $row, 'line' => $ln]) {
            $desc  = trim($row['item_description'] ?? '');
            $qty   = (float)($row['item_quantity'] ?? 0);
            $price = (float)str_replace(',', '', $row['item_unit_price'] ?? 0);

            if ($desc === '') {
                $this->errors[] = "Row {$ln}: item_description is required.";
                $this->skipped++;
                return;
            }
            if ($qty <= 0) {
                $this->errors[] = "Row {$ln}: item_quantity must be > 0.";
                $this->skipped++;
                return;
            }
            if ($price < 0) {
                $this->errors[] = "Row {$ln}: item_unit_price cannot be negative.";
                $this->skipped++;
                return;
            }

            $vatApplicable = $this->parseBool($header['vat_applicable'] ?? 'yes');
            $itemVat = isset($row['item_vat']) && $row['item_vat'] !== ''
                ? $this->parseBool($row['item_vat'])
                : $vatApplicable;

            $itemsData[] = [
                'description'    => $desc,
                'quantity'       => $qty,
                'unit_price'     => $price,
                'vat_applicable' => $itemVat,
                'vat_rate'       => Invoice::VAT_RATE,
            ];
        }

        // ── Check duplicate invoice number ───────────────────────────────────
        if (!$isAuto) {
            $exists = Invoice::where('tenant_id', $this->tenant->id)
                ->where('invoice_number', $invoiceNum)
                ->exists();

            if ($exists) {
                $this->errors[] = "Invoice {$invoiceNum}: already exists — skipped.";
                $this->skipped++;
                return;
            }
        }

        // ── Resolve customer (find or create) ────────────────────────────────
        $customer = Customer::where('tenant_id', $this->tenant->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($customerName)])
            ->first();

        if (!$customer) {
            $customer = Customer::create([
                'tenant_id'  => $this->tenant->id,
                'name'       => $customerName,
                'is_active'  => true,
                'is_company' => true,
            ]);
        }

        // ── Build invoice data ────────────────────────────────────────────────
        $vatApplicable = $this->parseBool($header['vat_applicable'] ?? 'yes');
        $whtApplicable = $this->parseBool($header['wht_applicable'] ?? 'no');
        $whtRate       = (float)($header['wht_rate'] ?? 0);
        $discount      = (float)str_replace(',', '', $header['discount_amount'] ?? 0);

        $data = [
            'customer_id'     => $customer->id,
            'invoice_date'    => $invoiceDate->toDateString(),
            'due_date'        => $dueDate->toDateString(),
            'reference'       => trim($header['reference'] ?? '') ?: null,
            'vat_applicable'  => $vatApplicable,
            'wht_applicable'  => $whtApplicable,
            'wht_rate'        => $whtRate,
            'discount_amount' => $discount,
            'notes'           => trim($header['notes'] ?? '') ?: null,
            'terms'           => trim($header['terms'] ?? '') ?: null,
        ];

        try {
            $invoice = $this->invoiceService->create($this->tenant, $data, $itemsData);

            // Override auto-number with the one from the file if provided
            if (!$isAuto) {
                $invoice->update(['invoice_number' => $invoiceNum]);
            }

            $this->imported++;
        } catch (\Exception $e) {
            $this->errors[] = "Row {$lineNum} ({$customerName}): " . $e->getMessage();
            $this->skipped++;
        }
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') return null;

        // Handle Excel serial dates (numeric)
        if (is_numeric($value)) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value));
            } catch (\Exception) {}
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd M Y', 'd F Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Exception) {}
        }

        return null;
    }

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        $v = strtolower(trim((string)$value));
        return in_array($v, ['yes', '1', 'true', 'y'], true);
    }
}
