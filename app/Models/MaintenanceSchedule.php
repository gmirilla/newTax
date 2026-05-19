<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceSchedule extends Model
{
    protected $fillable = [
        'tenant_id', 'asset_id', 'name', 'maintenance_type',
        'frequency_type', 'frequency_days', 'next_due_date',
        'estimated_hours', 'checklist', 'assigned_technician_id',
        'is_active', 'last_generated_at', 'created_by',
    ];

    protected $casts = [
        'next_due_date'      => 'date',
        'checklist'          => 'array',
        'is_active'          => 'boolean',
        'last_generated_at'  => 'datetime',
        'estimated_hours'    => 'decimal:2',
    ];

    public const FREQUENCY_DAILY    = 'daily';
    public const FREQUENCY_WEEKLY   = 'weekly';
    public const FREQUENCY_MONTHLY  = 'monthly';
    public const FREQUENCY_CUSTOM   = 'custom_interval';

    public const FREQUENCY_DAYS = [
        self::FREQUENCY_DAILY   => 1,
        self::FREQUENCY_WEEKLY  => 7,
        self::FREQUENCY_MONTHLY => 30,
    ];

    public const MAINTENANCE_TYPES = [
        'general'     => 'General',
        'lubrication' => 'Lubrication',
        'inspection'  => 'Inspection',
        'calibration' => 'Calibration',
        'overhaul'    => 'Overhaul',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('maintenance_schedules.tenant_id', app('currentTenant')->id);
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

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class, 'schedule_id');
    }

    public function isOverdue(): bool
    {
        return $this->next_due_date->isPast();
    }

    public function isDueToday(): bool
    {
        return $this->next_due_date->isToday();
    }

    /** Advance next_due_date by one interval. Does NOT save. */
    public function advanceNextDueDate(): static
    {
        $days = $this->frequency_type === self::FREQUENCY_CUSTOM
            ? $this->frequency_days
            : (self::FREQUENCY_DAYS[$this->frequency_type] ?? 30);

        $this->next_due_date = $this->next_due_date->addDays($days);
        return $this;
    }
}
