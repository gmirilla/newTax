<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Reads the heartbeat timestamp QueueHeartbeatJob writes every time a real
 * queue worker (not just the scheduler) processes a job. Shared by the
 * superadmin queue dashboard and the scheduled worker-down alert so the
 * staleness threshold only lives in one place.
 *
 * Threshold note: on shared hosting without a persistent worker daemon, the
 * documented fallback is `php artisan queue:work --stop-when-empty` run via
 * its own cron entry (commonly every 5 minutes) rather than a continuously
 * running process — see GAPS.md. In that setup the heartbeat can legitimately
 * go several minutes between updates even when everything is healthy, so the
 * threshold must comfortably exceed that worker cron interval or every check
 * falsely reports "down". If this environment ever moves to a continuously
 * running worker, this can be tightened back down.
 */
class QueueHealth
{
    public const STALE_AFTER_MINUTES = 8;

    public static function heartbeatAt(): ?Carbon
    {
        return Cache::get('queue.worker.heartbeat_at');
    }

    public static function isPresumedDown(): bool
    {
        $heartbeat = self::heartbeatAt();

        // diffInMinutes() returns a signed difference (negative for past timestamps) — use the absolute value.
        return !$heartbeat || now()->diffInMinutes($heartbeat, true) > self::STALE_AFTER_MINUTES;
    }
}
