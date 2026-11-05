<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Reads the heartbeat timestamp QueueHeartbeatJob writes every time a real
 * queue worker (not just the scheduler) processes a job. Shared by the
 * superadmin queue dashboard and the scheduled worker-down alert so the
 * staleness threshold only lives in one place.
 */
class QueueHealth
{
    public const STALE_AFTER_MINUTES = 3;

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
