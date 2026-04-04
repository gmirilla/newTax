<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id', 'employee_id',
        // Earnings
        'basic_salary', 'housing_allowance', 'transport_allowance',
        'medical_allowance', 'utility_allowance', 'other_allowances',
        'overtime', 'bonus', 'gross_pay',
        // Statutory deductions
        'pension_employee', 'pension_employer', 'nhf', 'nhis',
        // NTA 2025 tax reliefs (reduce taxable income for PAYE)
        'home_loan_relief', 'life_insurance_relief', 'rent_relief',
        // PAYE workings
        'consolidated_relief', 'taxable_income', 'paye_tax',
        // Variable deductions
        'loan_deduction', 'advance_deduction', 'penalty_deduction', 'other_deductions',
        'net_pay', 'notes',
    ];

    protected $casts = [
        'basic_salary'        => 'decimal:2',
        'housing_allowance'   => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'medical_allowance'   => 'decimal:2',
        'utility_allowance'   => 'decimal:2',
        'other_allowances'    => 'decimal:2',
        'overtime'            => 'decimal:2',
        'bonus'               => 'decimal:2',
        'gross_pay'           => 'decimal:2',
        'pension_employee'    => 'decimal:2',
        'pension_employer'    => 'decimal:2',
        'nhf'                 => 'decimal:2',
        'nhis'                  => 'decimal:2',
        'home_loan_relief'      => 'decimal:2',
        'life_insurance_relief' => 'decimal:2',
        'rent_relief'           => 'decimal:2',
        'consolidated_relief'   => 'decimal:2',
        'taxable_income'      => 'decimal:2',
        'paye_tax'            => 'decimal:2',
        'loan_deduction'      => 'decimal:2',
        'advance_deduction'   => 'decimal:2',
        'penalty_deduction'   => 'decimal:2',
        'other_deductions'    => 'decimal:2',
        'net_pay'             => 'decimal:2',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
