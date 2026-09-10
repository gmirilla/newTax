<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StorefrontOrder extends Model
{
    use SoftDeletes;

    const STATUS_PENDING   = 'pending';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    const CHANNEL_WEB      = 'web';
    const CHANNEL_WHATSAPP = 'whatsapp';

    protected $fillable = [
        'tenant_id', 'order_number', 'token',
        'customer_name', 'customer_email', 'customer_phone', 'delivery_address',
        'subtotal', 'vat_amount', 'total_amount',
        'status', 'channel', 'notes', 'rejection_reason', 'sales_order_id',
    ];

    protected $casts = [
        'subtotal'     => 'decimal:2',
        'vat_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('storefront_orders.tenant_id', app('currentTenant')->id);
            }
        });

        static::creating(function (StorefrontOrder $order) {
            if (empty($order->token)) {
                $order->token = (string) Str::uuid();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StorefrontOrderItem::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function publicUrl(): string
    {
        return route('storefront.order.status', ['tenant' => $this->tenant->slug, 'token' => $this->token]);
    }

    public function canBeActioned(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** Plain-text order summary, used for the WhatsApp deep link and notification email. */
    public function summaryText(): string
    {
        $lines = ["Order {$this->order_number}"];

        foreach ($this->items as $item) {
            $lines[] = "- {$item->quantity} x {$item->description} (₦" . number_format((float) $item->total, 2) . ')';
        }

        $lines[] = 'Total: ₦' . number_format((float) $this->total_amount, 2);
        $lines[] = "Customer: {$this->customer_name} ({$this->customer_phone})";

        return implode("\n", $lines);
    }
}
