<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'tenant_id', 'plan_id', 'paystack_reference',
        'amount', 'currency', 'type', 'status',
        'paid_at', 'metadata',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'paid_at'  => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'upgrade_proration' => 'Upgrade (pro-rated)',
            'subscription'      => 'Subscription',
            'manual'            => 'Manual',
            default             => ucfirst($this->type),
        };
    }
}
