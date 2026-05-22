<?php

namespace App\Providers;

use App\Models\BankAccount;
use App\Models\Bom;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\MaintenanceAsset;
use App\Models\MaintenanceBreakdown;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\ProductionOrder;
use App\Models\RestockRequest;
use App\Models\SalesOrder;
use App\Observers\InventoryItemObserver;
use App\Policies\BankAccountPolicy;
use App\Policies\BomPolicy;
use App\Policies\InventoryCategoryPolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\InventoryUnitPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\MaintenanceAssetPolicy;
use App\Policies\MaintenanceBreakdownPolicy;
use App\Policies\MaintenanceWorkOrderPolicy;
use App\Policies\ProductionOrderPolicy;
use App\Policies\RestockRequestPolicy;
use App\Policies\SalesOrderPolicy;
use App\View\Composers\SystemNotificationComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        Gate::policy(BankAccount::class, BankAccountPolicy::class);
        Gate::policy(Bom::class, BomPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(ProductionOrder::class, ProductionOrderPolicy::class);
        Gate::policy(InventoryCategory::class, InventoryCategoryPolicy::class);
        Gate::policy(InventoryItem::class, InventoryItemPolicy::class);
        Gate::policy(InventoryUnit::class, InventoryUnitPolicy::class);
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
        Gate::policy(RestockRequest::class, RestockRequestPolicy::class);
        Gate::policy(MaintenanceAsset::class, MaintenanceAssetPolicy::class);
        Gate::policy(MaintenanceWorkOrder::class, MaintenanceWorkOrderPolicy::class);
        Gate::policy(MaintenanceBreakdown::class, MaintenanceBreakdownPolicy::class);
        Gate::policy(MaintenanceSchedule::class, MaintenanceWorkOrderPolicy::class);

        // View composers
        View::composer('layouts.app', SystemNotificationComposer::class);

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
