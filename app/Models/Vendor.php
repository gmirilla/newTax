<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'phone', 'address',
        'city', 'state', 'tin', 'rc_number',
        'vendor_type', 'wht_rate', 'is_active', 'current_balance',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('currentTenant')) {
                $query->where('vendors.tenant_id', app('currentTenant')->id);
            }
        });
    }

    protected $casts = [
        'wht_rate'        => 'decimal:2',
        'is_active'       => 'boolean',
        'current_balance' => 'decimal:2',
    ];

    // Nigerian WHT rates per transaction type
    public const WHT_RATES = [
        'services_company'    => 5.0,   // Services rendered by companies
        'services_individual' => 10.0,  // Services rendered by individuals
        'contracts'           => 5.0,   // Contracts
        'rent'                => 10.0,  // Rent
        'dividends'           => 10.0,  // Dividend payments
        'interest'            => 10.0,  // Interest payments
        'royalties'           => 10.0,  // Royalties
        'technical_fees'      => 10.0,  // Technical/management fees
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function whtRecords(): HasMany
    {
        return $this->hasMany(WhtRecord::class);
    }
}
