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
