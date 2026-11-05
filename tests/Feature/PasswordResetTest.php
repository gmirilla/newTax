<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $tenant = Tenant::create([
            'name' => 'Reset Co', 'slug' => 'reset-co-' . uniqid(), 'email' => 'reset-' . uniqid() . '@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN', 'is_active' => true,
        ]);

        return User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Reset User', 'email' => 'reset-user-' . uniqid() . '@test.ng',
            'password' => bcrypt('old-password-1'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);
    }

    public function test_requesting_a_reset_link_for_an_existing_email_sends_the_custom_notification(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_requesting_a_reset_link_for_an_unknown_email_gives_the_same_response_and_sends_nothing(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), ['email' => 'nobody-' . uniqid() . '@test.ng']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_honeypot_filled_silently_skips_sending_and_still_shows_the_same_message(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
            'website' => 'http://spam.example',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_rapid_repeated_reset_requests_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['email' => 'nobody@test.ng']);
        }

        $response = $this->post(route('password.email'), ['email' => 'nobody@test.ng']);

        $response->assertStatus(429);
    }

    public function test_resetting_with_a_valid_token_changes_the_password_and_allows_login(): void
    {
        $user = $this->makeUser();
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPassword123', $user->refresh()->password));
    }

    public function test_resetting_with_an_invalid_token_fails_without_changing_the_password(): void
    {
        $user = $this->makeUser();
        $originalHash = $user->password;

        $response = $this->post(route('password.update'), [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame($originalHash, $user->refresh()->password);
    }

    public function test_resetting_with_mismatched_confirmation_fails_validation(): void
    {
        $user = $this->makeUser();
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'SomethingElse123',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
