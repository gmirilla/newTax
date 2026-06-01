<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $tenantAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::forceCreate([
            'name'              => 'Super Admin',
            'email'             => 'superadmin@platform.test',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_superadmin'     => true,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $plan = Plan::create([
            'name'          => 'Free',
            'slug'          => 'free',
            'price_monthly' => 0,
            'limits'        => [],
            'is_active'     => true,
            'is_public'     => true,
        ]);

        $tenant = Tenant::create([
            'name'                    => 'Test Co',
            'slug'                    => 'test-co',
            'email'                   => 'admin@testco.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 5_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $plan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $this->tenantAdmin = User::forceCreate([
            'tenant_id'         => $tenant->id,
            'name'              => 'Tenant Admin',
            'email'             => 'admin@testco.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_superadmin'     => false,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_superadmin_can_view_backups_page(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get(route('superadmin.backups.index'));

        // The page degrades gracefully when backup destinations are unreachable,
        // so we expect a 200 regardless of SFTP/disk availability in the test env.
        $response->assertOk();
        $response->assertViewIs('superadmin.backups.index');
    }

    public function test_tenant_admin_cannot_access_backups_page(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('superadmin.backups.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_backups_page(): void
    {
        $response = $this->get(route('superadmin.backups.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_run_now_dispatches_backup_job(): void
    {
        Artisan::shouldReceive('queue')
            ->once()
            ->with('backup:run --only-db');

        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.backups.run'));

        $response->assertRedirectToRoute('superadmin.backups.index');
        $response->assertSessionHas('success');
    }

    public function test_tenant_admin_cannot_trigger_backup(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->post(route('superadmin.backups.run'));

        $response->assertForbidden();
    }
}
