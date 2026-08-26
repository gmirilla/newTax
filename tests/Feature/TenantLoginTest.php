<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantLoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(string $slug, bool $active = true): Tenant
    {
        return Tenant::create([
            'name'            => ucwords(str_replace('-', ' ', $slug)),
            'slug'            => $slug,
            'email'           => $slug . '@test.ng',
            'tax_category'    => 'small',
            'annual_turnover' => 5_000_000,
            'currency'        => 'NGN',
            'is_active'       => $active,
        ]);
    }

    private function makeUser(Tenant $tenant, string $email): User
    {
        return User::forceCreate([
            'tenant_id'         => $tenant->id,
            'name'              => 'Admin',
            'email'             => $email,
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_branded_login_page_shows_company_name(): void
    {
        $tenant = $this->makeTenant('acme-ltd');

        $response = $this->get(route('login.tenant', $tenant->slug));

        $response->assertOk();
        $response->assertSee('Acme Ltd');
    }

    public function test_branded_login_page_404s_for_inactive_tenant(): void
    {
        $tenant = $this->makeTenant('closed-co', active: false);

        $this->get(route('login.tenant', $tenant->slug))->assertNotFound();
    }

    public function test_member_can_sign_in_through_their_company_url(): void
    {
        $tenant = $this->makeTenant('acme-ltd');
        $user   = $this->makeUser($tenant, 'admin@acme-ltd.ng');

        $response = $this->post(route('login.tenant.submit', $tenant->slug), [
            'email'    => 'admin@acme-ltd.ng',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_from_another_company_is_rejected_and_logged_out(): void
    {
        $tenantA = $this->makeTenant('acme-ltd');
        $tenantB = $this->makeTenant('globex-inc');
        $this->makeUser($tenantB, 'admin@globex-inc.ng');

        $response = $this->post(route('login.tenant.submit', $tenantA->slug), [
            'email'    => 'admin@globex-inc.ng',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $tenant = $this->makeTenant('acme-ltd');
        $this->makeUser($tenant, 'admin@acme-ltd.ng');

        $response = $this->post(route('login.tenant.submit', $tenant->slug), [
            'email'    => 'admin@acme-ltd.ng',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
