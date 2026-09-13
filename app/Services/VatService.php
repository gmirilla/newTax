<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Tenant;
use App\Models\VatReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VatService
{
    /**
     * Nigerian VAT rate - 7.5% as per Finance Act 2019.
     * Previously 5%, raised to 7.5% effective February 1, 2020.
     */
    public const VAT_RATE = 7.5;

    /**
     * VAT filing deadline: 21st of the month following the tax period.
     * e.g., January VAT is due by February 21st.
     */
    public const VAT_FILING_DAY = 21;

    /**
     * Calculate VAT on a given amount (output VAT for sales).
     * Formula: VAT = Amount × 7.5 / 100
     */
    public function calculateOutputVat(float $amount): float
    {
        return round($amount * self::VAT_RATE / 100, 2);
    }

    /**
     * Extract VAT from a VAT-inclusive amount (reverse VAT).
     * Formula: VAT = (VAT-inclusive Amount × 7.5) / 107.5
     */
    public function extractVatFromInclusive(float $vatInclusiveAmount): float
    {
        return round(($vatInclusiveAmount * self::VAT_RATE) / (100 + self::VAT_RATE), 2);
    }

    /**
     * Output VAT recognized on a cash basis: each invoice's vat_amount is
     * prorated by how much of it has actually been paid. A fully-paid
     * invoice contributes its full vat_amount; an unpaid invoice (amount_paid
     * == 0, e.g. status 'sent') contributes 0. Overpayment is clamped so a
     * single invoice never contributes more than its own vat_amount.
     */
    public function sumOutputVatReceived(Collection $invoices): float
    {
        return round($invoices->sum(function (Invoice $invoice) {
            $total = (float) $invoice->total_amount;
            if ($total <= 0) {
                return 0.0;
            }
            $ratio = min(1.0, (float) $invoice->amount_paid / $total);
            return round((float) $invoice->vat_amount * $ratio, 2);
        }), 2);
    }

    /**
     * Compute monthly VAT return for a tenant.
     * Net VAT = Output VAT (from sales) - Input VAT (from purchases)
     * Positive result: payable to FIRS
     * Negative result: VAT credit carried forward
     */
    public function computeMonthlyReturn(Tenant $tenant, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = Carbon::create($year, $month, 1)->endOfMonth();

        // Output VAT: VAT collected from customers on invoices, recognized
        // only on the portion of each invoice actually paid so far.
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->where('vat_applicable', true)
            ->whereBetween('invoice_date', [$start, $end])
            ->whereIn('status', ['sent', 'partial', 'paid'])
            ->get(['vat_amount', 'amount_paid', 'total_amount']);

        $outputVat = $this->sumOutputVatReceived($invoices);

        // Input VAT: VAT paid to vendors on expenses (claimable as credit)
        $inputVat = Expense::where('tenant_id', $tenant->id)
            ->where('vat_applicable', true)
            ->whereBetween('expense_date', [$start, $end])
            ->whereIn('status', ['approved', 'paid'])
            ->sum('vat_amount');

        $netVatPayable = round((float)$outputVat - (float)$inputVat, 2);

        return [
            'tax_year'        => $year,
            'tax_month'       => $month,
            'period_start'    => $start->toDateString(),
            'period_end'      => $end->toDateString(),
            'output_vat'      => round((float)$outputVat, 2),
            'input_vat'       => round((float)$inputVat, 2),
            'net_vat_payable' => $netVatPayable,
            'due_date'        => $this->getFilingDueDate($year, $month),
            'is_nil_return'   => ($outputVat == 0 && $inputVat == 0),
        ];
    }

    /**
     * Get the VAT filing due date for a given period.
     * Due: 21st of the following month.
     */
    public function getFilingDueDate(int $year, int $month): string
    {
        $nextMonth = Carbon::create($year, $month, 1)->addMonth();
        return Carbon::create($nextMonth->year, $nextMonth->month, self::VAT_FILING_DAY)
            ->toDateString();
    }

    /**
     * Create or update a VAT return record for the given period.
     */
    public function createOrUpdateReturn(Tenant $tenant, int $year, int $month): VatReturn
    {
        $vatReturn = VatReturn::firstOrNew([
            'tenant_id' => $tenant->id,
            'tax_year'  => $year,
            'tax_month' => $month,
        ]);

        // Filed/paid returns are locked against silent recompute — once a
        // return has been reported to NRS, changing its figures needs an
        // explicit, audited amendment (see TODO.md), not a routine refresh.
        if ($vatReturn->exists && in_array($vatReturn->status, ['filed', 'paid'], true)) {
            return $vatReturn;
        }

        $before = $vatReturn->exists
            ? $vatReturn->only(['output_vat', 'input_vat', 'net_vat_payable'])
            : null;

        $data = $this->computeMonthlyReturn($tenant, $year, $month);

        // Always refresh computed financial figures
        $vatReturn->fill(array_merge($data, ['tenant_id' => $tenant->id]));

        // Only initialise status on new records; never overwrite filed/paid
        if (! $vatReturn->exists) {
            $vatReturn->status = $data['is_nil_return'] ? 'nil_return' : 'pending';
        }

        $vatReturn->save();

        if ($before !== null && $vatReturn->wasChanged(['output_vat', 'input_vat', 'net_vat_payable'])) {
            AuditLog::record('vat_return.recalculated', $vatReturn, $before, [
                'output_vat'      => $vatReturn->output_vat,
                'input_vat'       => $vatReturn->input_vat,
                'net_vat_payable' => $vatReturn->net_vat_payable,
            ], 'tax,vat');
        }

        return $vatReturn;
    }

    /**
     * Recompute a filed return's figures and send it back to `pending` so
     * the corrected amount can be re-filed with NRS under a new reference —
     * callers must ensure $vatReturn->status === 'filed' before calling this
     * (paid returns are not amendable here; see TODO.md).
     */
    public function amendReturn(VatReturn $vatReturn, string $reason): VatReturn
    {
        $before = $vatReturn->only(['output_vat', 'input_vat', 'net_vat_payable', 'status']);

        $data = $this->computeMonthlyReturn($vatReturn->tenant, $vatReturn->tax_year, $vatReturn->tax_month);

        $vatReturn->fill($data);
        $vatReturn->status           = $data['is_nil_return'] ? 'nil_return' : 'pending';
        $vatReturn->filing_reference = null;
        $vatReturn->filed_date       = null;
        $vatReturn->filed_by         = null;
        $vatReturn->notes            = trim(($vatReturn->notes ? $vatReturn->notes . "\n" : '')
            . 'Amended ' . now()->toDateString() . ': ' . $reason);
        $vatReturn->save();

        AuditLog::record('vat_return.amended', $vatReturn, $before, [
            'output_vat'      => $vatReturn->output_vat,
            'input_vat'       => $vatReturn->input_vat,
            'net_vat_payable' => $vatReturn->net_vat_payable,
            'status'          => $vatReturn->status,
            'reason'          => $reason,
        ], 'tax,approval');

        return $vatReturn;
    }

    /**
     * Get all overdue VAT returns for a tenant.
     */
    public function getOverdueReturns(Tenant $tenant): Collection
    {
        return VatReturn::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Generate VAT summary for dashboard display.
     */
    public function getDashboardSummary(Tenant $tenant): array
    {
        $currentYear  = now()->year;
        $currentMonth = now()->month;

        $ytdInvoices = Invoice::where('tenant_id', $tenant->id)
            ->where('vat_applicable', true)
            ->whereYear('invoice_date', $currentYear)
            ->whereIn('status', ['sent', 'partial', 'paid'])
            ->get(['vat_amount', 'amount_paid', 'total_amount']);

        $ytdOutput = $this->sumOutputVatReceived($ytdInvoices);

        $ytdInput = Expense::where('tenant_id', $tenant->id)
            ->where('vat_applicable', true)
            ->whereYear('expense_date', $currentYear)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('vat_amount');

        $overdueCount = VatReturn::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        return [
            'ytd_output_vat'    => round((float)$ytdOutput, 2),
            'ytd_input_vat'     => round((float)$ytdInput, 2),
            'ytd_net_vat'       => round((float)$ytdOutput - (float)$ytdInput, 2),
            'overdue_returns'   => $overdueCount,
            'next_due_date'     => $this->getFilingDueDate($currentYear, $currentMonth),
            'current_period'    => date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)),
        ];
    }
}
