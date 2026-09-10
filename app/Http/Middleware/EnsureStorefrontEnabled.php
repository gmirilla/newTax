<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStorefrontEnabled
{
    /**
     * Guards the public storefront route group. Unlike RequiresPlan (which
     * checks the authenticated user's tenant), this checks the route-bound
     * {tenant} — these routes have no authenticated user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if (!$tenant instanceof Tenant
            || !$tenant->is_active
            || !$tenant->planAllows('storefront')
            || !optional($tenant->storefront)->is_enabled
        ) {
            abort(404);
        }

        return $next($request);
    }
}
