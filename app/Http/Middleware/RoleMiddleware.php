<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Enforce role-based access control.
     * Usage in routes: ->middleware('role:admin') or ->middleware('role:admin,accountant')
     */
    /**
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,accountant')
     * Multiple roles = user must have ONE of them.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Impersonating superadmins bypass all tenant role checks
        if (session()->has('superadmin_id')) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden. Insufficient permissions.'], 403);
            }

            // Staff hitting admin/accountant routes land on their own dashboard
            if ($user?->role === 'staff') {
                return redirect()->route('staff.dashboard')
                    ->with('error', 'You do not have permission to access that page.');
            }

            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}
