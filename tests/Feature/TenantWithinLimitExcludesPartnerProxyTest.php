<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantWithinLimitExcludesPartnerProxyTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(int $userLimit): Tenant
    {
        $plan = Plan::create([
            'slug' => 'test-plan-' . uniqid(), 'name' => 'Test', 'price_monthly' => 0,
            'is_active' => true, 'limits' => ['users' => $userLimit],
        ]);

        return Tenant::create([
            'name' => 'Client Co', 'slug' => 'client-co-' . uniqid(), 'email' => 'client-' . uniqid() . '@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);
    }

    public function test_partner_proxy_users_do_not_count_against_the_users_limit(): void
    {
        $tenant = $this->makeTenant(1);

        User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Real Admin', 'email' => 'admin@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);

        // At exactly the limit (1 real user) — withinLimit should already report false for the real seat...
        $this->assertFalse($tenant->withinLimit('users'));

        // ...but adding a partner proxy user must not push the count further or be counted at all.
        User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Firm (Partner Access)', 'email' => 'proxy@partner.accounttaxng.internal',
            'password' => bcrypt('password'), 'role' => 'accountant', 'is_active' => true, 'is_partner_proxy' => true,
        ]);

        $tenant->invalidateLimitCache('users');

        // Still exactly 1 real user against a limit of 1 — same result as before the proxy existed.
        $this->assertFalse($tenant->withinLimit('users'));
    }

    public function test_within_limit_is_true_when_only_proxy_users_exist_under_the_cap(): void
    {
        $tenant = $this->makeTenant(2);

        User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Real Admin', 'email' => 'admin2@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);

        User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Firm (Partner Access)', 'email' => 'proxy2@partner.accounttaxng.internal',
            'password' => bcrypt('password'), 'role' => 'accountant', 'is_active' => true, 'is_partner_proxy' => true,
        ]);

        // 1 real user against a limit of 2 — still within limit even though 2 rows physically exist.
        $this->assertTrue($tenant->withinLimit('users'));
    }
}
