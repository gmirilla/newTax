<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password',
        'role', 'is_active', 'is_superadmin', 'phone', 'module_access',
        'is_partner_proxy', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'is_superadmin'     => 'boolean',
            'tenant_id'         => 'integer',
            'module_access'     => 'array',
            'is_partner_proxy'  => 'boolean',
        ];
    }

    public const ROLE_ADMIN       = 'admin';
    public const ROLE_ACCOUNTANT  = 'accountant';
    public const ROLE_STAFF       = 'staff';

    public const MODULE_LIST = [
        'invoices'          => 'Invoices & Quotes',
        'expenses'          => 'Expenses',
        'inventory'         => 'Inventory',
        'inventory_reports' => 'Inventory Reports',
        'manufacturing'     => 'Manufacturing',
        'maintenance'       => 'Maintenance',
        'storefront'        => 'Online Storefront',
        'payroll'           => 'Payroll',
        'reports'           => 'Reports',
        'advanced_reports'  => 'Advanced Reports (Ledger, Balance Sheet, Trial Balance)',
        'api_access'        => 'API Access',
        'bank_accounts'     => 'Bank Accounts',
        'chart_of_accounts' => 'Chart of Accounts',
    ];

    public static function moduleDefaults(string $role): array
    {
        $on  = array_fill_keys(array_keys(self::MODULE_LIST), true);
        $off = array_fill_keys(array_keys(self::MODULE_LIST), false);

        return match($role) {
            self::ROLE_ADMIN, self::ROLE_ACCOUNTANT => $on,
            default => $off,
        };
    }

    public function canAccess(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $access = $this->module_access;

        if ($access === null) {
            return self::moduleDefaults($this->role)[$module] ?? false;
        }

        return (bool) ($access[$module] ?? self::moduleDefaults($this->role)[$module] ?? false);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAccountant(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_ACCOUNTANT]);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
