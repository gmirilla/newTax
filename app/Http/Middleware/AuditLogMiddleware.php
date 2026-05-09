<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /**
     * Log all mutating requests (POST, PUT, PATCH, DELETE) for NRS audit trail.
     * This provides an immutable record of all financial data changes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log write operations
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        // Skip non-tenant routes
        if (!auth()->check() || !auth()->user()->tenant_id) {
            return $response;
        }

        // Determine event type from HTTP method + route
        $event = match ($request->method()) {
            'POST'   => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default  => 'accessed',
        };

        // Detect special events
        if (str_contains($request->path(), 'payment')) {
            $event = 'payment_recorded';
        } elseif (str_contains($request->path(), 'filed')) {
            $event = 'tax_filed';
        } elseif (str_contains($request->path(), 'export')) {
            $event = 'data_exported';
        }

        try {
            AuditLog::create([
                'tenant_id'      => auth()->user()->tenant_id,
                'user_id'        => auth()->id(),
                'event'          => $event,
                'auditable_type' => 'Http\\Request',
                'auditable_id'   => 0,
                'new_values'     => $this->sanitizeInput($request->all()),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'url'            => $request->fullUrl(),
                'tags'           => $this->extractTags($request->path()),
            ]);
        } catch (\Exception $e) {
            // Never fail a request due to audit logging
            logger()->error('AuditLog failed: ' . $e->getMessage());
        }

        return $response;
    }

    private function sanitizeInput(array $input): array
    {
        // Remove sensitive fields from audit log
        $sensitive = ['password', 'password_confirmation', 'token', 'bvn'];

        foreach ($sensitive as $field) {
            if (isset($input[$field])) {
                $input[$field] = '[REDACTED]';
            }
        }

        return $input;
    }

    private function extractTags(string $path): string
    {
        $tagMap = [
            'invoice'     => 'invoice',
            'transaction' => 'transaction',
            'vat'         => 'tax,vat',
            'wht'         => 'tax,wht',
            'cit'         => 'tax,cit',
            'payroll'     => 'payroll,paye',
            'expense'     => 'expense',
            'report'      => 'report',
        ];

        foreach ($tagMap as $keyword => $tag) {
            if (str_contains($path, $keyword)) {
                return $tag;
            }
        }

        return 'general';
    }
}
