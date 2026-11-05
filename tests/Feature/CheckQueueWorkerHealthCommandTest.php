<?php

namespace Tests\Feature;

use App\Mail\QueueWorkerStatusAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckQueueWorkerHealthCommandTest extends TestCase
{
    public function test_it_alerts_when_no_heartbeat_has_ever_been_recorded(): void
    {
        Mail::fake();
        Cache::forget('queue.worker.heartbeat_at');
        Cache::forget('queue.worker.down_alert_sent');

        $this->artisan('queue:check-worker-health')->assertSuccessful();

        Mail::assertSent(QueueWorkerStatusAlert::class, fn ($mail) => $mail->isDown === true);
        $this->assertTrue(Cache::get('queue.worker.down_alert_sent'));
    }

    public function test_it_does_not_send_a_duplicate_alert_while_still_down(): void
    {
        Mail::fake();
        Cache::forget('queue.worker.heartbeat_at');
        Cache::put('queue.worker.down_alert_sent', true, now()->addDay());

        $this->artisan('queue:check-worker-health')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_it_sends_a_recovery_alert_once_the_heartbeat_is_fresh_again(): void
    {
        Mail::fake();
        Cache::put('queue.worker.heartbeat_at', now(), now()->addMinutes(10));
        Cache::put('queue.worker.down_alert_sent', true, now()->addDay());

        $this->artisan('queue:check-worker-health')->assertSuccessful();

        Mail::assertSent(QueueWorkerStatusAlert::class, fn ($mail) => $mail->isDown === false);
        $this->assertFalse(Cache::has('queue.worker.down_alert_sent'));
    }

    public function test_it_sends_nothing_during_normal_healthy_operation(): void
    {
        Mail::fake();
        Cache::put('queue.worker.heartbeat_at', now(), now()->addMinutes(10));
        Cache::forget('queue.worker.down_alert_sent');

        $this->artisan('queue:check-worker-health')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_recipient_falls_back_to_mail_from_address_when_env_var_is_unset(): void
    {
        Mail::fake();
        Cache::forget('queue.worker.heartbeat_at');
        Cache::forget('queue.worker.down_alert_sent');
        config(['mail.from.address' => 'ops-fallback@test.ng']);

        $this->artisan('queue:check-worker-health')->assertSuccessful();

        Mail::assertSent(QueueWorkerStatusAlert::class, function ($mail) {
            return $mail->hasTo('ops-fallback@test.ng');
        });
    }

    public function test_a_mail_failure_does_not_crash_the_command(): void
    {
        Cache::forget('queue.worker.heartbeat_at');
        Cache::forget('queue.worker.down_alert_sent');

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->artisan('queue:check-worker-health')->assertSuccessful();
    }
}
