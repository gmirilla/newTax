<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresPlan
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = $request->user()?->tenant;

        if (!$tenant) {
            return $this->deny($request, $feature, 'No tenant account found.');
        }

        // Superadmins impersonating a tenant bypass plan checks
        if (session()->has('superadmin_id')) {
            return $next($request);
        }

        if (!$tenant->planAllows($feature)) {
            return $this->deny($request, $feature);
        }

        return $next($request);
    }

    private function deny(Request $request, string $feature, string $message = ''): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message ?: "Your current plan does not include access to this feature.",
                'upgrade' => true,
                'feature' => $feature,
            ], 403);
        }

        return redirect()->route('billing')
            ->with('upgrade_feature', $feature)
            ->with('error', $message ?: "This feature requires a higher plan. Please upgrade to continue.");
    }
}
