<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderLine extends Model
{
    protected $fillable = [
        'production_order_id', 'raw_material_item_id',
        'quantity_required', 'quantity_consumed', 'unit_cost_at_production',
    ];

    protected $casts = [
        'quantity_required'       => 'decimal:3',
        'quantity_consumed'       => 'decimal:3',
        'unit_cost_at_production' => 'decimal:4',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'raw_material_item_id');
    }
}
