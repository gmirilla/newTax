<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTourSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_tour_markup_for_admin(): void
    {
        $plan = Plan::create([
            'name' => 'Business', 'slug' => 'business', 'price_monthly' => 0,
            'limits' => [], 'is_active' => true, 'is_public' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Acme Ltd', 'slug' => 'acme-ltd', 'email' => 'acme@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 0, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $admin = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => 'admin@acme-ltd.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-tour="nav-dashboard"', false);
        $response->assertSee('startAppTour', false);
    }

    public function test_dashboard_renders_for_staff_without_admin_only_tour_targets(): void
    {
        $plan = Plan::create([
            'name' => 'Business2', 'slug' => 'business2', 'price_monthly' => 0,
            'limits' => [], 'is_active' => true, 'is_public' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Beta Ltd', 'slug' => 'beta-ltd', 'email' => 'beta@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 0, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $staff = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Staff', 'email' => 'staff@beta-ltd.ng',
            'password' => bcrypt('password'), 'role' => 'staff', 'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertRedirect(route('staff.dashboard'));

        $response = $this->actingAs($staff)->get(route('staff.dashboard'));
        $response->assertOk();
        $response->assertSee('data-tour="nav-dashboard"', false);
        $response->assertDontSee('data-tour="nav-settings"', false);
    }
}
