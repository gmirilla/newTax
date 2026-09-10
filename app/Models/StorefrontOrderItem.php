<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontOrderItem extends Model
{
    protected $fillable = [
        'storefront_order_id', 'inventory_item_id', 'storefront_service_id', 'description',
        'quantity', 'unit_price', 'vat_amount', 'subtotal', 'total',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'unit_price' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'subtotal'   => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(StorefrontOrder::class, 'storefront_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(StorefrontService::class, 'storefront_service_id');
    }

    public function calculateTotals(): void
    {
        $this->subtotal   = round((float) $this->quantity * (float) $this->unit_price, 2);
        $this->vat_amount = round($this->subtotal * Invoice::VAT_RATE / 100, 2);
        $this->total      = $this->subtotal + $this->vat_amount;
    }
}
