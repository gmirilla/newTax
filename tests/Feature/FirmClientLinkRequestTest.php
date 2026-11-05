<?php

namespace Tests\Feature;

use App\Mail\FirmLinkInvitation;
use App\Models\FirmClientLink;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FirmClientLinkRequestTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenantWithAdmin(string $name, string $role = 'admin'): array
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
            'partner_code' => strtoupper(substr(md5($name . uniqid()), 0, 8)),
        ]);

        $user = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => "{$name} User", 'email' => strtolower($name) . '-user-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => $role, 'is_active' => true, 'email_verified_at' => now(),
        ]);

        return [$tenant, $user];
    }

    public function test_firm_admin_can_request_a_link_to_an_existing_client_by_email(): void
    {
        Mail::fake();

        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client');

        $response = $this->actingAs($firmAdmin)
            ->post(route('partners.clients.request'), ['email' => $clientAdmin->email]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('firm_client_links', [
            'firm_tenant_id'   => $firm->id,
            'client_tenant_id' => $client->id,
            'status'           => FirmClientLink::STATUS_PENDING_CLIENT,
        ]);

        Mail::assertSent(FirmLinkInvitation::class, fn ($mail) => $mail->hasTo($clientAdmin->email));
    }

    public function test_client_admin_can_request_a_link_to_a_firm_by_partner_code(): void
    {
        Notification::fake();

        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm2');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client2');

        $response = $this->actingAs($clientAdmin)
            ->post(route('partners.manage.request'), ['partner_code' => $firm->partner_code]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('firm_client_links', [
            'firm_tenant_id'   => $firm->id,
            'client_tenant_id' => $client->id,
            'status'           => FirmClientLink::STATUS_PENDING_FIRM,
        ]);
    }

    public function test_non_admin_cannot_request_a_link_from_either_side(): void
    {
        [$firm, ]   = $this->makeTenantWithAdmin('Firm3');
        [$client, ] = $this->makeTenantWithAdmin('Client3');

        $firmStaff   = User::forceCreate([
            'tenant_id' => $firm->id, 'name' => 'Staff', 'email' => 'staff3a@test.ng',
            'password' => bcrypt('password'), 'role' => 'staff', 'is_active' => true,
        ]);
        $clientStaff = User::forceCreate([
            'tenant_id' => $client->id, 'name' => 'Staff', 'email' => 'staff3b@test.ng',
            'password' => bcrypt('password'), 'role' => 'staff', 'is_active' => true,
        ]);

        $this->actingAs($firmStaff)
            ->post(route('partners.clients.request'), ['email' => 'nobody@test.ng'])
            ->assertRedirect(route('staff.dashboard'));

        $this->actingAs($clientStaff)
            ->post(route('partners.manage.request'), ['partner_code' => 'WHATEVER'])
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_duplicate_active_link_request_is_rejected(): void
    {
        [$firm, $firmAdmin]     = $this->makeTenantWithAdmin('Firm4');
        [$client, $clientAdmin] = $this->makeTenantWithAdmin('Client4');

        FirmClientLink::create([
            'firm_tenant_id' => $firm->id, 'client_tenant_id' => $client->id,
            'status' => FirmClientLink::STATUS_ACTIVE, 'requested_by_user_id' => $firmAdmin->id,
        ]);

        $response = $this->actingAs($firmAdmin)
            ->post(route('partners.clients.request'), ['email' => $clientAdmin->email]);

        $response->assertSessionHas('error');
        $this->assertSame(1, FirmClientLink::where('firm_tenant_id', $firm->id)->where('client_tenant_id', $client->id)->count());
    }

    public function test_self_link_is_rejected(): void
    {
        [$firm, $firmAdmin] = $this->makeTenantWithAdmin('Firm5');

        $response = $this->actingAs($firmAdmin)
            ->post(route('partners.manage.request'), ['partner_code' => $firm->partner_code]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('firm_client_links', ['firm_tenant_id' => $firm->id, 'client_tenant_id' => $firm->id]);
    }
}
