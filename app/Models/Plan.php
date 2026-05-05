<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description',
        'price_monthly', 'price_yearly', 'trial_days',
        'paystack_plan_code', 'paystack_plan_code_yearly',
        'limits', 'is_active', 'is_public', 'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly'  => 'decimal:2',
        'trial_days'    => 'integer',
        'sort_order'    => 'integer',
        'limits'        => 'array',
        'is_active'     => 'boolean',
        'is_public'     => 'boolean',
    ];

    // Default limit structure — used when a key is missing from the stored JSON
    public const LIMIT_DEFAULTS = [
        'invoices_per_month' => 5,
        'users'              => 1,
        'payroll_staff'      => 0,
        'customers'          => 50,
        'payroll'            => false,
        'firs'               => false,
        'advanced_reports'   => false,
        'api_access'         => false,
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /** Get a specific limit, falling back to the default. */
    public function limit(string $key): mixed
    {
        return $this->limits[$key] ?? self::LIMIT_DEFAULTS[$key] ?? null;
    }

    /** Whether a feature flag is enabled on this plan. */
    public function allows(string $feature): bool
    {
        return (bool) $this->limit($feature);
    }

    /** Human-readable price string. */
    public function priceLabel(): string
    {
        if ($this->price_monthly == 0) {
            return 'Free';
        }
        return '₦' . number_format($this->price_monthly, 0) . '/mo';
    }

    /** Effective monthly cost when billed annually (price_yearly / 12). */
    public function yearlyMonthlyEquivalent(): float
    {
        if (!$this->price_yearly) return (float) $this->price_monthly;
        return round($this->price_yearly / 12, 2);
    }

    /** Percentage saved by choosing annual over 12 months of monthly billing. */
    public function yearlyDiscount(): int
    {
        if (!$this->price_yearly || !$this->price_monthly) return 0;
        $fullYear = $this->price_monthly * 12;
        return (int) round((($fullYear - $this->price_yearly) / $fullYear) * 100);
    }

    /** Price for the given billing cycle. */
    public function priceForCycle(string $cycle): float
    {
        return $cycle === 'yearly' && $this->price_yearly
            ? (float) $this->price_yearly
            : (float) $this->price_monthly;
    }

    /** Paystack plan code for the given billing cycle. */
    public function planCodeForCycle(string $cycle): ?string
    {
        return $cycle === 'yearly'
            ? ($this->paystack_plan_code_yearly ?: $this->paystack_plan_code)
            : $this->paystack_plan_code;
    }

    public static function getDefault(): ?self
    {
        return static::where('slug', 'free')->where('is_active', true)->first();
    }
}
