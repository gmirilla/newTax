<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAntiAbuseTest extends TestCase
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
            'terms_accepted'        => '1',
        ], $overrides);
    }

    // ── Honeypot ─────────────────────────────────────────────────────────────

    public function test_registration_is_silently_rejected_when_the_honeypot_field_is_filled(): void
    {
        $payload = $this->registerPayload(['website' => 'http://spam.example']);

        $this->post(route('register'), $payload);

        $this->assertDatabaseMissing('tenants', ['email' => 'company@acmetrading.ng']);
        $this->assertDatabaseMissing('users', ['email' => 'jane@acmetrading.ng']);
    }

    public function test_registration_succeeds_when_the_honeypot_field_is_left_blank(): void
    {
        $this->post(route('register'), $this->registerPayload())
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'jane@acmetrading.ng']);
    }

    public function test_login_is_silently_rejected_when_the_honeypot_field_is_filled(): void
    {
        $tenant = Tenant::create([
            'name' => 'Honeypot Co', 'slug' => 'honeypot-co', 'email' => 'honeypot@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN', 'is_active' => true,
        ]);
        User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Honeypot Admin', 'email' => 'admin@honeypot.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);

        $response = $this->post(route('login'), [
            'email'    => 'admin@honeypot.ng',
            'password' => 'password',
            'website'  => 'http://spam.example',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ── Disposable email blocking ───────────────────────────────────────────

    public function test_registration_rejects_a_disposable_personal_email(): void
    {
        $payload = $this->registerPayload(['email' => 'jane@yopmail.com']);

        $this->post(route('register'), $payload)
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'jane@yopmail.com']);
    }

    public function test_registration_rejects_a_disposable_company_email(): void
    {
        $payload = $this->registerPayload(['company_email' => 'company@mailinator.com']);

        $this->post(route('register'), $payload)
            ->assertSessionHasErrors('company_email');

        $this->assertDatabaseMissing('tenants', ['email' => 'company@mailinator.com']);
    }

    // ── Throttling ───────────────────────────────────────────────────────────

    public function test_rapid_repeated_registration_attempts_are_throttled(): void
    {
        // Honeypot-filled requests are rejected without logging anyone in,
        // so the caller stays a guest across every attempt (isolating the
        // throttle behavior from registration's own side effects).
        $payload = $this->registerPayload(['website' => 'http://spam.example']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('register'), $payload);
        }

        $response = $this->post(route('register'), $payload);

        $response->assertStatus(429);
    }

    public function test_rapid_repeated_login_attempts_are_throttled(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('login'), ['email' => 'nobody@test.ng', 'password' => 'wrong']);
        }

        $response = $this->post(route('login'), ['email' => 'nobody@test.ng', 'password' => 'wrong']);

        $response->assertStatus(429);
    }
}
