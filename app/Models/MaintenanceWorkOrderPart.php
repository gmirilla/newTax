<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceWorkOrderPart extends Model
{
    protected $fillable = [
        'tenant_id', 'work_order_id', 'inventory_item_id',
        'quantity_requested', 'quantity_used', 'unit_cost', 'subtotal',
        'notes', 'created_by',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
        'quantity_used'      => 'decimal:3',
        'unit_cost'          => 'decimal:2',
        'subtotal'           => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('maintenance_work_order_parts.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(MaintenanceWorkOrder::class, 'work_order_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
