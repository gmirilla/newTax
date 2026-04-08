<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private readonly VatService         $vatService,
        private readonly BookkeepingService $bookkeepingService,
    ) {}

    /**
     * Create a tax-compliant invoice with auto-calculated VAT.
     */
    public function create(Tenant $tenant, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($tenant, $data, $items) {
            $invoiceNumber = $this->generateInvoiceNumber($tenant);

            $invoice = Invoice::create([
                'tenant_id'      => $tenant->id,
                'customer_id'    => $data['customer_id'],
                'invoice_number' => $invoiceNumber,
                'reference'      => $data['reference'] ?? null,
                'invoice_date'   => $data['invoice_date'],
                'due_date'       => $data['due_date'],
                'vat_applicable' => $data['vat_applicable'] ?? true,
                'wht_applicable' => $data['wht_applicable'] ?? false,
                'wht_rate'       => $data['wht_rate'] ?? 0,
                'discount_amount'=> $data['discount_amount'] ?? 0,
                'notes'          => $data['notes'] ?? null,
                'terms'          => $data['terms'] ?? 'Payment due within 30 days.',
                'currency'       => 'NGN',
                'status'         => 'draft',
                'created_by'     => auth()->id(),
            ]);

            // Create line items with VAT computation
            foreach ($items as $index => $itemData) {
                $item = new InvoiceItem([
                    'invoice_id'     => $invoice->id,
                    'description'    => $itemData['description'],
                    'quantity'       => $itemData['quantity'],
                    'unit_price'     => $itemData['unit_price'],
                    'vat_applicable' => $itemData['vat_applicable'] ?? $invoice->vat_applicable,
                    'vat_rate'       => Invoice::VAT_RATE,
                    'account_code'   => $itemData['account_code'] ?? null,
                    'sort_order'     => $index + 1,
                ]);

                $item->calculateTotals();
                $item->save();
            }

            // Recalculate invoice totals
            $invoice->load('items');
            $invoice->recalculateTotals();

            AuditLog::record('created', $invoice, [], $invoice->toArray(), 'invoice');

            return $invoice->fresh(['items', 'customer']);
        });
    }

    /**
     * Record a payment against an invoice and post the corresponding journal entry.
     *
     * If the invoice has not yet been journalised (transaction_id IS NULL), revenue
     * is recognised first so the AR account exists in the GL before we settle it.
     *
     * Journal posted here:
     *   DR  Bank / Cash account  [payment amount]
     *   CR  Accounts Receivable  [payment amount]
     *
     * $paymentData must include 'payment_account_id' (the bank/cash GL account id).
     */
    public function recordPayment(Invoice $invoice, array $paymentData): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $paymentData) {
            $invoice->load(['tenant', 'customer']);

            // Ensure revenue is recognised in the GL before settling AR
            if (! $invoice->transaction_id) {
                $this->postRevenueJournal($invoice);
            }

            $payment = InvoicePayment::create([
                'tenant_id'    => $invoice->tenant_id,
                'invoice_id'   => $invoice->id,
                'payment_date' => $paymentData['payment_date'],
                'amount'       => $paymentData['amount'],
                'method'       => $paymentData['method'],
                'reference'    => $paymentData['reference'] ?? null,
                'notes'        => $paymentData['notes'] ?? null,
                'recorded_by'  => auth()->id(),
            ]);

            // Update invoice payment status
            $invoice->amount_paid = (float) $invoice->amount_paid + (float) $paymentData['amount'];
            $invoice->balance_due = (float) $invoice->total_amount  - $invoice->amount_paid;

            if ($invoice->balance_due <= 0) {
                $invoice->status = 'paid';
            } elseif ($invoice->amount_paid > 0) {
                $invoice->status = 'partial';
            }

            $invoice->save();

            // Post payment journal if a bank/cash account was selected
            if (! empty($paymentData['payment_account_id'])) {
                $this->postPaymentJournal($invoice, $payment, (int) $paymentData['payment_account_id']);
            }

            AuditLog::record('payment_recorded', $invoice, [], $paymentData, 'invoice,payment');

            return $payment;
        });
    }

    /**
     * Recognise revenue for a sent invoice.
     *
     *   DR  Accounts Receivable (1100)   total_amount
     *   CR  Revenue (4001)               subtotal − discount − wht_amount   (net revenue)
     *   CR  VAT Payable (2100)           vat_amount   (if VAT applies)
     *
     * Debits = Credits:
     *   total_amount = subtotal + vat − wht − discount
     *               = (subtotal − discount − wht) + vat  ✓
     *
     * Safe to call multiple times — skips if already journalised.
     * Also skips silently if required accounts are not in the chart of accounts.
     */
    public function postRevenueJournal(Invoice $invoice): void
    {
        if ($invoice->transaction_id) {
            return;
        }

        $invoice->loadMissing(['tenant', 'customer']);
        $tenant = $invoice->tenant;

        $arAccount  = Account::where('tenant_id', $tenant->id)->where('code', '1100')->first();
        $revAccount = Account::where('tenant_id', $tenant->id)->where('code', '4001')->first();

        if (! $arAccount || ! $revAccount) {
            return; // accounts not configured — stay in supplement mode
        }

        $totalAmount = round((float) $invoice->total_amount, 2);
        $netRevenue  = round((float) $invoice->subtotal - (float) $invoice->discount_amount - (float) $invoice->wht_amount, 2);
        $vatAmount   = round((float) $invoice->vat_amount, 2);

        $entries = [
            [
                'account_id'  => $arAccount->id,
                'entry_type'  => 'debit',
                'amount'      => $totalAmount,
                'description' => "AR: {$invoice->invoice_number}",
            ],
        ];

        if ($vatAmount > 0) {
            $vatAccount = Account::where('tenant_id', $tenant->id)->where('code', '2100')->first();
            if ($vatAccount) {
                $entries[] = [
                    'account_id'  => $revAccount->id,
                    'entry_type'  => 'credit',
                    'amount'      => $netRevenue,
                    'description' => "Revenue: {$invoice->invoice_number}",
                ];
                $entries[] = [
                    'account_id'  => $vatAccount->id,
                    'entry_type'  => 'credit',
                    'amount'      => $vatAmount,
                    'description' => "Output VAT: {$invoice->invoice_number}",
                ];
            } else {
                // No VAT account — credit full total to revenue to keep balance
                $entries[] = [
                    'account_id'  => $revAccount->id,
                    'entry_type'  => 'credit',
                    'amount'      => $totalAmount,
                    'description' => "Revenue: {$invoice->invoice_number}",
                ];
            }
        } else {
            $entries[] = [
                'account_id'  => $revAccount->id,
                'entry_type'  => 'credit',
                'amount'      => $totalAmount,
                'description' => "Revenue: {$invoice->invoice_number}",
            ];
        }

        $transaction = $this->bookkeepingService->postJournalEntry($tenant, [
            'transaction_date' => $invoice->invoice_date->toDateString(),
            'type'             => 'sale',
            'description'      => "Invoice issued: {$invoice->invoice_number} — {$invoice->customer->name}",
            'reference'        => $invoice->invoice_number,
        ], $entries);

        $invoice->update(['transaction_id' => $transaction->id]);
    }

    /**
     * Settle Accounts Receivable when a payment is collected.
     *
     *   DR  Bank / Cash (payment_account_id)   amount
     *   CR  Accounts Receivable (1100)          amount
     */
    private function postPaymentJournal(Invoice $invoice, InvoicePayment $payment, int $bankAccountId): void
    {
        $tenant    = $invoice->tenant;
        $arAccount = Account::where('tenant_id', $tenant->id)->where('code', '1100')->first();

        if (! $arAccount) {
            return;
        }

        $this->bookkeepingService->postJournalEntry($tenant, [
            'transaction_date' => $payment->payment_date->toDateString(),
            'type'             => 'receipt',
            'description'      => "Payment received: {$invoice->invoice_number} — {$invoice->customer->name}",
            'reference'        => 'REC-' . $invoice->invoice_number,
        ], [
            [
                'account_id'  => $bankAccountId,
                'entry_type'  => 'debit',
                'amount'      => (float) $payment->amount,
                'description' => "Receipt: {$invoice->invoice_number}",
            ],
            [
                'account_id'  => $arAccount->id,
                'entry_type'  => 'credit',
                'amount'      => (float) $payment->amount,
                'description' => "AR settled: {$invoice->invoice_number}",
            ],
        ]);
    }

    /**
     * Generate a unique invoice number in format: INV-YYYYMM-NNNN
     * e.g., INV-202501-0001
     */
    public function generateInvoiceNumber(Tenant $tenant): string
    {
        $prefix = 'INV-' . now()->format('Ym') . '-';

        $lastInvoice = Invoice::where('tenant_id', $tenant->id)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->lockForUpdate()
            ->first();

        $nextNumber = $lastInvoice
            ? (int)substr($lastInvoice->invoice_number, -4) + 1
            : 1;

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Mark overdue invoices. Should be run daily via scheduler.
     */
    public function markOverdueInvoices(Tenant $tenant): int
    {
        return Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['sent', 'partial'])
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }

    /**
     * Get invoice summary for dashboard.
     */
    public function getDashboardSummary(Tenant $tenant): array
    {
        $invoices = Invoice::where('tenant_id', $tenant->id);

        return [
            'total_invoiced'   => (float)$invoices->clone()->whereIn('status', ['sent', 'partial', 'paid', 'overdue'])->sum('total_amount'),
            'total_paid'       => (float)$invoices->clone()->where('status', 'paid')->sum('total_amount'),
            'total_outstanding'=> (float)$invoices->clone()->whereIn('status', ['sent', 'partial', 'overdue'])->sum('balance_due'),
            'total_overdue'    => (float)$invoices->clone()->where('status', 'overdue')->sum('balance_due'),
            'draft_count'      => $invoices->clone()->where('status', 'draft')->count(),
            'overdue_count'    => $invoices->clone()->where('status', 'overdue')->count(),
            'this_month_total' => (float)$invoices->clone()
                ->whereMonth('invoice_date', now()->month)
                ->whereYear('invoice_date', now()->year)
                ->sum('total_amount'),
        ];
    }
}
