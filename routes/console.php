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
