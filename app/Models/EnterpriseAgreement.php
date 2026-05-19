<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnterpriseAgreement extends Model
{
    protected $fillable = [
        'tenant_id', 'plan_id', 'negotiated_price', 'billing_cycle',
        'payment_terms_days', 'start_date', 'end_date', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'negotiated_price'   => 'decimal:2',
        'payment_terms_days' => 'integer',
        'start_date'         => 'date',
        'end_date'           => 'date',
    ];

    const STATUS_ACTIVE     = 'active';
    const STATUS_EXPIRED    = 'expired';
    const STATUS_TERMINATED = 'terminated';

    const CYCLE_MONTHLY   = 'monthly';
    const CYCLE_QUARTERLY = 'quarterly';
    const CYCLE_ANNUALLY  = 'annually';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function platformInvoices(): HasMany
    {
        return $this->hasMany(PlatformInvoice::class, 'agreement_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
