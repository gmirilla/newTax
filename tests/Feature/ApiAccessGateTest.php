<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAccessGateTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenantAndAdmin(bool $apiAccess): array
    {
        $plan = Plan::create([
            'slug' => 'test-plan-' . uniqid(), 'name' => 'Test', 'price_monthly' => 0,
            'is_active' => true, 'limits' => ['api_access' => $apiAccess],
        ]);

        $tenant = Tenant::create([
            'name' => 'Api Co', 'slug' => 'api-co-' . uniqid(), 'email' => 'api@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $admin = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Admin', 'email' => 'admin-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);

        return [$tenant, $admin];
    }

    public function test_invoice_and_tax_api_routes_are_blocked_without_the_plan_flag(): void
    {
        [, $admin] = $this->makeTenantAndAdmin(false);
        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/invoices')
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/tax/compliance-dashboard')
            ->assertStatus(403);
    }

    public function test_invoice_and_tax_api_routes_are_allowed_with_the_plan_flag(): void
    {
        [, $admin] = $this->makeTenantAndAdmin(true);
        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/invoices')
            ->assertOk();
    }

    public function test_token_issuance_is_unaffected_by_the_plan_flag(): void
    {
        [, $admin] = $this->makeTenantAndAdmin(false);

        $this->postJson('/api/v1/auth/token', [
            'email'    => $admin->email,
            'password' => 'password',
        ])->assertOk();
    }
}
