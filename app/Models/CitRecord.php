<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitRecord extends Model
{
    use HasFactory;

    protected $table = 'cit_records';

    protected $fillable = [
        'tenant_id', 'tax_year',
        'gross_profit', 'allowable_deductions', 'taxable_profit',
        'annual_turnover', 'company_size', 'cit_rate',
        'cit_amount', 'development_levy', 'education_levy', 'total_tax_due', 'minimum_tax',
        'due_date', 'filed_date', 'paid_date', 'amount_paid',
        'filing_reference', 'status', 'notes', 'filed_by',
    ];

    protected $casts = [
        'due_date'            => 'date',
        'filed_date'          => 'date',
        'paid_date'           => 'date',
        'gross_profit'        => 'decimal:2',
        'allowable_deductions'=> 'decimal:2',
        'taxable_profit'      => 'decimal:2',
        'annual_turnover'     => 'decimal:2',
        'cit_rate'            => 'decimal:2',
        'cit_amount'          => 'decimal:2',
        'development_levy'    => 'decimal:2',
        'education_levy'      => 'decimal:2',
        'total_tax_due'       => 'decimal:2',
        'minimum_tax'         => 'decimal:2',
        'amount_paid'         => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function filer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }
}
