<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DatabaseExportTest extends TestCase
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

        $plan   = Plan::create([
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

    public function test_superadmin_can_view_export_index(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get(route('superadmin.database.export'));

        $response->assertOk();
        $response->assertViewIs('superadmin.database.export');
        $response->assertViewHas('tables');
        $response->assertViewHas('rowCounts');
    }

    public function test_tenant_admin_cannot_access_export_page(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('superadmin.database.export'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_export_page(): void
    {
        $response = $this->get(route('superadmin.database.export'));

        $response->assertRedirect(route('login'));
    }

    public function test_export_post_returns_zip_stream(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.database.export.download'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/zip');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.zip', $response->headers->get('Content-Disposition'));
    }

    public function test_export_creates_audit_log(): void
    {
        $this->actingAs($this->superadmin)
            ->post(route('superadmin.database.export.download'));

        $this->assertDatabaseHas('audit_logs', [
            'event'          => 'superadmin.database_exported',
            'auditable_type' => User::class,
            'auditable_id'   => $this->superadmin->id,
        ]);
    }

    public function test_export_index_uses_cached_row_counts(): void
    {
        Cache::put('superadmin_export_row_counts', ['plans' => 99], 300);

        $response = $this->actingAs($this->superadmin)
            ->get(route('superadmin.database.export'));

        $response->assertOk();
        $this->assertSame(['plans' => 99], $response->viewData('rowCounts'));
    }
}
