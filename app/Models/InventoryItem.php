<?php

namespace App\Models;

use App\Observers\InventoryItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(InventoryItemObserver::class)]
class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'category_id', 'name', 'sku', 'description', 'item_type', 'unit',
        'selling_price', 'cost_price', 'avg_cost',
        'current_stock', 'restock_level', 'is_active', 'created_by',
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'cost_price'    => 'decimal:2',
        'avg_cost'      => 'decimal:2',
        'current_stock' => 'decimal:3',
        'restock_level' => 'decimal:3',
        'is_active'     => 'boolean',
    ];

    public const TYPE_PRODUCT       = 'product';
    public const TYPE_RAW_MATERIAL  = 'raw_material';
    public const TYPE_FINISHED_GOOD = 'finished_good';
    public const TYPE_SEMI_FINISHED = 'semi_finished';

    public const ITEM_TYPES = [
        self::TYPE_PRODUCT       => 'Product',
        self::TYPE_RAW_MATERIAL  => 'Raw Material',
        self::TYPE_FINISHED_GOOD => 'Finished Good',
        self::TYPE_SEMI_FINISHED => 'Semi-finished',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('inventory_items.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'item_id');
    }

    public function restockRequests(): HasMany
    {
        return $this->hasMany(RestockRequest::class, 'item_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(InventoryAlert::class, 'item_id');
    }

    public function isBelowRestockLevel(): bool
    {
        return (float) $this->current_stock <= (float) $this->restock_level
            && (float) $this->restock_level > 0;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->current_stock <= 0;
    }

    /**
     * Recalculate weighted average cost after a restock.
     * Does NOT save — caller must update the model.
     */
    public function recalculateAvgCost(float $qtyIn, float $unitCost): float
    {
        $currentQty   = (float) $this->current_stock;
        $currentValue = $currentQty * (float) $this->avg_cost;
        $newTotal     = $currentQty + $qtyIn;

        if ($newTotal <= 0) {
            return $unitCost;
        }

        return round(($currentValue + ($qtyIn * $unitCost)) / $newTotal, 4);
    }
}
