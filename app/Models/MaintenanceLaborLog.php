<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLaborLog extends Model
{
    protected $fillable = [
        'tenant_id', 'work_order_id', 'user_id',
        'work_date', 'hours_worked', 'hourly_rate', 'labor_cost', 'description',
    ];

    protected $casts = [
        'work_date'    => 'date',
        'hours_worked' => 'decimal:2',
        'hourly_rate'  => 'decimal:2',
        'labor_cost'   => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('maintenance_labor_logs.tenant_id', app('currentTenant')->id);
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

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
