<?php

namespace App\Services;

use App\Models\CitRecord;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\Tenant;
use App\Models\VatReturn;
use App\Models\WhtRecord;
use Carbon\Carbon;

class ReportService
{
    public function __construct(
        private readonly VatService       $vatService,
        private readonly WhtService       $whtService,
        private readonly CitService       $citService,
        private readonly BookkeepingService $bookkeepingService
    ) {}

    /**
     * Comprehensive tax compliance dashboard data.
     */
    public function getComplianceDashboard(Tenant $tenant, int $year): array
    {
        $currentMonth = now()->month;

        // ── VAT ───────────────────────────────────────────────────────────────
        $vatReturns = VatReturn::where('tenant_id', $tenant->id)
            ->where('tax_year', $year)
            ->get();

        $overdueVatCount = VatReturn::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();

        $outputVat = (float)Invoice::where('tenant_id', $tenant->id)
            ->whereYear('invoice_date', $year)
            ->whereIn('status', ['sent', 'partial', 'paid'])
            ->sum('vat_amount');

        $inputVat = (float)Expense::where('tenant_id', $tenant->id)
            ->whereYear('expense_date', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('vat_amount');

        $returnsDue   = min($currentMonth, 12); // months elapsed so far in selected year
        $returnsFiled = $vatReturns->whereIn('status', ['filed', 'paid'])->count();

        // ── WHT ───────────────────────────────────────────────────────────────
        $whtRecords = WhtRecord::where('tenant_id', $tenant->id)
            ->whereYear('deduction_date', $year)
            ->get();

        $whtDeducted  = (float)$whtRecords->sum('wht_amount');
        $whtRemitted  = (float)$whtRecords->where('filing_status', 'remitted')->sum('wht_amount');
        $whtOutstanding = round($whtDeducted - $whtRemitted, 2);

        // ── CIT ───────────────────────────────────────────────────────────────
        // CIT is filed for the previous fiscal year (e.g., viewing 2026 → CIT record is for 2025)
        $citTaxYear = $year - 1;
        $citRecord  = CitRecord::where('tenant_id', $tenant->id)
            ->where('tax_year', $citTaxYear)
            ->first();

        $citCompanySize    = $tenant->tax_category ?? 'small';
        $citRate           = $tenant->getCitRate();
        $citAssessable     = (float)($citRecord?->taxable_profit ?? 0);
        $citPayable        = (float)($citRecord?->cit_amount ?? 0);
        $citDevLevy        = (float)($citRecord?->development_levy ?? $citRecord?->education_levy ?? 0);
        $citTotal          = (float)($citRecord?->total_tax_due ?? 0);
        $citFilingDeadline = \Carbon\Carbon::parse(
            $this->citService->getFilingDueDate($citTaxYear)
        )->format('d M Y');

        // ── PAYE / Payroll ────────────────────────────────────────────────────
        $payrolls = Payroll::where('tenant_id', $tenant->id)
            ->where('pay_year', $year)
            ->get();

        $employeeCount   = Employee::where('tenant_id', $tenant->id)->where('is_active', true)->count();
        $totalPayroll    = round($payrolls->sum('total_gross'), 2);
        $totalPaye       = round($payrolls->sum('total_paye'), 2);
        $totalPension    = round($payrolls->sum('total_pension'), 2);
        $totalNhf        = round($payrolls->sum('total_nhf'), 2);

        // ── Compliance score ─────────────────────────────────────────────────
        $compliance_score = $this->computeComplianceScore($tenant, $overdueVatCount);

        // ── Action items ──────────────────────────────────────────────────────
        $actionItems = [];

        if ($overdueVatCount > 0) {
            $actionItems[] = [
                'title'       => "{$overdueVatCount} overdue VAT return(s)",
                'description' => 'File outstanding VAT returns immediately to avoid penalties.',
            ];
        }

        if ($whtOutstanding > 0) {
            $actionItems[] = [
                'title'       => 'Outstanding WHT not remitted',
                'description' => '₦' . number_format($whtOutstanding, 2) . ' in WHT deducted but not yet remitted to FIRS.',
            ];
        }

        if (!$citRecord && $citTaxYear >= 2020) {
            $actionItems[] = [
                'title'       => "CIT not computed for {$citTaxYear}",
                'description' => 'Go to Tax → Company Income Tax → Compute CIT to generate your return.',
                'due_date'    => $citFilingDeadline,
            ];
        }

        if (empty($tenant->tin)) {
            $actionItems[] = [
                'title'       => 'Company TIN not on file',
                'description' => 'Add your Tax Identification Number in company settings.',
            ];
        }

        // ── Invoice / revenue summary (for dashboard KPI cards) ─────────────
        $totalRevenue  = (float)Invoice::where('tenant_id', $tenant->id)
            ->whereYear('invoice_date', $year)
            ->whereIn('status', ['sent', 'partial', 'paid'])
            ->sum('subtotal');

        $outstanding = (float)Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->sum('balance_due');

        $nextVatDue = $this->vatService->getFilingDueDate($year, $currentMonth);

        $citIsExempt = ($citCompanySize === 'small');
        $lastFiledCit = CitRecord::where('tenant_id', $tenant->id)
            ->whereIn('status', ['filed', 'paid'])
            ->orderBy('tax_year', 'desc')
            ->value('tax_year');

        return [
            'compliance_score' => $compliance_score,
            'score'            => $compliance_score, // alias for tax-summary view

            'invoices' => [
                'total_revenue' => $totalRevenue,
                'outstanding'   => $outstanding,
            ],

            'vat' => [
                'registered'    => $tenant->isVatRegistered(),
                'output_total'  => $outputVat,
                'input_total'   => $inputVat,
                'net'           => round($outputVat - $inputVat, 2),
                'returns_filed' => $returnsFiled,
                'returns_due'   => $returnsDue,       // months elapsed — for tax-summary
                'overdue_count' => $overdueVatCount,  // truly overdue — for dashboard banner
                'next_due'      => $nextVatDue,
                'ytd_summary'   => [
                    'ytd_output_vat' => $outputVat,
                    'ytd_input_vat'  => $inputVat,
                    'ytd_net_vat'    => round($outputVat - $inputVat, 2),
                ],
            ],

            'wht' => [
                'total_deducted'     => $whtDeducted,
                'total_remitted'     => $whtRemitted,
                'outstanding'        => $whtOutstanding,
                'pending_remittance' => $whtOutstanding, // alias for dashboard
            ],

            'cit' => [
                'company_size'      => $citCompanySize,
                'rate'              => $citRate,
                'assessable_profit' => $citAssessable,
                'cit_payable'       => $citPayable,
                'education_tax'     => $citDevLevy,
                'total_liability'   => $citTotal,
                'filing_deadline'   => $citFilingDeadline,
                // nested 'summary' sub-array for dashboard CIT card
                'summary' => [
                    'company_size'    => $citCompanySize,
                    'cit_rate'        => $citRate,
                    'is_exempt'       => $citIsExempt,
                    'last_filed_year' => $lastFiledCit,
                    'next_due_date'   => $citFilingDeadline,
                ],
            ],

            'paye' => [
                'employee_count'         => $employeeCount,
                'total_payroll'          => $totalPayroll,
                'total_paye'             => $totalPaye,
                'total_pension_employee' => $totalPension,
                'total_nhf'              => $totalNhf,
            ],

            // 'payroll' key with dashboard-expected sub-keys
            'payroll' => [
                'employee_count' => $employeeCount,
                'months_run'     => $payrolls->count(),
                'total_gross'    => $totalPayroll,
                'total_paye'     => $totalPaye,
                'total_net'      => round($payrolls->sum('total_net'), 2),
            ],

            'action_items' => $actionItems,
        ];
    }

    /**
     * Monthly VAT return report data.
     */
    public function getVatReport(Tenant $tenant, int $year, int $month): array
    {
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->where('vat_applicable', true)
            ->whereMonth('invoice_date', $month)
            ->whereYear('invoice_date', $year)
            ->whereIn('status', ['sent', 'partial', 'paid'])
            ->with('customer')
            ->get();

        $expenses = Expense::where('tenant_id', $tenant->id)
            ->where('vat_applicable', true)
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->with('vendor')
            ->get();

        return [
            'period'        => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'tax_year'      => $year,
            'tax_month'     => $month,
            'output_vat'    => [
                'items'     => $invoices,
                'total'     => round($invoices->sum('vat_amount'), 2),
            ],
            'input_vat'     => [
                'items'     => $expenses,
                'total'     => round($expenses->sum('vat_amount'), 2),
            ],
            'net_vat'       => round($invoices->sum('vat_amount') - $expenses->sum('vat_amount'), 2),
            'due_date'      => $this->vatService->getFilingDueDate($year, $month),
        ];
    }

    /**
     * Annual CIT computation report.
     */
    public function getCitReport(Tenant $tenant, int $year): array
    {
        $computation = $this->citService->compute($tenant, $year);

        $monthlyRevenue = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyRevenue[$m] = (float)Invoice::where('tenant_id', $tenant->id)
                ->whereYear('invoice_date', $year)
                ->whereMonth('invoice_date', $m)
                ->whereIn('status', ['sent', 'partial', 'paid'])
                ->sum('subtotal');
        }

        return array_merge($computation, [
            'tenant'          => $tenant,
            'monthly_revenue' => $monthlyRevenue,
        ]);
    }

    private function getPayrollSummary(Tenant $tenant, int $year): array
    {
        $payrolls = Payroll::where('tenant_id', $tenant->id)
            ->where('pay_year', $year)
            ->get();

        return [
            'total_gross'    => round($payrolls->sum('total_gross'), 2),
            'total_paye'     => round($payrolls->sum('total_paye'), 2),
            'total_pension'  => round($payrolls->sum('total_pension'), 2),
            'total_net'      => round($payrolls->sum('total_net'), 2),
            'months_run'     => $payrolls->count(),
            'employee_count' => Employee::where('tenant_id', $tenant->id)->where('is_active', true)->count(),
        ];
    }

    private function computeComplianceScore(Tenant $tenant, int $overdueVatReturns): int
    {
        $compliance_score = 100;

        // Deduct for overdue VAT returns (-10 per overdue return, max -50)
        $compliance_score -= min(50, $overdueVatReturns * 10);

        // Deduct if VAT not registered but should be
        if (!$tenant->vat_registered && $tenant->annual_turnover >= Tenant::VAT_THRESHOLD) {
            $compliance_score -= 20;
        }

        // Deduct for missing TIN
        if (empty($tenant->tin)) {
            $compliance_score -= 10;
        }

        return max(0, $compliance_score);
    }
}
