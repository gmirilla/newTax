<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueMonitorControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $tenantAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::forceCreate([
            'name' => 'Super Admin', 'email' => 'superadmin@platform.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_superadmin' => true,
            'is_active' => true, 'email_verified_at' => now(),
        ]);

        $plan = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'price_monthly' => 0, 'limits' => [],
            'is_active' => true, 'is_public' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Test Co', 'slug' => 'test-co', 'email' => 'admin@testco.ng',
            'tax_category' => 'small', 'annual_turnover' => 5_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $this->tenantAdmin = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => 'Tenant Admin', 'email' => 'admin@testco.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_superadmin' => false,
            'is_active' => true, 'email_verified_at' => now(),
        ]);
    }

    private function seedFailedJob(): string
    {
        $uuid = (string) Str::uuid();

        DB::table('failed_jobs')->insert([
            'uuid'       => $uuid,
            'connection' => 'database',
            'queue'      => 'default',
            'payload'    => json_encode(['uuid' => $uuid]),
            'exception'  => "RuntimeException: something went wrong\nStack trace...",
            'failed_at'  => now(),
        ]);

        return $uuid;
    }

    public function test_superadmin_can_view_queue_page(): void
    {
        $this->seedFailedJob();

        $response = $this->actingAs($this->superadmin)->get(route('superadmin.queue.index'));

        $response->assertOk();
        $response->assertViewIs('superadmin.queue.index');
    }

    public function test_tenant_admin_cannot_view_queue_page(): void
    {
        $response = $this->actingAs($this->tenantAdmin)->get(route('superadmin.queue.index'));

        $response->assertForbidden();
    }

    public function test_fresh_heartbeat_renders_as_healthy(): void
    {
        Cache::put('queue.worker.heartbeat_at', now(), now()->addMinutes(10));

        $response = $this->actingAs($this->superadmin)->get(route('superadmin.queue.index'));

        $response->assertOk();
        $response->assertViewHas('status', fn ($status) => $status['worker_presumed_down'] === false);
    }

    public function test_stale_heartbeat_renders_as_presumed_down(): void
    {
        Cache::put('queue.worker.heartbeat_at', now()->subMinutes(10), now()->addMinutes(10));

        $response = $this->actingAs($this->superadmin)->get(route('superadmin.queue.index'));

        $response->assertOk();
        $response->assertViewHas('status', fn ($status) => $status['worker_presumed_down'] === true);
    }

    public function test_retry_calls_queue_retry_with_the_uuid(): void
    {
        $uuid = $this->seedFailedJob();

        Artisan::shouldReceive('call')->once()->with('queue:retry', ['id' => [$uuid]]);

        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.queue.retry', $uuid));

        $response->assertRedirectToRoute('superadmin.queue.index');
        $response->assertSessionHas('success');
    }

    public function test_destroy_calls_queue_forget_with_the_uuid(): void
    {
        $uuid = $this->seedFailedJob();

        Artisan::shouldReceive('call')->once()->with('queue:forget', ['id' => $uuid]);

        $response = $this->actingAs($this->superadmin)
            ->delete(route('superadmin.queue.destroy', $uuid));

        $response->assertRedirectToRoute('superadmin.queue.index');
        $response->assertSessionHas('success');
    }

    public function test_restart_workers_sends_the_honest_caveat_message(): void
    {
        Artisan::shouldReceive('call')->once()->with('queue:restart');

        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.queue.restart-workers'));

        $response->assertRedirectToRoute('superadmin.queue.index');
        $response->assertSessionHas('success', function ($message) {
            return str_contains($message, 'process supervisor');
        });
    }

    public function test_tenant_admin_cannot_trigger_queue_actions(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->post(route('superadmin.queue.restart-workers'));

        $response->assertForbidden();
    }
}
