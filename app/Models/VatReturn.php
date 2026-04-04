<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VatReturn extends Model
{
    use HasFactory;

    protected $table = 'vat_returns';

    protected $fillable = [
        'tenant_id', 'tax_year', 'tax_month',
        'period_start', 'period_end',
        'output_vat', 'input_vat', 'net_vat_payable',
        'due_date', 'filed_date', 'paid_date',
        'amount_paid', 'filing_reference',
        'status', 'notes', 'filed_by',
    ];

    protected $casts = [
        'period_start'    => 'date',
        'period_end'      => 'date',
        'due_date'        => 'date',
        'filed_date'      => 'date',
        'paid_date'       => 'date',
        'output_vat'      => 'decimal:2',
        'input_vat'       => 'decimal:2',
        'net_vat_payable' => 'decimal:2',
        'amount_paid'     => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function filer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    public function getMonthName(): string
    {
        return date('F', mktime(0, 0, 0, $this->tax_month, 1));
    }
}
