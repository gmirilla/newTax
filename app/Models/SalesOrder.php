<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT     = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'order_number', 'customer_id', 'customer_name',
        'sale_date', 'subtotal', 'vat_amount', 'discount_amount', 'total_amount',
        'payment_method', 'payment_reference', 'bank_account_id', 'status', 'notes',
        'invoice_id', 'transaction_id', 'created_by',
    ];

    protected $casts = [
        'sale_date'       => 'date',
        'subtotal'        => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('sales_orders.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleOrderItem::class, 'sale_order_id')->orderBy('sort_order');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculateTotals(): void
    {
        $this->load('items');
        $subtotal   = (float) $this->items->sum('subtotal');
        $vatAmount  = (float) $this->items->sum('vat_amount');
        $discount   = (float) $this->discount_amount;

        $this->subtotal     = $subtotal;
        $this->vat_amount   = $vatAmount;
        $this->total_amount = round($subtotal + $vatAmount - $discount, 2);
        $this->save();
    }

    public function isWalkIn(): bool
    {
        return is_null($this->customer_id);
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->items()->exists();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CONFIRMED]);
    }
}
