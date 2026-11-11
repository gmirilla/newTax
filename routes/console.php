<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Scheduled Tasks ─────────────────────────────────────────────────────────

Schedule::command('tax:send-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->description('Send Nigerian tax compliance reminders (VAT, CIT deadlines)');

Schedule::command('tax:generate-vat-returns')
    ->monthlyOn(1, '01:00')   // 1st of each month, generate previous month's VAT return
    ->withoutOverlapping()
    ->description('Auto-generate VAT returns for all registered tenants');

Schedule::command('invoices:mark-overdue')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->description('Mark past-due invoices as overdue');

Schedule::command('subscriptions:downgrade-expired-trials')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->description('Downgrade expired trial tenants to the Free plan');

Schedule::command('maintenance:generate-pm-work-orders')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->description('Generate PM work orders for due schedules');

// Drain the queue from the scheduler itself so a single `schedule:run` cron line is
// enough on shared hosting (no separate queue:work cron entry to go missing).
// --max-time keeps each run under a minute; runInBackground stops it delaying other
// scheduled tasks; the short overlap expiry avoids a stuck mutex if a run is killed.
// Place before the heartbeat so it is picked up by the same run's worker.
Schedule::command('queue:work --stop-when-empty --tries=3 --backoff=120 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->description('Process queued jobs (emails, notifications, FIRS) without a separate worker daemon');

Schedule::job(new \App\Jobs\QueueHeartbeatJob)
    ->everyMinute()
    ->description('Heartbeat: confirms the queue worker is alive and processing jobs');

Schedule::command('queue:check-worker-health')
    ->everyFiveMinutes()
    ->description('Alert by email if the queue worker appears down (or has recovered)');

// ─── Backups ──────────────────────────────────────────────────────────────────
// Staggered: run → clean → monitor, 30 minutes apart to avoid overlap.
// backup:run uses pg_dump which must be available on the server PATH.

Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->description('Daily database backup to local disk and Namecheap SFTP');

Schedule::command('backup:clean')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->description('Prune old backups according to retention policy');

Schedule::command('backup:monitor')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->description('Check backup health and alert on failure');
