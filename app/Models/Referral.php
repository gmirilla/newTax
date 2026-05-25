<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_REWARDED  = 'rewarded';

    public const REWARD_NGN     = 2000;   // credit earned per qualified referral
    public const MAX_CREDIT_NGN = 20000;  // maximum referral_credit_ngn balance a tenant can hold

    protected $fillable = [
        'referrer_tenant_id',
        'referee_tenant_id',
        'status',
        'reward_ngn',
        'qualified_at',
        'rewarded_at',
    ];

    protected $casts = [
        'reward_ngn'   => 'decimal:2',
        'qualified_at' => 'datetime',
        'rewarded_at'  => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referrer_tenant_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referee_tenant_id');
    }

    public function creditLedgerEntries(): HasMany
    {
        return $this->hasMany(ReferralCreditLedger::class);
    }
}
