<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Dispatched every minute by the scheduler and run by the queue worker.
 * The cache timestamp it sets only advances when a worker actually dequeues
 * and executes a job — proving the worker, not just cron, is alive.
 */
class QueueHeartbeatJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Only one heartbeat sits in the queue at a time, so a down worker doesn't
    // accumulate one job per minute. The lock expires after 10 minutes so a lost
    // job can't block heartbeats forever.
    public int $uniqueFor = 600;

    public function handle(): void
    {
        Cache::put('queue.worker.heartbeat_at', now(), now()->addMinutes(10));
    }
}
