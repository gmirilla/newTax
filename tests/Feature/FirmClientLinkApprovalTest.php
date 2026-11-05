<?php

namespace Tests\Feature;

use App\Models\FirmClientLink;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirmClientLinkApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenantWithAdmin(string $name): array
    {
        $plan = Plan::create([
            'slug' => 'test-plan-' . uniqid(), 'name' => 'Test', 'price_monthly' => 0,
            'is_active' => true, 'limits' => [],
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

    private function makeLink(Tenant $firm, Tenant $client, string $status, User $requester): FirmClientLink
    {
        return FirmClientLink::create([
            'firm_tenant_id' => $firm->id, 'client_tenant_id' => $client->id,
            'status' => $status, 'requested_by_user_id' => $requester->id,
        ]);
    }

    public function test_client_admin_approving_a_pending_client_approval_link_activates_it_and_provisions_a_proxy_user(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client');
        $link = $this->makeLink($firm, $client, FirmClientLink::STATUS_PENDING_CLIENT, $firmAdmin);

        $response = $this->actingAs($clientAdmin)->post(route('partners.manage.approve', $link));

        $response->assertRedirect();
        $link->refresh();
        $this->assertSame(FirmClientLink::STATUS_ACTIVE, $link->status);
        $this->assertNotNull($link->proxy_user_id);

        $proxy = User::find($link->proxy_user_id);
        $this->assertSame($client->id, $proxy->tenant_id);
        $this->assertSame('accountant', $proxy->role);
        $this->assertTrue((bool) $proxy->is_partner_proxy);
    }

    public function test_firm_admin_approving_a_pending_firm_approval_link_activates_it(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm2');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client2');
        $link = $this->makeLink($firm, $client, FirmClientLink::STATUS_PENDING_FIRM, $clientAdmin);

        $this->actingAs($firmAdmin)->post(route('partners.clients.approve', $link))->assertRedirect();

        $this->assertSame(FirmClientLink::STATUS_ACTIVE, $link->refresh()->status);
    }

    public function test_wrong_side_cannot_approve(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm3');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client3');
        $link = $this->makeLink($firm, $client, FirmClientLink::STATUS_PENDING_FIRM, $clientAdmin);

        // This link awaits the FIRM's approval — the client admin trying to approve it must be denied.
        $this->actingAs($clientAdmin)->post(route('partners.manage.approve', $link))->assertForbidden();

        $this->assertSame(FirmClientLink::STATUS_PENDING_FIRM, $link->refresh()->status);
    }

    public function test_non_admin_on_the_correct_side_cannot_approve(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm4');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client4');
        $link = $this->makeLink($firm, $client, FirmClientLink::STATUS_PENDING_CLIENT, $firmAdmin);

        $clientAccountant = User::forceCreate([
            'tenant_id' => $client->id, 'name' => 'Accountant', 'email' => 'acct4@test.ng',
            'password' => bcrypt('password'), 'role' => 'accountant', 'is_active' => true,
        ]);

        // Blocked by role:admin middleware before the policy is ever consulted.
        $this->actingAs($clientAccountant)->post(route('partners.manage.approve', $link))->assertRedirect(route('dashboard'));
    }
}
