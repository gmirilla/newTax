<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenancyService
{
    public function __construct(
        private readonly BookkeepingService $bookkeepingService
    ) {}

    /**
     * Register a new tenant (company) with an admin user.
     * Provisions the chart of accounts automatically.
     */
    public function registerTenant(array $tenantData, array $adminData): array
    {
        return DB::transaction(function () use ($tenantData, $adminData) {
            $tenant = Tenant::create([
                'name'            => $tenantData['name'],
                'slug'            => Str::slug($tenantData['name']) . '-' . Str::random(4),
                'email'           => $tenantData['email'],
                'phone'           => $tenantData['phone'] ?? null,
                'address'         => $tenantData['address'] ?? null,
                'city'            => $tenantData['city'] ?? null,
                'state'           => $tenantData['state'] ?? null,
                'tin'             => $tenantData['tin'] ?? null,
                'rc_number'       => $tenantData['rc_number'] ?? null,
                'business_type'   => $tenantData['business_type'] ?? 'limited_liability',
                'annual_turnover' => $tenantData['annual_turnover'] ?? 0,
                'currency'        => 'NGN',
                'subscription_plan' => 'free',
                'subscription_status' => 'active',
            ]);

            // Determine tax category based on turnover
            $tenant->updateTaxCategory();

            // Assign Growth trial plan if one exists with trial_days > 0
            $trialPlan = Plan::where('is_active', true)
                ->where('trial_days', '>', 0)
                ->orderBy('sort_order')
                ->first();

            if ($trialPlan) {
                $tenant->assignPlan(
                    $trialPlan,
                    'trialing',
                    null,
                    now()->addDays($trialPlan->trial_days)
                );
            }

            // Create admin user
            $admin = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $adminData['name'],
                'email'     => $adminData['email'],
                'password'  => bcrypt($adminData['password']),
                'role'      => User::ROLE_ADMIN,
                'is_active' => true,
            ]);

            // Provision default chart of accounts
            $this->bookkeepingService->provisionDefaultAccounts($tenant);

            // Provision default units of measure
            $this->provisionDefaultUnits($tenant);

            return ['tenant' => $tenant, 'admin' => $admin];
        });
    }

    /**
     * Get or set the current tenant from the session/request.
     * In a single-DB multi-tenant setup, tenant is derived from authenticated user.
     */
    public function setCurrentTenant(User $user): void
    {
        if ($user->tenant_id) {
            app()->instance('currentTenant', $user->tenant);
        }
    }

    /**
     * Get the current tenant (set by middleware).
     */
    public function getCurrentTenant(): ?Tenant
    {
        return app()->has('currentTenant') ? app('currentTenant') : null;
    }

    private function provisionDefaultUnits(Tenant $tenant): void
    {
        $now = now();
        $defaults = ['piece', 'pair', 'kg', 'g', 'litre', 'ml', 'carton', 'bag', 'box', 'roll', 'metre', 'set'];

        foreach ($defaults as $name) {
            DB::table('inventory_units')->insertOrIgnore([
                'tenant_id'  => $tenant->id,
                'name'       => $name,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
