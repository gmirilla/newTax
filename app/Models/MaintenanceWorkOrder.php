<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaintenanceWorkOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'work_order_number', 'source_type', 'asset_id',
        'schedule_id', 'breakdown_id', 'title', 'description',
        'priority', 'assigned_to', 'scheduled_date',
        'started_at', 'completed_at', 'estimated_hours', 'actual_hours',
        'remarks', 'status', 'closed_at', 'created_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
        'closed_at'      => 'datetime',
        'estimated_hours'=> 'decimal:2',
        'actual_hours'   => 'decimal:2',
    ];

    public const SOURCE_PREVENTIVE = 'preventive';
    public const SOURCE_CORRECTIVE = 'corrective';

    public const PRIORITY_LOW      = 'low';
    public const PRIORITY_MEDIUM   = 'medium';
    public const PRIORITY_HIGH     = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public const PRIORITY_COLORS = [
        self::PRIORITY_LOW      => 'gray',
        self::PRIORITY_MEDIUM   => 'blue',
        self::PRIORITY_HIGH     => 'yellow',
        self::PRIORITY_CRITICAL => 'red',
    ];

    public const STATUS_OPEN              = 'open';
    public const STATUS_ASSIGNED          = 'assigned';
    public const STATUS_IN_PROGRESS       = 'in_progress';
    public const STATUS_WAITING_FOR_PARTS = 'waiting_for_parts';
    public const STATUS_COMPLETED         = 'completed';
    public const STATUS_CLOSED            = 'closed';

    public const STATUS_COLORS = [
        self::STATUS_OPEN              => 'gray',
        self::STATUS_ASSIGNED          => 'blue',
        self::STATUS_IN_PROGRESS       => 'yellow',
        self::STATUS_WAITING_FOR_PARTS => 'orange',
        self::STATUS_COMPLETED         => 'green',
        self::STATUS_CLOSED            => 'slate',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('maintenance_work_orders.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(MaintenanceAsset::class, 'asset_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'schedule_id');
    }

    public function breakdown(): BelongsTo
    {
        return $this->belongsTo(MaintenanceBreakdown::class, 'breakdown_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrderPart::class, 'work_order_id');
    }

    public function laborLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLaborLog::class, 'work_order_id');
    }

    public function cost(): HasOne
    {
        return $this->hasOne(MaintenanceCost::class, 'work_order_id');
    }

    public function canStart(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_ASSIGNED]);
    }

    public function canComplete(): bool
    {
        return in_array($this->status, [self::STATUS_IN_PROGRESS, self::STATUS_WAITING_FOR_PARTS]);
    }

    public function canClose(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function totalPartsCost(): float
    {
        return (float) $this->parts()->sum('subtotal');
    }

    public function totalLaborCost(): float
    {
        return (float) $this->laborLogs()->sum('labor_cost');
    }
}
