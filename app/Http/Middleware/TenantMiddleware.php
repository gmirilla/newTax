<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Resolve the current tenant from the authenticated user and bind it
     * to the service container for use throughout the request lifecycle.
     *
     * In a single-database multi-tenant setup, all queries are automatically
     * scoped by tenant_id via the user's tenant relationship.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Superadmins have no tenant — they use their own routes
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        if (!$user->tenant_id) {
            abort(403, 'No tenant associated with this account.');
        }

        if (!$user->is_active) {
            auth()->logout();
            abort(403, 'Your account has been deactivated.');
        }

        $tenant = $user->load('tenant.plan')->tenant;

        if (!$tenant || !$tenant->is_active) {
            abort(403, 'Your company account is inactive. Please contact support.');
        }

        // Bind tenant to the container for dependency injection
        app()->instance('currentTenant', $tenant);

        // Share tenant and authenticated user with all views
        view()->share('currentTenant', $tenant);
        view()->share('currentUser', $user);

        return $next($request);
    }
}
