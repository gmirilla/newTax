<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Confines a model to the current tenant.
 *
 * Uses the bound `currentTenant` when present, and otherwise falls back to the
 * authenticated user's tenant. The fallback matters because Laravel resolves
 * route-model bindings (SubstituteBindings) BEFORE TenantMiddleware binds
 * `currentTenant`, so without it `{expense}` / `{transaction}` bindings would
 * resolve other tenants' rows. Superadmins (no tenant_id) and unauthenticated
 * contexts (jobs, console) are left unscoped.
 */
class CurrentTenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app()->bound('currentTenant')
            ? app('currentTenant')->id
            : auth()->user()?->tenant_id;

        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}
