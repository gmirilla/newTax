<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Policies\InvoicePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singletons for stateless tax services
        $this->app->singleton(\App\Services\VatService::class);
        $this->app->singleton(\App\Services\WhtService::class);
        $this->app->singleton(\App\Services\CitService::class);
        $this->app->singleton(\App\Services\PayeService::class);
        $this->app->singleton(\App\Services\BookkeepingService::class);
    }

    public function boot(): void
    {
        // Policies
        Gate::policy(Invoice::class, InvoicePolicy::class);

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
