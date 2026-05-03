<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SubscriptionPayment;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address', 'city', 'state',
        'tin', 'rc_number', 'business_type', 'tax_category',
        'annual_turnover', 'vat_registered', 'vat_number', 'logo',
        'currency', 'subscription_plan', 'subscription_expires_at', 'is_active',
        'is_professional_firm',
        'plan_id', 'next_plan_id', 'subscription_status', 'billing_cycle', 'trial_ends_at',
        'paystack_customer_id', 'paystack_subscription_code',
    ];

    protected $casts = [
        'annual_turnover'        => 'decimal:2',
        'vat_registered'         => 'boolean',
        'is_active'              => 'boolean',
        'is_professional_firm'   => 'boolean',
        'subscription_expires_at'=> 'datetime',
        'trial_ends_at'          => 'datetime',
    ];

    // Nigerian tax thresholds — 2026 (Finance Act 2025)
    public const VAT_THRESHOLD  = 25_000_000;  // ₦25M — VAT registration mandatory
    public const CIT_SMALL_MAX  = 50_000_000;  // ₦50M — 0% CIT (2026 threshold)
    public const CIT_SMALL_RATE = 0;
    public const CIT_LARGE_RATE = 30;          // >₦50M — 30% CIT

    // --- Relationships ---

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function nextPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'next_plan_id');
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function hasPendingPlanChange(): bool
    {
        return $this->next_plan_id !== null;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function vatReturns(): HasMany
    {
        return $this->hasMany(VatReturn::class);
    }

    public function whtRecords(): HasMany
    {
        return $this->hasMany(WhtRecord::class);
    }

    public function citRecords(): HasMany
    {
        return $this->hasMany(CitRecord::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // --- Tax helper methods ---

    public function getCitRate(): float
    {
        return match ($this->tax_category) {
            'small'  => self::CIT_SMALL_RATE,
            'large'  => self::CIT_LARGE_RATE,
            'medium' => self::CIT_LARGE_RATE, // legacy — treated as large under 2026 rules
            default  => self::CIT_SMALL_RATE,
        };
    }

    public function isVatRegistered(): bool
    {
        return $this->vat_registered || $this->annual_turnover >= self::VAT_THRESHOLD;
    }

    public function updateTaxCategory(): void
    {
        $turnover = $this->annual_turnover;

        // 2026: professional firms are always 'large' regardless of turnover
        if ($this->is_professional_firm) {
            $this->tax_category = 'large';
        } else {
            $this->tax_category = $turnover <= self::CIT_SMALL_MAX ? 'small' : 'large';
        }

        if ($this->annual_turnover >= self::VAT_THRESHOLD) {
            $this->vat_registered = true;
        }

        $this->save();
    }

    // --- Subscription helpers ---

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trialing'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function trialExpired(): bool
    {
        return $this->subscription_status === 'trialing'
            && $this->trial_ends_at
            && $this->trial_ends_at->isPast();
    }

    public function isInGracePeriod(): bool
    {
        if ($this->isOnTrial()) return false;
        if (!in_array($this->subscription_status, ['active', 'cancelled', 'suspended'])) return false;
        if (!$this->subscription_expires_at) return false;

        return $this->subscription_expires_at->isPast()
            && $this->subscription_expires_at->copy()->addDays(7)->isFuture();
    }

    public function graceDaysLeft(): int
    {
        if (!$this->isInGracePeriod()) return 0;
        return max(0, (int) now()->diffInDays($this->subscription_expires_at->copy()->addDays(7)));
    }

    public function subscriptionActive(): bool
    {
        if ($this->isOnTrial()) return true;

        // cancelled = won't renew but access continues until expiry
        // suspended = payment failed; grace period: 7 days after expiry before enforcement kicks in
        if (in_array($this->subscription_status, ['active', 'cancelled', 'suspended'])) {
            if (!$this->subscription_expires_at) return true; // perpetual
            return $this->subscription_expires_at->copy()->addDays(7)->isFuture();
        }

        return false;
    }

    /** Check a feature flag against the current plan; denies when subscription is inactive. */
    public function planAllows(string $feature): bool
    {
        if (!$this->plan) return false;
        if (!$this->subscriptionActive()) return false;
        return $this->plan->allows($feature);
    }

    /** Check a numeric limit (null = unlimited). Falls back to Free defaults when subscription is inactive. */
    public function withinLimit(string $resource): bool
    {
        if (!$this->plan) return false;

        $limit = $this->subscriptionActive()
            ? $this->plan->limit($resource)
            : (int) (Plan::LIMIT_DEFAULTS[$resource] ?? 0);

        if ($limit === null) return true;
        if ($limit <= 0)     return false;

        // Cache counts for 5 minutes to avoid a DB query on every request
        $month    = now()->format('Y-m');
        $cacheKey = "tenant.{$this->id}.limit.{$resource}.{$month}";
        $current  = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, fn() => match ($resource) {
            'invoices_per_month' => $this->invoices()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'users'         => $this->users()->count(),
            'payroll_staff' => \App\Models\Employee::where('tenant_id', $this->id)->where('is_active', true)->count(),
            'customers'     => $this->customers()->count(),
            default         => 0,
        });

        return $current < $limit;
    }

    /** Invalidate withinLimit cache for a resource after a record is created. */
    public function invalidateLimitCache(string $resource): void
    {
        \Illuminate\Support\Facades\Cache::forget("tenant.{$this->id}.limit.{$resource}." . now()->format('Y-m'));
    }

    /** Assign a plan and sync the legacy subscription_plan slug. */
    public function assignPlan(Plan $plan, ?string $status = 'active', ?\Carbon\Carbon $expiresAt = null, ?\Carbon\Carbon $trialEndsAt = null): void
    {
        $this->update([
            'plan_id'                 => $plan->id,
            'subscription_plan'       => $plan->slug,
            'subscription_status'     => $status,
            'subscription_expires_at' => $expiresAt,
            'trial_ends_at'           => $trialEndsAt,
        ]);
    }
}
