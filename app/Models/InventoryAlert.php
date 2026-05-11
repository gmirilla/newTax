<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAlert extends Model
{
    protected $fillable = [
        'tenant_id', 'item_id', 'type', 'stock_at_alert', 'notified_at', 'seen_at',
    ];

    protected $casts = [
        'stock_at_alert' => 'decimal:3',
        'notified_at'    => 'datetime',
        'seen_at'        => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('inventory_alerts.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function isSeen(): bool
    {
        return ! is_null($this->seen_at);
    }

    public function dismiss(): void
    {
        $this->update(['seen_at' => now()]);
    }
}
