<?php

namespace Tests\Feature;

use App\Models\FirmClientLink;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FirmClientLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirmClientWorkspaceSwitchTest extends TestCase
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

    public function test_firm_accountant_can_enter_an_active_client_workspace(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client');
        $link = $this->makeActiveLink($firm, $client, $firmAdmin);

        $response = $this->actingAs($firmAdmin)->post(route('partners.clients.enter', $link));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($link->proxyUser);
        $this->assertSame($firmAdmin->id, session('partner_origin_user_id'));

        // The proxy user genuinely belongs to the client tenant, so the next
        // request's TenantMiddleware resolves the client's own workspace.
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_entering_a_revoked_link_is_forbidden(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm2');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client2');
        $link = $this->makeActiveLink($firm, $client, $firmAdmin);
        $link->update(['status' => FirmClientLink::STATUS_REVOKED]);

        $this->actingAs($firmAdmin)->post(route('partners.clients.enter', $link))->assertForbidden();
        $this->assertAuthenticatedAs($firmAdmin);
    }

    public function test_firm_user_from_a_different_firm_tenant_cannot_enter(): void
    {
        [$firm, $firmAdmin]       = $this->makeTenantWithAdmin('Firm3');
        [$client, $clientAdmin]   = $this->makeTenantWithAdmin('Client3');
        [$otherFirm, $otherAdmin] = $this->makeTenantWithAdmin('OtherFirm3');
        $link = $this->makeActiveLink($firm, $client, $firmAdmin);

        $this->actingAs($otherAdmin)->post(route('partners.clients.enter', $link))->assertForbidden();
    }

    public function test_exit_client_workspace_restores_the_original_firm_user(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm4');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client4');
        $link = $this->makeActiveLink($firm, $client, $firmAdmin);

        $this->actingAs($firmAdmin)->post(route('partners.clients.enter', $link));
        $this->assertAuthenticatedAs($link->proxyUser);

        $response = $this->post(route('partners.exit'));

        $response->assertRedirect(route('partners.clients.index'));
        $this->assertAuthenticatedAs($firmAdmin);
        $this->assertFalse(session()->has('partner_origin_user_id'));
    }

    public function test_revoking_a_link_forces_logout_of_an_in_progress_partner_session_on_its_next_request(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm5');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client5');
        $link = $this->makeActiveLink($firm, $client, $firmAdmin);

        $this->actingAs($firmAdmin)->post(route('partners.clients.enter', $link));
        $this->assertAuthenticatedAs($link->proxyUser);

        // Revoked "from the other side" — e.g. the client admin, in a separate browser session.
        app(FirmClientLinkService::class)->revoke($link, $clientAdmin);

        // A real browser request always re-resolves the user from the DB; within a single
        // test process the auth guard caches the user object across sequential calls, so
        // force it to forget that cache to accurately simulate the next real request.
        \Illuminate\Support\Facades\Auth::forgetGuards();

        // The partner session's very next request is force-logged-out by TenantMiddleware's is_active check.
        $this->get(route('dashboard'))->assertStatus(403);
        $this->assertGuest();
    }
}
