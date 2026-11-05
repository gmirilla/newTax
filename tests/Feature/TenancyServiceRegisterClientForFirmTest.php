<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FirmClientLink;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenancyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyServiceRegisterClientForFirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_firm_can_create_a_brand_new_client_with_an_auto_active_link_and_no_approval_step(): void
    {
        $plan = Plan::create([
            'slug' => 'test-plan-' . uniqid(), 'name' => 'Test', 'price_monthly' => 0,
            'is_active' => true, 'limits' => [],
        ]);

        $firm = Tenant::create([
            'name' => 'Firm', 'slug' => 'firm-' . uniqid(), 'email' => 'firm-' . uniqid() . '@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $firmAdmin = User::forceCreate([
            'tenant_id' => $firm->id, 'name' => 'Firm Admin', 'email' => 'firm-admin-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);

        $result = app(TenancyService::class)->registerClientForFirm(
            $firm,
            ['name' => 'Brand New Client Co', 'email' => 'newclient-' . uniqid() . '@test.ng'],
            ['name' => 'Client Admin', 'email' => 'newclient-admin-' . uniqid() . '@test.ng', 'password' => 'password123'],
            $firmAdmin
        );

        $this->assertArrayHasKey('tenant', $result);
        $this->assertArrayHasKey('admin', $result);
        $this->assertArrayHasKey('link', $result);

        /** @var FirmClientLink $link */
        $link = $result['link'];
        $this->assertSame(FirmClientLink::STATUS_ACTIVE, $link->status);
        $this->assertSame($firm->id, $link->firm_tenant_id);
        $this->assertSame($result['tenant']->id, $link->client_tenant_id);
        $this->assertNull($link->approved_by_user_id); // auto-activated, not human-approved
        $this->assertNotNull($link->proxy_user_id);

        $proxy = User::find($link->proxy_user_id);
        $this->assertSame($result['tenant']->id, $proxy->tenant_id);
        $this->assertTrue((bool) $proxy->is_partner_proxy);

        // The new tenant got the same provisioning registerTenant() already guarantees.
        $this->assertTrue(Account::where('tenant_id', $result['tenant']->id)->exists());
        $this->assertSame('admin', $result['admin']->role);
    }
}
