<?php

namespace App\Services;

use App\Models\CitRecord;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Tenant;
use Carbon\Carbon;

class CitService
{
    /**
     * Nigerian Company Income Tax (CIT) — 2026 rates.
     * CITA as amended by Finance Acts 2019–2025.
     *
     * Two-bracket structure (Finance Act 2025):
     *   Small  (turnover ≤ ₦50M): 0%  — exempt, but must still file
     *   Large  (turnover  > ₦50M): 30%
     *
     * Note: Professional service firms (lawyers, engineers, accountants, etc.)
     * do NOT qualify for the 0% small-company exemption regardless of turnover.
     *
     * Development Levy (Finance Act 2025 — replaces TETFund, IT Levy, NASENI, Police Trust Fund):
     *   4% of assessable profit — applies to all companies except small (non-professional)
     *
     * Minimum Tax: 0.5% of gross turnover or ₦200,000 (whichever is higher)
     * Applies to non-small companies when CIT computed < minimum tax.
     */

    public const CIT_RATES = [
        'small' => 0,
        'large' => 30,
    ];

    public const DEVELOPMENT_LEVY_RATE  = 4.0;   // 4% of assessable profit (replaces education tax)
    public const MINIMUM_TAX_RATE       = 0.5;   // 0.5% of gross turnover
    public const MINIMUM_TAX_FLOOR      = 200_000; // ₦200,000 minimum
    public const SMALL_COMPANY_THRESHOLD = 50_000_000; // ₦50M (2026 threshold)

    /**
     * Compute CIT for a given tax year.
     */
    public function compute(Tenant $tenant, int $taxYear): array
    {
        $start = Carbon::create($taxYear, 1, 1)->startOfYear();
        $end   = Carbon::create($taxYear, 12, 31)->endOfYear();

        // Total revenue (turnover)
        $grossRevenue = (float) Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['sent', 'partial', 'paid'])
            ->whereBetween('invoice_date', [$start, $end])
            ->sum('subtotal');

        // Total allowable expenses
        $allowableExpenses = (float) Expense::where('tenant_id', $tenant->id)
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        // Assessable profit
        $assessableProfit = max(0, $grossRevenue - $allowableExpenses);

        // Company classification — professional firms cannot claim small-company exemption
        $isProfessionalFirm = (bool)($tenant->is_professional_firm ?? false);
        $companySize        = $this->determineCompanySize($grossRevenue, $isProfessionalFirm);
        $citRate            = self::CIT_RATES[$companySize];

        // CIT amount
        $citAmount = round($assessableProfit * $citRate / 100, 2);

        // Development Levy: 4% of assessable profit
        // Exempt for small non-professional companies
        $developmentLevy = ($companySize === 'large')
            ? round($assessableProfit * self::DEVELOPMENT_LEVY_RATE / 100, 2)
            : 0.0;

        // Minimum Tax (when CIT < minimum tax threshold — not applicable to small companies)
        $minimumTax = max(
            round($grossRevenue * self::MINIMUM_TAX_RATE / 100, 2),
            self::MINIMUM_TAX_FLOOR
        );

        // Apply minimum tax rule if CIT is lower (not for small companies)
        $effectiveCit = $citAmount;
        if ($companySize !== 'small' && $citAmount < $minimumTax) {
            $effectiveCit = $minimumTax;
        }

        $totalTaxDue = round($effectiveCit + $developmentLevy, 2);

        return [
            'tax_year'             => $taxYear,
            'gross_profit'         => $assessableProfit,
            'annual_turnover'      => $grossRevenue,
            'allowable_deductions' => $allowableExpenses,
            'taxable_profit'       => $assessableProfit,
            'company_size'         => $companySize,
            'cit_rate'             => $citRate,
            'cit_amount'           => $effectiveCit,
            'development_levy'     => $developmentLevy,
            'education_levy'       => 0, // legacy field — replaced by development levy
            'minimum_tax'          => $minimumTax,
            'total_tax_due'        => $totalTaxDue,
            'is_exempt'            => $companySize === 'small',
            'is_professional_firm' => $isProfessionalFirm,
            'due_date'             => $this->getFilingDueDate($taxYear),
        ];
    }

    /**
     * Create or update the CIT record for a tax year.
     */
    public function createOrUpdateRecord(Tenant $tenant, int $taxYear): CitRecord
    {
        $data = $this->compute($tenant, $taxYear);

        return CitRecord::updateOrCreate(
            ['tenant_id' => $tenant->id, 'tax_year' => $taxYear],
            array_merge($data, [
                'tenant_id' => $tenant->id,
                'status'    => $data['is_exempt'] ? 'exempt' : 'pending',
            ])
        );
    }

    /**
     * Determine company size based on annual turnover and professional firm status.
     * 2026: Only two categories — small (≤₦50M, non-professional) and large (everyone else).
     */
    public function determineCompanySize(float $annualTurnover, bool $isProfessionalFirm = false): string
    {
        if ($isProfessionalFirm) {
            return 'large'; // Professional firms always pay CIT regardless of turnover
        }

        return $annualTurnover <= self::SMALL_COMPANY_THRESHOLD ? 'small' : 'large';
    }

    /**
     * CIT is due 6 months after the company's accounting year end.
     * For December year-end companies, due by June 30th.
     */
    public function getFilingDueDate(int $taxYear): string
    {
        return Carbon::create($taxYear + 1, 6, 30)->toDateString();
    }

    /**
     * Generate CIT dashboard summary.
     */
    public function getDashboardSummary(Tenant $tenant): array
    {
        $currentYear = now()->year;
        $lastRecord  = CitRecord::where('tenant_id', $tenant->id)
            ->orderBy('tax_year', 'desc')
            ->first();

        return [
            'current_year'    => $currentYear,
            'company_size'    => $tenant->tax_category,
            'cit_rate'        => $tenant->getCitRate(),
            'last_filed_year' => $lastRecord?->tax_year,
            'last_status'     => $lastRecord?->status,
            'total_paid'      => (float)($lastRecord?->amount_paid ?? 0),
            'total_due'       => (float)($lastRecord?->total_tax_due ?? 0),
            'is_exempt'       => $tenant->tax_category === 'small',
            'next_due_date'   => $this->getFilingDueDate($currentYear - 1),
        ];
    }
}
