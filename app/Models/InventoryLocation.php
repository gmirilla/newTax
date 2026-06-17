<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLocation extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'code', 'address', 'city', 'state',
        'contact_name', 'contact_phone', 'is_default', 'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('inventory_locations.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'location_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'location_id');
    }

    public function restockRequests(): HasMany
    {
        return $this->hasMany(RestockRequest::class, 'location_id');
    }

    /** Stock quantity for a specific item at this location, computed from movements. */
    public function stockForItem(int $itemId): float
    {
        return (float) StockMovement::where('location_id', $this->id)
            ->where('item_id', $itemId)
            ->selectRaw("
                COALESCE(SUM(CASE
                    WHEN type IN ('restock','opening','adjustment_in','transfer_in','production_in') THEN quantity
                    ELSE -quantity
                END), 0) AS balance
            ")
            ->value('balance');
    }
}
