<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhtRecord extends Model
{
    use HasFactory;

    protected $table = 'wht_records';

    protected $fillable = [
        'tenant_id', 'vendor_id', 'expense_id', 'invoice_id',
        'deduction_date', 'gross_amount', 'transaction_type',
        'wht_rate', 'wht_amount', 'net_payment',
        'is_company', 'vendor_tin', 'credit_note_number',
        'tax_month', 'tax_year', 'filing_status',
        'remittance_date', 'remittance_reference',
    ];

    protected $casts = [
        'deduction_date'   => 'date',
        'remittance_date'  => 'date',
        'gross_amount'     => 'decimal:2',
        'wht_rate'         => 'decimal:2',
        'wht_amount'       => 'decimal:2',
        'net_payment'      => 'decimal:2',
        'is_company'       => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
