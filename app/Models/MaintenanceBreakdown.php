<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceBreakdown extends Model
{
    protected $fillable = [
        'tenant_id', 'breakdown_number', 'asset_id', 'work_order_id',
        'reported_by', 'issue_description', 'severity',
        'downtime_start', 'downtime_end', 'downtime_hours',
        'root_cause', 'corrective_action', 'status',
    ];

    protected $casts = [
        'downtime_start' => 'datetime',
        'downtime_end'   => 'datetime',
        'downtime_hours' => 'decimal:2',
    ];

    public const STATUS_OPEN        = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED    = 'resolved';
    public const STATUS_CLOSED      = 'closed';

    public const SEVERITY_LOW      = 'low';
    public const SEVERITY_MEDIUM   = 'medium';
    public const SEVERITY_HIGH     = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_COLORS = [
        self::SEVERITY_LOW      => 'gray',
        self::SEVERITY_MEDIUM   => 'yellow',
        self::SEVERITY_HIGH     => 'orange',
        self::SEVERITY_CRITICAL => 'red',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('maintenance_breakdowns.tenant_id', app('currentTenant')->id);
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

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(MaintenanceWorkOrder::class, 'work_order_id');
    }

    /** Compute downtime hours from start/end without saving. */
    public function calculateDowntimeHours(): float
    {
        $end = $this->downtime_end ?? now();
        return round($this->downtime_start->diffInMinutes($end) / 60, 2);
    }

    public function isResolvable(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }
}
