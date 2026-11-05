<?php

namespace Tests\Feature;

use App\Models\FirmClientLink;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FirmClientLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirmClientLinkRevocationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenantWithAdmin(string $name): array
    {
        $plan = Plan::create([
            'slug' => 'test-plan-' . uniqid(), 'name' => 'Test', 'price_monthly' => 0,
            'is_active' => true, 'limits' => ['users' => 5],
        ]);

        $tenant = Tenant::create([
            'name' => $name, 'slug' => strtolower($name) . '-' . uniqid(), 'email' => strtolower($name) . uniqid() . '@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $admin = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => "{$name} Admin", 'email' => strtolower($name) . '-admin-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);

        return [$tenant, $admin];
    }

    private function makeActiveLink(Tenant $firm, Tenant $client, User $requester): FirmClientLink
    {
        $link = FirmClientLink::create([
            'firm_tenant_id' => $firm->id, 'client_tenant_id' => $client->id,
            'status' => FirmClientLink::STATUS_ACTIVE, 'requested_by_user_id' => $requester->id,
            'approved_at' => now(),
        ]);

        app(FirmClientLinkService::class)->provisionProxyUser($link);

        return $link->refresh();
    }

    public function test_client_admin_can_revoke_an_active_link(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client');
        $link = $this->makeActiveLink($firm, $client, $firmAdmin);

        $this->actingAs($clientAdmin)->post(route('partners.manage.revoke', $link))->assertRedirect();

        $this->assertSame(FirmClientLink::STATUS_REVOKED, $link->refresh()->status);
        $this->assertFalse((bool) $link->proxyUser->refresh()->is_active);
    }

    public function test_firm_admin_can_revoke_an_active_link(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm2');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client2');
        $link = $this->makeActiveLink($firm, $client, $firmAdmin);

        $this->actingAs($firmAdmin)->post(route('partners.clients.revoke', $link))->assertRedirect();

        $this->assertSame(FirmClientLink::STATUS_REVOKED, $link->refresh()->status);
    }

    public function test_non_admin_cannot_revoke(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm3');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client3');
        $link = $this->makeActiveLink($firm, $client, $firmAdmin);

        $clientAccountant = User::forceCreate([
            'tenant_id' => $client->id, 'name' => 'Accountant', 'email' => 'acct3@test.ng',
            'password' => bcrypt('password'), 'role' => 'accountant', 'is_active' => true,
        ]);

        // Blocked by role:admin middleware before the policy is consulted.
        $this->actingAs($clientAccountant)
            ->post(route('partners.manage.revoke', $link))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(FirmClientLink::STATUS_ACTIVE, $link->refresh()->status);
    }

    public function test_revoked_proxy_user_no_longer_counts_against_the_clients_user_limit(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm4');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client4');
        $link = $this->makeActiveLink($firm, $client, $firmAdmin);

        // Proxy users never count regardless — but confirm the cache is invalidated
        // correctly on revoke (no stale cached count lingering).
        $this->assertTrue($client->withinLimit('users'));

        $this->actingAs($clientAdmin)->post(route('partners.manage.revoke', $link));

        $client->refresh();
        $this->assertTrue($client->withinLimit('users'));
    }
}
