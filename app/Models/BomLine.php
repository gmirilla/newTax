<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomLine extends Model
{
    protected $fillable = [
        'bom_id', 'raw_material_item_id', 'quantity_required',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:3',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'raw_material_item_id');
    }
}
