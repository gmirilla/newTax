<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'employee_id', 'first_name', 'last_name',
        'email', 'phone', 'address', 'state_of_origin', 'state_of_residence',
        'tin', 'bvn', 'bank_name', 'account_number', 'account_name',
        'hire_date', 'termination_date', 'job_title', 'department', 'employment_type',
        'basic_salary', 'housing_allowance', 'transport_allowance',
        'medical_allowance', 'utility_allowance', 'other_allowances', 'gross_salary',
        'pension_rate', 'nhf_rate', 'nhf_enabled',
        'nhis_enabled', 'nhis_amount',
        'home_loan_interest', 'life_insurance_premium', 'annual_rent',
        'is_active',
    ];

    protected $casts = [
        'hire_date'           => 'date',
        'termination_date'    => 'date',
        'basic_salary'        => 'decimal:2',
        'housing_allowance'   => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'medical_allowance'   => 'decimal:2',
        'utility_allowance'   => 'decimal:2',
        'other_allowances'    => 'decimal:2',
        'gross_salary'        => 'decimal:2',
        'pension_rate'        => 'decimal:2',
        'nhf_rate'            => 'decimal:2',
        'nhis_amount'           => 'decimal:2',
        'home_loan_interest'    => 'decimal:2',
        'life_insurance_premium'=> 'decimal:2',
        'annual_rent'           => 'decimal:2',
        'nhf_enabled'           => 'boolean',
        'nhis_enabled'          => 'boolean',
        'is_active'           => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function calculateGrossSalary(): float
    {
        return (float) ($this->basic_salary
            + $this->housing_allowance
            + $this->transport_allowance
            + $this->medical_allowance
            + ($this->utility_allowance ?? 0)
            + $this->other_allowances);
    }
}
