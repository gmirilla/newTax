<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
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
class QueueHeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Cache::put('queue.worker.heartbeat_at', now(), now()->addMinutes(10));
    }
}
