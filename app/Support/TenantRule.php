<?php

namespace App\Support;

use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rule;

/**
 * Validation rules that confine a referenced row to the current tenant.
 *
 * Plain `exists:table,id` only checks the row exists in ANY tenant, which lets a
 * user reference (and in some flows mutate) another company's data by guessing ids.
 */
class TenantRule
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('tenant_id', self::tenantId());
    }

    private static function tenantId(): int
    {
        if (app()->bound('currentTenant')) {
            return (int) app('currentTenant')->id;
        }

        // Fail closed: an id no real tenant has, so nothing validates.
        return (int) (auth()->user()?->tenant_id ?? 0);
    }
}
