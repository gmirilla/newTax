<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceCost extends Model
{
    protected $fillable = [
        'tenant_id', 'work_order_id', 'asset_id',
        'labor_cost', 'parts_cost', 'external_cost', 'total_cost',
        'transaction_id', 'posted_at',
    ];

    protected $casts = [
        'labor_cost'    => 'decimal:2',
        'parts_cost'    => 'decimal:2',
        'external_cost' => 'decimal:2',
        'total_cost'    => 'decimal:2',
        'posted_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('maintenance_costs.tenant_id', app('currentTenant')->id);
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

    public function asset(): BelongsTo
    {
        return $this->belongsTo(MaintenanceAsset::class, 'asset_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isPosted(): bool
    {
        return $this->posted_at !== null;
    }
}
