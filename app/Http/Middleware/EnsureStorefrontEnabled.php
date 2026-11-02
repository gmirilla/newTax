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

        // The {tenant:slug} binding already 404s for a genuinely unknown slug —
        // by the time we get here, the tenant exists but access is currently denied.
        if (!$tenant instanceof Tenant) {
            abort(404);
        }

        if (!$tenant->is_active
            || !$tenant->planAllows('storefront')
            || !optional($tenant->storefront)->is_enabled
        ) {
            return response()->view('storefront.unavailable', compact('tenant'), 503);
        }

        return $next($request);
    }
}
