<?php

namespace App\Providers;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\RestockRequest;
use App\Models\SalesOrder;
use App\Observers\InventoryItemObserver;
use App\Policies\InventoryCategoryPolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\InventoryUnitPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\RestockRequestPolicy;
use App\Policies\SalesOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);

        // Observers
        InventoryItem::observe(InventoryItemObserver::class);

        // Policies
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(InventoryCategory::class, InventoryCategoryPolicy::class);
        Gate::policy(InventoryItem::class, InventoryItemPolicy::class);
        Gate::policy(InventoryUnit::class, InventoryUnitPolicy::class);
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
        Gate::policy(RestockRequest::class, RestockRequestPolicy::class);

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
