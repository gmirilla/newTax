<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLimitTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(?int $customerLimit): Tenant
    {
        $plan = Plan::create([
            'slug' => 'test-plan-' . uniqid(), 'name' => 'Test', 'price_monthly' => 0,
            'is_active' => true, 'limits' => ['customers' => $customerLimit],
        ]);

        return Tenant::create([
            'name' => 'Cust Co', 'slug' => 'cust-co-' . uniqid(), 'email' => 'cust@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);
    }

    private function makeAdmin(Tenant $tenant): User
    {
        return User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => 'admin-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);
    }

    public function test_quick_create_is_blocked_once_the_customer_limit_is_reached(): void
    {
        $tenant = $this->makeTenant(1);
        $admin  = $this->makeAdmin($tenant);

        Customer::create(['tenant_id' => $tenant->id, 'name' => 'Existing Co', 'is_company' => true, 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->postJson(route('customers.quick-store'), ['name' => 'New Co']);

        $response->assertStatus(422);
        $this->assertSame(1, Customer::where('tenant_id', $tenant->id)->count());
    }

    public function test_quick_create_succeeds_under_the_limit(): void
    {
        $tenant = $this->makeTenant(5);
        $admin  = $this->makeAdmin($tenant);

        $response = $this->actingAs($admin)
            ->postJson(route('customers.quick-store'), ['name' => 'New Co']);

        $response->assertStatus(201);
        $this->assertSame(1, Customer::where('tenant_id', $tenant->id)->count());
    }

    public function test_unlimited_plan_is_never_blocked(): void
    {
        $tenant = $this->makeTenant(null);
        $admin  = $this->makeAdmin($tenant);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($admin)->postJson(route('customers.quick-store'), ['name' => "Co {$i}"]);
        }

        $this->assertSame(3, Customer::where('tenant_id', $tenant->id)->count());
    }
}
