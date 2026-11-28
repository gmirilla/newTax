<?php

namespace App\Models;

use App\Models\Concerns\HasDiscount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quote extends Model
{
    use HasFactory, SoftDeletes, HasDiscount;

    protected $fillable = [
        'tenant_id', 'customer_id',
        'quote_number', 'reference', 'quote_date', 'expiry_date',
        'subtotal', 'vat_amount', 'wht_amount', 'discount_amount', 'discount_type', 'discount_value', 'total_amount',
        'vat_applicable', 'wht_applicable', 'wht_rate',
        'status', 'notes', 'terms', 'currency', 'public_token',
        'converted_invoice_id', 'created_by',
    ];

    protected $casts = [
        'quote_date'      => 'date',
        'expiry_date'     => 'date',
        'subtotal'        => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'wht_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_value'  => 'decimal:2',
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

        static::creating(function (Quote $quote) {
            if (empty($quote->public_token)) {
                $quote->public_token = (string) Str::uuid();
            }
        });
    }

    public function publicUrl(): string
    {
        return route('quote.public.show', ['token' => $this->public_token]);
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
        $subtotal = (float) $this->items->sum('subtotal');
        $lineVat  = (float) $this->items->where('vat_applicable', true)->sum('vat_amount');
        $t        = $this->computeTotals($subtotal, $lineVat);

        $this->update([
            'subtotal'        => $subtotal,
            'vat_amount'      => $t['vat'],
            'wht_amount'      => $t['wht'],
            'discount_amount' => $t['discount'],
            'total_amount'    => $t['total'],
        ]);
    }
}
