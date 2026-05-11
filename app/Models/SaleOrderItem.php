<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleOrderItem extends Model
{
    protected $fillable = [
        'sale_order_id', 'item_id', 'description',
        'quantity', 'unit_price', 'cost_price_at_sale',
        'subtotal', 'vat_applicable', 'vat_rate', 'vat_amount',
        'total', 'sort_order',
    ];

    protected $casts = [
        'quantity'           => 'decimal:3',
        'unit_price'         => 'decimal:2',
        'cost_price_at_sale' => 'decimal:2',
        'subtotal'           => 'decimal:2',
        'vat_applicable'     => 'boolean',
        'vat_rate'           => 'decimal:2',
        'vat_amount'         => 'decimal:2',
        'total'              => 'decimal:2',
    ];

    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sale_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function calculateTotals(): void
    {
        $this->subtotal   = round((float) $this->quantity * (float) $this->unit_price, 2);
        $this->vat_amount = $this->vat_applicable
            ? round($this->subtotal * (float) $this->vat_rate / 100, 2)
            : 0;
        $this->total = $this->subtotal + $this->vat_amount;
    }

    public function cogs(): float
    {
        return round((float) $this->quantity * (float) $this->cost_price_at_sale, 2);
    }
}
