<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingSoon;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DowngradeExpiredTrials extends Command
{
    protected $signature = 'subscriptions:downgrade-expired-trials';
    protected $description = 'Process expired subscriptions: downgrade trials, apply pending plan changes, clean up orphaned cancellations, send trial-ending notifications';

    public function handle(): int
    {
        $freePlan = Plan::where('slug', 'free')->where('is_active', true)->first();

        if (!$freePlan) {
            $this->error('Free plan not found. Ensure a plan with slug "free" exists and is active.');
            return self::FAILURE;
        }

        $total = 0;

        // ── 1. Trial ending soon notifications (3 days before expiry) ─────────
        $soonExpiring = Tenant::where('subscription_status', 'trialing')
            ->whereDate('trial_ends_at', now()->addDays(3)->toDateString())
            ->get();

        foreach ($soonExpiring as $tenant) {
            try {
                Mail::to($tenant->email)->send(new TrialEndingSoon($tenant));
                $this->line("  Trial ending soon — email sent: {$tenant->name}");
            } catch (\Throwable $e) {
                $this->warn("  Failed to send trial-ending email to {$tenant->email}: {$e->getMessage()}");
            }
        }

        // ── 2. Expired trials → Free (no grace period for trials) ────────────
        $expiredTrials = Tenant::where('subscription_status', 'trialing')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expiredTrials as $tenant) {
            $tenant->assignPlan($freePlan, 'active');
            $this->line("  Trial expired → Free: {$tenant->name} (ID {$tenant->id})");
            $total++;
        }

        // ── 3. Pending plan changes past grace period (7 days after expiry) ───
        $pendingChanges = Tenant::whereNotNull('next_plan_id')
            ->where('subscription_expires_at', '<', now()->subDays(7))
            ->with('nextPlan')
            ->get();

        foreach ($pendingChanges as $tenant) {
            $newPlan = $tenant->nextPlan ?? $freePlan;
            $tenant->assignPlan($newPlan, 'active');
            $tenant->update(['next_plan_id' => null]);
            $this->line("  Pending change applied → {$newPlan->name}: {$tenant->name} (ID {$tenant->id})");
            $total++;
        }

        // ── 4. Orphaned cancelled/suspended subscriptions past grace period ───
        $orphaned = Tenant::whereIn('subscription_status', ['cancelled', 'suspended'])
            ->whereNull('next_plan_id')
            ->where('subscription_expires_at', '<', now()->subDays(7))
            ->whereNotNull('plan_id')
            ->whereHas('plan', fn($q) => $q->where('price_monthly', '>', 0))
            ->get();

        foreach ($orphaned as $tenant) {
            $tenant->assignPlan($freePlan, 'active');
            $this->line("  Orphaned {$tenant->subscription_status} → Free: {$tenant->name} (ID {$tenant->id})");
            $total++;
        }

        if ($total === 0) {
            $this->info('No subscription changes needed.');
        } else {
            $this->info("Processed {$total} subscription change(s).");
        }

        return self::SUCCESS;
    }
}
