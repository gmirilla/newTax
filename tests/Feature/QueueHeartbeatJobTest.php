<?php

namespace Tests\Feature;

use App\Jobs\QueueHeartbeatJob;
use App\Support\QueueHealth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QueueHeartbeatJobTest extends TestCase
{
    public function test_handle_sets_a_recent_heartbeat_timestamp(): void
    {
        Cache::forget(QueueHealth::CACHE_KEY);

        (new QueueHeartbeatJob)->handle();

        $heartbeat = QueueHealth::heartbeatAt();

        $this->assertNotNull($heartbeat);
        $this->assertTrue(now()->diffInSeconds($heartbeat, true) < 5);
        $this->assertFalse(QueueHealth::isPresumedDown());
    }

    public function test_heartbeat_is_stored_as_a_plain_timestamp_not_an_object(): void
    {
        (new QueueHeartbeatJob)->handle();

        // An object would come back as __PHP_Incomplete_Class from a serializing cache
        // (database store, cache.serializable_classes=false) in production.
        $this->assertIsInt(Cache::get(QueueHealth::CACHE_KEY));
    }

    public function test_a_legacy_serialized_carbon_value_is_treated_as_missing_not_a_crash(): void
    {
        // What production's database cache returns for an old Carbon entry.
        $legacy = unserialize(serialize(now()), ['allowed_classes' => false]);
        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $legacy);
        Cache::put(QueueHealth::CACHE_KEY, $legacy, 600);

        $this->assertNull(QueueHealth::heartbeatAt());
        $this->assertTrue(QueueHealth::isPresumedDown());

        // ...and the next real heartbeat recovers it.
        (new QueueHeartbeatJob)->handle();
        $this->assertFalse(QueueHealth::isPresumedDown());
    }

    public function test_a_stale_timestamp_is_presumed_down(): void
    {
        Cache::put(QueueHealth::CACHE_KEY, now()->subMinutes(QueueHealth::STALE_AFTER_MINUTES + 2)->getTimestamp(), 600);

        $this->assertTrue(QueueHealth::isPresumedDown());
    }
}
