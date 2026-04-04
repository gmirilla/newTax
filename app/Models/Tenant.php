<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address', 'city', 'state',
        'tin', 'rc_number', 'business_type', 'tax_category',
        'annual_turnover', 'vat_registered', 'vat_number', 'logo',
        'currency', 'subscription_plan', 'subscription_expires_at', 'is_active',
        'is_professional_firm',
    ];

    protected $casts = [
        'annual_turnover'        => 'decimal:2',
        'vat_registered'         => 'boolean',
        'is_active'              => 'boolean',
        'is_professional_firm'   => 'boolean',
        'subscription_expires_at'=> 'datetime',
    ];

    // Nigerian tax thresholds — 2026 (Finance Act 2025)
    public const VAT_THRESHOLD  = 25_000_000;  // ₦25M — VAT registration mandatory
    public const CIT_SMALL_MAX  = 50_000_000;  // ₦50M — 0% CIT (2026 threshold)
    public const CIT_SMALL_RATE = 0;
    public const CIT_LARGE_RATE = 30;          // >₦50M — 30% CIT

    // --- Relationships ---

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
}
