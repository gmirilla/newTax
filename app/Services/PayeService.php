<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollItem;

class PayeService
{
    /**
     * Nigerian PAYE (Personal Income Tax) — 2026 rates.
     * Personal Income Tax Act (PITA) as amended by Finance Act 2024/2025.
     *
     * Tax-free threshold: ₦800,000/year (≈ ₦66,667/month)
     * Progressive annual bands (applied AFTER the ₦800k tax-free amount):
     *   First  ₦800,000  →  0%  (tax-free band)
     *   Next ₦2,200,000  → 15%  (₦800k – ₦3M)
     *   Next ₦9,000,000  → 18%  (₦3M   – ₦12M)
     *   Next₦13,000,000  → 21%  (₦12M  – ₦25M)
     *   Next₦25,000,000  → 23%  (₦25M  – ₦50M)
     *   Above₦50,000,000 → 25%
     *
     * Consolidated Relief Allowance (CRA):
     *   max(₦200,000, 1% of gross) + 20% of gross
     *
     * Taxable income = Gross - Pension(employee) - NHF - NHIS - CRA
     */

    public const TAX_BANDS = [
        ['limit' => 800_000,    'rate' => 0],   // Tax-free band
        ['limit' => 2_200_000,  'rate' => 15],  // ₦800k – ₦3M
        ['limit' => 9_000_000,  'rate' => 18],  // ₦3M   – ₦12M
        ['limit' => 13_000_000, 'rate' => 21],  // ₦12M  – ₦25M
        ['limit' => 25_000_000, 'rate' => 23],  // ₦25M  – ₦50M
        ['limit' => PHP_INT_MAX,'rate' => 25],  // Above ₦50M
    ];

    // CRA constants
    public const CONSOLIDATED_RELIEF_FLAT = 0; // ₦200,000 p.a.
    public const CONSOLIDATED_RELIEF_PCT  = 0;      // 20% of gross income
    public const MIN_RELIEF_PCT           = 0;       // 1% of gross (floor for flat component)

    // Pension (Contributory Pension Scheme — PRA 2014)
    public const PENSION_EMPLOYEE_RATE = 8;   // 8% of (basic + housing + transport)
    public const PENSION_EMPLOYER_RATE = 10;  // 10% employer contribution (not deducted from employee)

    // National Housing Fund (Federal Mortgage Bank Act)
    public const NHF_RATE = 2.5; // 2.5% of basic salary (monthly)

    // NTA 2025 personal tax reliefs (Nigeria Tax Act 2025 — effective 1 Jan 2026)
    public const RENT_RELIEF_RATE = 20;      // 20% of annual rent paid
    public const RENT_RELIEF_CAP  = 500_000; // ₦500,000 p.a. maximum

    /**
     * Compute monthly PAYE and all statutory deductions for an employee.
     *
     * $overrides can carry per-payroll-run values:
     *   overtime, bonus, loan_deduction, advance_deduction, penalty_deduction, notes
     *
     * Returns an unsaved PayrollItem with all fields populated.
     */
    public function computeMonthlyPaye(Employee $employee, array $overrides = []): PayrollItem
    {
        // ── 1. Earnings ──────────────────────────────────────────────────────
        $basicSalary        = (float)$employee->basic_salary;
        $housingAllowance   = (float)$employee->housing_allowance;
        $transportAllowance = (float)$employee->transport_allowance;
        $medicalAllowance   = (float)$employee->medical_allowance;
        $utilityAllowance   = (float)($employee->utility_allowance ?? 0);
        $otherAllowances    = (float)$employee->other_allowances;

        $overtime = (float)($overrides['overtime'] ?? 0);
        $bonus    = (float)($overrides['bonus'] ?? 0);

        // Regular monthly gross (base pay, no overtime/bonus — used for recurring deduction base)
        $regularGross = $basicSalary + $housingAllowance + $transportAllowance
            + $medicalAllowance + $utilityAllowance + $otherAllowances;

        // Total gross this month including variable pay
        $totalGross = $regularGross + $overtime + $bonus;

        // ── 2. Pension (8% employee / 10% employer on basic+housing+transport) ─
        $pensionBase     = $basicSalary + $housingAllowance + $transportAllowance;
        $pensionEmployee = round($pensionBase * self::PENSION_EMPLOYEE_RATE / 100, 2);
        $pensionEmployer = round($pensionBase * self::PENSION_EMPLOYER_RATE / 100, 2);

        // ── 3. NHF — 2.5% of basic (optional per employee) ──────────────────
        $nhf = ($employee->nhf_enabled ?? true)
            ? round($basicSalary * self::NHF_RATE / 100, 2)
            : 0.0;

        // ── 4. NHIS/HMO — fixed monthly ₦ amount per employee ───────────────
        $nhis = ($employee->nhis_enabled ?? false)
            ? round((float)($employee->nhis_amount ?? 0), 2)
            : 0.0;

        // ── 5. PAYE — annualise regular gross for consistent monthly tax ─────
        // Bonus/overtime are added to this month's tax base (one-off uplift)
        $annualRegularGross = $regularGross * 12;
        $annualBonus        = $bonus + $overtime; // taxed fully in month received

        $annualPensionEmployee = $pensionEmployee * 12;
        $annualNhf             = $nhf * 12;
        $annualNhis            = $nhis * 12;

        // CRA on total annual income
        $annualTotalGross = $annualRegularGross + $annualBonus;
        $cra = $this->computeConsolidatedRelief($annualTotalGross);

        // ── 5b. NTA 2025 personal tax reliefs ────────────────────────────────
        // Home loan interest — fully deductible (NTA 2025 §X)
        $annualHomeLoanRelief = max(0, (float)($employee->home_loan_interest ?? 0));

        // Life / annuity insurance premiums — fully deductible (NTA 2025 §X)
        $annualLifeInsRelief = max(0, (float)($employee->life_insurance_premium ?? 0));

        // Rent relief — 20% of annual rent, capped at ₦500,000 (NTA 2025 §X)
        $annualRent        = max(0, (float)($employee->annual_rent ?? 0));
        $annualRentRelief  = min(
            $annualRent * self::RENT_RELIEF_RATE / 100,
            self::RENT_RELIEF_CAP
        );

        // Annual taxable = gross - pension - NHF - NHIS - CRA - NTA 2025 reliefs
        $annualTaxable = max(0,
            $annualTotalGross
            - $annualPensionEmployee
            - $annualNhf
            - $annualNhis
            - $cra
            - $annualHomeLoanRelief
            - $annualLifeInsRelief
            - $annualRentRelief
        );

        // Annual PAYE → monthly PAYE
        $annualPaye  = $this->computeProgressiveTax($annualTaxable);
        $monthlyPaye = round($annualPaye / 12, 2);

        // ── 6. Other deductions ───────────────────────────────────────────────
        $loanDeduction    = (float)($overrides['loan_deduction'] ?? 0);
        $advanceDeduction = (float)($overrides['advance_deduction'] ?? 0);
        $penaltyDeduction = (float)($overrides['penalty_deduction'] ?? 0);

        // ── 7. Net pay ────────────────────────────────────────────────────────
        $totalDeductions = $pensionEmployee + $nhf + $nhis + $monthlyPaye
            + $loanDeduction + $advanceDeduction + $penaltyDeduction;

        $netPay = round($totalGross - $totalDeductions, 2);

        // ── 8. Build PayrollItem ──────────────────────────────────────────────
        return new PayrollItem([
            'basic_salary'        => $basicSalary,
            'housing_allowance'   => $housingAllowance,
            'transport_allowance' => $transportAllowance,
            'medical_allowance'   => $medicalAllowance,
            'utility_allowance'   => $utilityAllowance,
            'other_allowances'    => $otherAllowances,
            'overtime'            => $overtime,
            'bonus'               => $bonus,
            'gross_pay'           => round($totalGross, 2),

            // Statutory deductions
            'pension_employee'    => $pensionEmployee,
            'pension_employer'    => $pensionEmployer,
            'nhf'                 => $nhf,
            'nhis'                => $nhis,

            // NTA 2025 tax reliefs (stored as monthly portions — annual ÷ 12)
            'home_loan_relief'      => round($annualHomeLoanRelief / 12, 2),
            'life_insurance_relief' => round($annualLifeInsRelief / 12, 2),
            'rent_relief'           => round($annualRentRelief / 12, 2),

            // PAYE workings
            'consolidated_relief' => round($cra / 12, 2),
            'taxable_income'      => round($annualTaxable / 12, 2),
            'paye_tax'            => $monthlyPaye,

            // Variable deductions
            'loan_deduction'      => $loanDeduction,
            'advance_deduction'   => $advanceDeduction,
            'penalty_deduction'   => $penaltyDeduction,
            'other_deductions'    => (float)($overrides['other_deductions'] ?? 0),

            'net_pay'             => $netPay,
            'notes'               => $overrides['notes'] ?? null,
        ]);
    }

    /**
     * CRA = max(₦200,000, 1% of gross) + 20% of gross
     */
    public function computeConsolidatedRelief(float $annualGross): float
    {
        $flatComponent = max(
            self::CONSOLIDATED_RELIEF_FLAT,
            $annualGross * self::MIN_RELIEF_PCT / 100
        );
        $pctComponent = $annualGross * self::CONSOLIDATED_RELIEF_PCT / 100;

        return round($flatComponent + $pctComponent, 2);
    }

    /**
     * Apply 2026 progressive PAYE tax bands to annual taxable income.
     * Includes a ₦800,000 tax-free band as the first bracket.
     */
    public function computeProgressiveTax(float $annualTaxableIncome): float
    {
        if ($annualTaxableIncome <= 0) {
            return 0.0;
        }

        $tax       = 0.0;
        $remaining = $annualTaxableIncome;

        foreach (self::TAX_BANDS as $band) {
            if ($remaining <= 0) break;

            $taxableInBand  = min($remaining, $band['limit']);
            $tax           += $taxableInBand * $band['rate'] / 100;
            $remaining     -= $taxableInBand;
        }

        return round($tax, 2);
    }

    /**
     * Generate structured payslip data for a PayrollItem.
     */
    public function generatePayslip(PayrollItem $item): array
    {
        $earnings = array_filter([
            'Basic Salary'         => (float)$item->basic_salary,
            'Housing Allowance'    => (float)$item->housing_allowance,
            'Transport Allowance'  => (float)$item->transport_allowance,
            'Medical Allowance'    => (float)$item->medical_allowance,
            'Utility Allowance'    => (float)($item->utility_allowance ?? 0),
            'Other Allowances'     => (float)$item->other_allowances,
            'Overtime'             => (float)$item->overtime,
            'Bonus / One-off'      => (float)$item->bonus,
        ], fn($v) => $v > 0);

        $deductions = array_filter([
            'Employee Pension (8%)'         => (float)$item->pension_employee,
            'NHF (2.5% of Basic)'           => (float)$item->nhf,
            'NHIS / HMO'                    => (float)($item->nhis ?? 0),
            'PAYE Tax'                      => (float)$item->paye_tax,
            'Loan Repayment'                => (float)($item->loan_deduction ?? 0),
            'Salary Advance Recovery'       => (float)($item->advance_deduction ?? 0),
            'Penalty / Absence Deduction'   => (float)($item->penalty_deduction ?? 0),
            'Other Deductions'              => (float)$item->other_deductions,
        ], fn($v) => $v > 0);

        // NTA 2025 reliefs — only include if non-zero
        $taxReliefs = array_filter([
            'Home Loan Interest (NTA 2025)'  => (float)($item->home_loan_relief ?? 0),
            'Life Insurance Premium (NTA 2025)' => (float)($item->life_insurance_relief ?? 0),
            'Rent Relief — 20%, max ₦500k (NTA 2025)' => (float)($item->rent_relief ?? 0),
        ], fn($v) => $v > 0);

        return [
            'employee'           => $item->employee,
            'period'             => $item->payroll->getMonthName(),
            'earnings'           => $earnings,
            'gross_pay'          => (float)$item->gross_pay,
            'deductions'         => $deductions,
            'tax_reliefs'        => $taxReliefs,
            'employer_cost'      => [
                'Employer Pension (10%)' => (float)$item->pension_employer,
            ],
            'net_pay'            => (float)$item->net_pay,
            'taxable_income'     => (float)$item->taxable_income,
            'consolidated_relief'=> (float)$item->consolidated_relief,
            'notes'              => $item->notes,
        ];
    }
}
