<?php

use App\Http\Middleware\AuditLogMiddleware;
use App\Http\Middleware\RequiresPlan;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__ . '/../routes/web.php',
        api:      __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register named route middleware aliases
        $middleware->alias([
            'tenant'      => TenantMiddleware::class,
            'role'        => RoleMiddleware::class,
            'audit'       => AuditLogMiddleware::class,
            'superadmin'  => SuperAdminMiddleware::class,
            'plan'        => RequiresPlan::class,
        ]);

        // Apply CSRF protection to web routes
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'webhooks/paystack',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Prevent sensitive FIRS credential values from appearing in exception
        // reports, Horizon dashboards, or log storage.
        $exceptions->dontFlash([
            'api_key',
            'secret_key',
            'service_id',
            'public_key',
            'certificate',
        ]);
    })
    ->create();
