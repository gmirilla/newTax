<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FirmClientLink;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FirmClientLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirmClientLinkAuditTraceabilityTest extends TestCase
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

    public function test_a_mutating_request_during_a_partner_session_stamps_both_the_proxy_and_the_real_firm_user(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client');

        $link = FirmClientLink::create([
            'firm_tenant_id' => $firm->id, 'client_tenant_id' => $client->id,
            'status' => FirmClientLink::STATUS_ACTIVE, 'requested_by_user_id' => $firmAdmin->id,
            'approved_at' => now(),
        ]);
        app(FirmClientLinkService::class)->provisionProxyUser($link);
        $link->refresh();

        $this->actingAs($firmAdmin)->post(route('partners.clients.enter', $link));
        $this->assertAuthenticatedAs($link->proxyUser);

        $this->post(route('upsell.dismiss'));

        $log = AuditLog::where('tenant_id', $client->id)
            ->where('user_id', $link->proxy_user_id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($firmAdmin->id, $log->acting_on_behalf_of_user_id);
    }

    public function test_acting_on_behalf_of_is_null_for_an_ordinary_non_partner_request(): void
    {
        [, $clientAdmin] = $this->makeTenantWithAdmin('Client2');

        $this->actingAs($clientAdmin)->post(route('upsell.dismiss'));

        $log = AuditLog::where('user_id', $clientAdmin->id)->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertNull($log->acting_on_behalf_of_user_id);
    }
}
