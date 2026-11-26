<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCreditLedger extends Model
{
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT  = 'debit';

    protected $table = 'referral_credit_ledger';

    protected $fillable = [
        'tenant_id',
        'referral_id',
        'subscription_payment_id',
        'type',
        'amount_ngn',
        'description',
        'reversed_at',
    ];

    protected $casts = [
        'amount_ngn'  => 'decimal:2',
        'reversed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function subscriptionPayment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class);
    }
}
