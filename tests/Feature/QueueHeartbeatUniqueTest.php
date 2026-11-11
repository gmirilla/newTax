<?php

namespace Tests\Feature;

use App\Jobs\QueueHeartbeatJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueHeartbeatUniqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_is_not_queued_twice_while_one_is_pending(): void
    {
        Cache::flush();
        Queue::fake();

        QueueHeartbeatJob::dispatch();
        QueueHeartbeatJob::dispatch();
        QueueHeartbeatJob::dispatch();

        Queue::assertPushed(QueueHeartbeatJob::class, 1);
        $this->assertInstanceOf(ShouldBeUnique::class, new QueueHeartbeatJob);
    }

    public function test_scheduler_runs_the_queue_worker_every_minute(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command ?? '', 'queue:work'));

        $this->assertNotNull($event, 'queue:work is not scheduled');
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertStringContainsString('--stop-when-empty', $event->command);
    }
}
