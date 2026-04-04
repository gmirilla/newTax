<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Tenant;
use App\Models\Vendor;
use App\Models\WhtRecord;
use App\Services\CitService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WhtService
{
    /**
     * Nigerian Withholding Tax (WHT) Rates per FIRS guidelines.
     * WHT is deducted at source by the payer and remitted to FIRS.
     *
     * Rates vary based on:
     * - Transaction type (services, contracts, rent, dividends, etc.)
     * - Recipient type (company vs. individual)
     */
    public const WHT_RATES = [
        // Companies vs Individuals
        'services' => [
            'company'    => 5.0,  // Services by registered companies
            'individual' => 10.0, // Services by individuals
        ],
        'contracts' => [
            'company'    => 5.0,
            'individual' => 5.0,
        ],
        'rent' => [
            'company'    => 10.0,
            'individual' => 10.0,
        ],
        'dividends' => [
            'company'    => 10.0,
            'individual' => 10.0,
        ],
        'interest' => [
            'company'    => 10.0,
            'individual' => 10.0,
        ],
        'royalties' => [
            'company'    => 10.0,
            'individual' => 10.0,
        ],
        'technical_fees' => [
            'company'    => 10.0,
            'individual' => 10.0,
        ],
        'directors_fees' => [
            'company'    => 10.0,
            'individual' => 10.0,
        ],
        'commissions' => [
            'company'    => 10.0,
            'individual' => 10.0,
        ],
    ];

    /**
     * Get the applicable WHT rate for a transaction.
     */
    public function getRate(string $transactionType, bool $isCompany): float
    {
        $type     = $this->normalizeType($transactionType);
        $category = $isCompany ? 'company' : 'individual';

        return self::WHT_RATES[$type][$category] ?? 5.0;
    }

    /**
     * Calculate WHT amount on a gross payment.
     */
    public function calculate(float $grossAmount, string $transactionType, bool $isCompany): array
    {
        $rate      = $this->getRate($transactionType, $isCompany);
        $whtAmount = round($grossAmount * $rate / 100, 2);
        $netAmount = round($grossAmount - $whtAmount, 2);

        return [
            'gross_amount'  => $grossAmount,
            'wht_rate'      => $rate,
            'wht_amount'    => $whtAmount,
            'net_payment'   => $netAmount,
        ];
    }

    /**
     * WHT small-business exemption (2026 — Finance Act 2025):
     * Small businesses (turnover ≤ ₦50M) are exempt from WHT
     * on transactions under ₦2,000,000 per month.
     */
    public const WHT_EXEMPTION_THRESHOLD = 2_000_000; // ₦2M per transaction

    public function isExemptFromWht(Tenant $tenant, float $amount): bool
    {
        $isSmallBusiness = ($tenant->annual_turnover ?? 0) <= CitService::SMALL_COMPANY_THRESHOLD;
        return $isSmallBusiness && $amount < self::WHT_EXEMPTION_THRESHOLD;
    }

    /**
     * Deduct WHT from a vendor payment and create a WHT record.
     * Returns null if the transaction qualifies for the small-business WHT exemption.
     */
    public function deductFromExpense(Expense $expense, Vendor $vendor): ?WhtRecord
    {
        $tenant = Tenant::find($expense->tenant_id);
        if ($tenant && $this->isExemptFromWht($tenant, (float)$expense->amount)) {
            return null; // Exempt — no WHT deducted
        }

        $transactionType = $this->mapVendorTypeToTransaction($vendor->vendor_type);
        $calculation     = $this->calculate(
            (float) $expense->amount,
            $transactionType,
            true // vendor is a company by default
        );

        $whtRecord = WhtRecord::create([
            'tenant_id'        => $expense->tenant_id,
            'vendor_id'        => $vendor->id,
            'expense_id'       => $expense->id,
            'deduction_date'   => $expense->expense_date,
            'gross_amount'     => $calculation['gross_amount'],
            'transaction_type' => $transactionType,
            'wht_rate'         => $calculation['wht_rate'],
            'wht_amount'       => $calculation['wht_amount'],
            'net_payment'      => $calculation['net_payment'],
            'is_company'       => true,
            'vendor_tin'       => $vendor->tin,
            'tax_month'        => $expense->expense_date->month,
            'tax_year'         => $expense->expense_date->year,
            'filing_status'    => 'pending',
        ]);

        // Update expense with WHT details
        $expense->update([
            'wht_applicable' => true,
            'wht_rate'       => $calculation['wht_rate'],
            'wht_amount'     => $calculation['wht_amount'],
            'net_payable'    => $calculation['net_payment'],
        ]);

        return $whtRecord;
    }

    /**
     * Generate monthly WHT schedule for FIRS remittance.
     * WHT is remitted monthly along with VAT.
     */
    public function generateMonthlySchedule(Tenant $tenant, int $year, int $month): array
    {
        $records = WhtRecord::where('tenant_id', $tenant->id)
            ->where('tax_year', $year)
            ->where('tax_month', $month)
            ->with('vendor')
            ->get();

        $summary = [
            'period'        => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'tax_year'      => $year,
            'tax_month'     => $month,
            'total_gross'   => $records->sum('gross_amount'),
            'total_wht'     => $records->sum('wht_amount'),
            'total_net'     => $records->sum('net_payment'),
            'records'       => $records,
            'by_type'       => $records->groupBy('transaction_type')->map(function ($group) {
                return [
                    'count'      => $group->count(),
                    'gross'      => $group->sum('gross_amount'),
                    'wht_amount' => $group->sum('wht_amount'),
                ];
            }),
        ];

        return $summary;
    }

    /**
     * Get total pending WHT to remit.
     */
    public function getPendingRemittance(Tenant $tenant): float
    {
        return (float) WhtRecord::where('tenant_id', $tenant->id)
            ->where('filing_status', 'pending')
            ->sum('wht_amount');
    }

    private function normalizeType(string $type): string
    {
        return match ($type) {
            'services', 'service' => 'services',
            'contracts', 'contract' => 'contracts',
            'rent', 'lease' => 'rent',
            'dividends', 'dividend' => 'dividends',
            'interest' => 'interest',
            'royalties', 'royalty' => 'royalties',
            'technical_fees', 'management_fees' => 'technical_fees',
            default => 'services',
        };
    }

    private function mapVendorTypeToTransaction(string $vendorType): string
    {
        return match ($vendorType) {
            'goods'     => 'contracts',
            'services'  => 'services',
            'rent'      => 'rent',
            'mixed'     => 'services',
            default     => 'services',
        };
    }
}
