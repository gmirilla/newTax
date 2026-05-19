<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaintenanceAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'asset_code', 'asset_name', 'category_id',
        'serial_number', 'manufacturer', 'model',
        'purchase_date', 'warranty_expiry', 'location',
        'assigned_operator_id', 'related_item_id',
        'maintenance_interval_days', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'purchase_date'   => 'date',
        'warranty_expiry' => 'date',
    ];

    public const STATUS_ACTIVE            = 'active';
    public const STATUS_RUNNING           = 'running';
    public const STATUS_UNDER_MAINTENANCE = 'under_maintenance';
    public const STATUS_BREAKDOWN         = 'breakdown';
    public const STATUS_RETIRED           = 'retired';

    public const STATUSES = [
        self::STATUS_ACTIVE            => 'Active',
        self::STATUS_RUNNING           => 'Running',
        self::STATUS_UNDER_MAINTENANCE => 'Under Maintenance',
        self::STATUS_BREAKDOWN         => 'Breakdown',
        self::STATUS_RETIRED           => 'Retired',
    ];

    public const STATUS_COLORS = [
        self::STATUS_ACTIVE            => 'green',
        self::STATUS_RUNNING           => 'blue',
        self::STATUS_UNDER_MAINTENANCE => 'yellow',
        self::STATUS_BREAKDOWN         => 'red',
        self::STATUS_RETIRED           => 'gray',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('maintenance_assets.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaintenanceAssetCategory::class, 'category_id');
    }

    public function assignedOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_operator_id');
    }

    public function relatedItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'related_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class, 'asset_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class, 'asset_id');
    }

    public function breakdowns(): HasMany
    {
        return $this->hasMany(MaintenanceBreakdown::class, 'asset_id');
    }

    public function cost(): HasOne
    {
        return $this->hasOne(MaintenanceCost::class, 'asset_id');
    }

    public function isOperational(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_RUNNING]);
    }

    public function isAvailableForProduction(): bool
    {
        return $this->status !== self::STATUS_BREAKDOWN
            && $this->status !== self::STATUS_RETIRED;
    }

    public function totalMaintenanceCost(): float
    {
        return (float) $this->workOrders()
            ->join('maintenance_costs', 'maintenance_costs.work_order_id', '=', 'maintenance_work_orders.id')
            ->sum('maintenance_costs.total_cost');
    }
}
