<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\PlatformPaymentCredential;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $tenantAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::forceCreate([
            'name'              => 'Super Admin',
            'email'             => 'superadmin@platform.test',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_superadmin'     => true,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $plan = Plan::create([
            'name'          => 'Free',
            'slug'          => 'free',
            'price_monthly' => 0,
            'limits'        => [],
            'is_active'     => true,
            'is_public'     => true,
        ]);

        $tenant = Tenant::create([
            'name'                    => 'Test Co',
            'slug'                    => 'test-co',
            'email'                   => 'admin@testco.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 5_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $plan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $this->tenantAdmin = User::forceCreate([
            'tenant_id'         => $tenant->id,
            'name'              => 'Tenant Admin',
            'email'             => 'admin@testco.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_superadmin'     => false,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_superadmin_can_view_payment_gateway_page(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get(route('superadmin.payment-gateway.index'));

        $response->assertOk();
        $response->assertViewIs('superadmin.payment-gateway.index');
    }

    public function test_tenant_admin_cannot_view_payment_gateway_page(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('superadmin.payment-gateway.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('superadmin.payment-gateway.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_store_creates_an_encrypted_credential_and_audit_log(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.payment-gateway.store'), [
                'secret_key' => 'sk_live_newsecret1234',
                'public_key' => 'pk_live_newpublic1234',
            ]);

        $response->assertRedirect(route('superadmin.payment-gateway.index'));
        $response->assertSessionHas('success');

        $raw = DB::table('platform_payment_credentials')->where('provider', 'paystack')->first();
        $this->assertNotNull($raw);
        $this->assertNotEquals('sk_live_newsecret1234', $raw->secret_key);

        $audit = AuditLog::where('event', 'superadmin.paystack_credentials_updated')->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString('sk_live_newsecret1234', json_encode($audit->new_values));
        $this->assertSame(4, strlen($audit->new_values['secret_key_last4']));
    }

    public function test_store_with_blank_secret_key_keeps_existing_value(): void
    {
        PlatformPaymentCredential::create([
            'provider'   => 'paystack',
            'secret_key' => 'sk_live_original',
            'public_key' => 'pk_live_original',
        ]);

        $this->actingAs($this->superadmin)
            ->post(route('superadmin.payment-gateway.store'), [
                'secret_key' => '',
                'public_key' => 'pk_live_updated',
            ]);

        $credential = PlatformPaymentCredential::where('provider', 'paystack')->first();
        $this->assertSame('sk_live_original', $credential->secret_key);
        $this->assertSame('pk_live_updated', $credential->public_key);
    }

    public function test_store_fails_validation_when_no_secret_key_and_none_exists_yet(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.payment-gateway.store'), [
                'secret_key' => '',
                'public_key' => 'pk_live_updated',
            ]);

        $response->assertSessionHasErrors('secret_key');
        $this->assertDatabaseMissing('platform_payment_credentials', ['provider' => 'paystack']);
    }

    public function test_paystack_service_resolves_db_key_over_env(): void
    {
        config(['paystack.secret_key' => 'env_secret_key']);

        PlatformPaymentCredential::create([
            'provider'   => 'paystack',
            'secret_key' => 'db_secret_key',
        ]);

        Http::fake(['*' => Http::response(['status' => true, 'data' => []], 200)]);

        app(PaystackService::class)->initializeTransaction(['email' => 'x@y.com', 'amount' => 1000]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer db_secret_key');
        });
    }
}
