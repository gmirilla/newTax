<?php

namespace App\Models;

use App\Models\RestockRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    public const STATUS_DRAFT         = 'draft';
    public const STATUS_IN_PRODUCTION = 'in_production';
    public const STATUS_COMPLETED     = 'completed';
    public const STATUS_CANCELLED     = 'cancelled';

    protected $fillable = [
        'tenant_id', 'order_number', 'bom_id', 'finished_item_id',
        'quantity_planned', 'quantity_produced', 'additional_cost',
        'status', 'started_at', 'completed_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'quantity_planned'  => 'decimal:3',
        'quantity_produced' => 'decimal:3',
        'additional_cost'   => 'decimal:2',
        'started_at'        => 'datetime',
        'completed_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('production_orders.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function finishedItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'finished_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ProductionOrderLine::class);
    }

    public function restockRequests(): HasMany
    {
        return $this->hasMany(RestockRequest::class);
    }
}
