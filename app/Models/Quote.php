<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'customer_id',
        'quote_number', 'reference', 'quote_date', 'expiry_date',
        'subtotal', 'vat_amount', 'wht_amount', 'discount_amount', 'total_amount',
        'vat_applicable', 'wht_applicable', 'wht_rate',
        'status', 'notes', 'terms', 'currency',
        'converted_invoice_id', 'created_by',
    ];

    protected $casts = [
        'quote_date'      => 'date',
        'expiry_date'     => 'date',
        'subtotal'        => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'wht_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'vat_applicable'  => 'boolean',
        'wht_applicable'  => 'boolean',
        'wht_rate'        => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('quotes.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo   { return $this->belongsTo(Tenant::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent']);
    }

    public function isExpired(): bool
    {
        return $this->status === 'sent' && $this->expiry_date->isPast();
    }

    public function recalculateTotals(): void
    {
        $this->load('items');
        $subtotal  = $this->items->sum('subtotal');
        $vatAmount = $this->vat_applicable
            ? $this->items->where('vat_applicable', true)->sum('vat_amount')
            : 0;
        $whtAmount = $this->wht_applicable
            ? round($subtotal * $this->wht_rate / 100, 2)
            : 0;

        $this->update([
            'subtotal'        => $subtotal,
            'vat_amount'      => $vatAmount,
            'wht_amount'      => $whtAmount,
            'total_amount'    => $subtotal + $vatAmount - $whtAmount - $this->discount_amount,
        ]);
    }
}
