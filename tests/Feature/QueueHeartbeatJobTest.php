<?php

namespace Tests\Feature;

use App\Jobs\QueueHeartbeatJob;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QueueHeartbeatJobTest extends TestCase
{
    public function test_handle_sets_a_recent_heartbeat_timestamp(): void
    {
        Cache::forget('queue.worker.heartbeat_at');

        (new QueueHeartbeatJob)->handle();

        $heartbeat = Cache::get('queue.worker.heartbeat_at');

        $this->assertNotNull($heartbeat);
        $this->assertTrue(now()->diffInSeconds($heartbeat) < 5);
    }
}
