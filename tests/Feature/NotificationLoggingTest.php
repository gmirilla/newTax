<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Minimal mail notification used purely to exercise the logging listeners end-to-end. */
class LoggingFixtureNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Test')
            ->line('Test body.');
    }
}

class NotificationLoggingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'price_monthly' => 0, 'limits' => [],
            'is_active' => true, 'is_public' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Log Co', 'slug' => 'log-co', 'email' => 'logco@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $this->user = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Log Admin', 'email' => 'log@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_a_successful_send_creates_a_sent_log_row(): void
    {
        Mail::fake();

        $this->user->notify(new LoggingFixtureNotification);

        $log = NotificationLog::sent()->first();
        $this->assertNotNull($log);
        $this->assertSame(LoggingFixtureNotification::class, $log->notification_class);
        $this->assertSame($this->user->tenant_id, $log->tenant_id);
        $this->assertSame($this->user->id, $log->notifiable_id);
    }

    public function test_a_failed_send_creates_a_failed_log_row_with_the_error_message(): void
    {
        // Fire the event directly to exercise the listener in isolation,
        // without needing a real channel failure to occur end-to-end.
        event(new NotificationFailed(
            $this->user,
            new LoggingFixtureNotification,
            'mail',
            ['exception' => new \RuntimeException('SMTP connection refused')]
        ));

        $log = NotificationLog::failed()->first();
        $this->assertNotNull($log);
        $this->assertSame('SMTP connection refused', $log->error_message);
    }
}
