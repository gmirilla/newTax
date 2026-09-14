<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name'          => 'Acme Trading Co',
            'company_email'         => 'company@acmetrading.ng',
            'name'                  => 'Jane Founder',
            'email'                 => 'jane@acmetrading.ng',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_registration_grants_immediate_dashboard_access_without_verifying_email(): void
    {
        $response = $this->post(route('register'), $this->registerPayload());

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'jane@acmetrading.ng')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_registration_sends_a_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->registerPayload());

        $user = User::where('email', 'jane@acmetrading.ng')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_dashboard_shows_verify_email_reminder_for_unverified_user(): void
    {
        $this->post(route('register'), $this->registerPayload());
        $user = User::where('email', 'jane@acmetrading.ng')->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Verify your email');
    }

    public function test_dashboard_hides_verify_email_reminder_for_verified_user(): void
    {
        $tenant = Tenant::create([
            'name' => 'Verified Co', 'slug' => 'verified-co', 'email' => 'verified@co.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true,
        ]);

        $user = User::forceCreate([
            'tenant_id'         => $tenant->id,
            'name'              => 'Verified Admin',
            'email'             => 'admin@verified-co.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Verify your email');
    }

    public function test_dismissing_the_verify_email_banner_hides_it_for_the_rest_of_the_day(): void
    {
        $this->post(route('register'), $this->registerPayload());
        $user = User::where('email', 'jane@acmetrading.ng')->firstOrFail();

        $this->actingAs($user)
            ->post(route('verification.banner.dismiss'))
            ->assertNoContent();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Verify your email');
    }
}
