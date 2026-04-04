<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'vendor_id', 'account_id', 'transaction_id',
        'reference', 'expense_date', 'category', 'description',
        'amount', 'vat_applicable', 'vat_amount',
        'wht_applicable', 'wht_rate', 'wht_amount', 'net_payable',
        'status', 'receipt_path', 'notes', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'expense_date'   => 'date',
        'amount'         => 'decimal:2',
        'vat_applicable' => 'boolean',
        'vat_amount'     => 'decimal:2',
        'wht_applicable' => 'boolean',
        'wht_rate'       => 'decimal:2',
        'wht_amount'     => 'decimal:2',
        'net_payable'    => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
