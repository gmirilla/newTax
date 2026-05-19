<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PlanAccessTest extends TestCase
{
    use RefreshDatabase;

    private Plan $freePlan;
    private Plan $businessPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freePlan = Plan::create([
            'name'          => 'Free',
            'slug'          => 'free',
            'price_monthly' => 0,
            'limits'        => [
                'invoices_per_month' => 5,
                'users'              => 1,
                'inventory'          => false,
                'manufacturing'      => false,
                'payroll'            => false,
            ],
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->businessPlan = Plan::create([
            'name'          => 'Business',
            'slug'          => 'business',
            'price_monthly' => 25000,
            'limits'        => [
                'invoices_per_month' => null,
                'users'              => 10,
                'inventory'          => true,
                'manufacturing'      => false,
                'payroll'            => true,
            ],
            'is_active' => true,
            'is_public' => true,
        ]);
    }

    private function makeTenant(Plan $plan, string $slug, string $status = 'active'): Tenant
    {
        return Tenant::create([
            'name'                    => ucwords(str_replace('-', ' ', $slug)),
            'slug'                    => $slug,
            'email'                   => $slug . '@test.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 10_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $plan->id,
            'subscription_status'     => $status,
            'subscription_expires_at' => now()->addYear(),
        ]);
    }

    private function makeAdmin(Tenant $tenant, string $email): User
    {
        return User::forceCreate([
            'tenant_id'         => $tenant->id,
            'name'              => 'Admin',
            'email'             => $email,
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    // ── planAllows() ──────────────────────────────────────────────────────────

    public function test_plan_allows_inventory_on_business_plan(): void
    {
        $tenant = $this->makeTenant($this->businessPlan, 'biz-co');
        $this->assertTrue($tenant->planAllows('inventory'));
    }

    public function test_plan_denies_inventory_on_free_plan(): void
    {
        $tenant = $this->makeTenant($this->freePlan, 'free-co');
        $this->assertFalse($tenant->planAllows('inventory'));
    }

    public function test_plan_allows_returns_false_when_no_plan_assigned(): void
    {
        $tenant = Tenant::create([
            'name'            => 'No Plan Co',
            'slug'            => 'no-plan-co',
            'email'           => 'noplan@test.ng',
            'tax_category'    => 'small',
            'annual_turnover' => 5_000_000,
            'currency'        => 'NGN',
            'is_active'       => true,
        ]);

        $this->assertFalse($tenant->planAllows('inventory'));
    }

    public function test_plan_allows_returns_false_after_subscription_expires_past_grace(): void
    {
        // Expired more than 7 days ago — outside grace window
        $tenant = Tenant::create([
            'name'                    => 'Expired Co',
            'slug'                    => 'expired-co',
            'email'                   => 'expired@test.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 5_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $this->businessPlan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->subDays(10),
        ]);

        $this->assertFalse($tenant->planAllows('inventory'));
    }

    public function test_plan_allows_returns_true_within_grace_period(): void
    {
        // Expired 3 days ago — still within 7-day grace window
        $tenant = Tenant::create([
            'name'                    => 'Grace Co',
            'slug'                    => 'grace-co',
            'email'                   => 'grace@test.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 5_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $this->businessPlan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->subDays(3),
        ]);

        $this->assertTrue($tenant->planAllows('inventory'));
    }

    // ── withinLimit() ─────────────────────────────────────────────────────────

    public function test_within_limit_true_when_below_invoice_cap(): void
    {
        $tenant = $this->makeTenant($this->freePlan, 'free-limit-co');
        // No invoices yet — 0 < 5
        $this->assertTrue($tenant->withinLimit('invoices_per_month'));
    }

    public function test_within_limit_false_when_invoice_cap_reached(): void
    {
        $tenant = $this->makeTenant($this->freePlan, 'free-full-co');
        $admin  = $this->makeAdmin($tenant, 'admin@free-full.ng');

        for ($i = 0; $i < 5; $i++) {
            Invoice::create([
                'tenant_id'      => $tenant->id,
                'customer_id'    => null,
                'invoice_number' => 'INV-TEST-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'invoice_date'   => now()->toDateString(),
                'due_date'       => now()->addDays(30)->toDateString(),
                'subtotal'       => 1000,
                'vat_amount'     => 0,
                'discount_amount'=> 0,
                'total_amount'   => 1000,
                'amount_paid'    => 0,
                'balance_due'    => 1000,
                'vat_applicable' => false,
                'status'         => 'draft',
                'is_b2c'         => false,
                'currency'       => 'NGN',
                'created_by'     => $admin->id,
            ]);
        }

        Cache::flush(); // clear the 5-minute limit cache
        $this->assertFalse($tenant->withinLimit('invoices_per_month'));
    }

    public function test_null_limit_means_unlimited_invoices(): void
    {
        $tenant = $this->makeTenant($this->businessPlan, 'biz-unlimited-co');
        // Business plan has null for invoices_per_month → always within limit
        $this->assertTrue($tenant->withinLimit('invoices_per_month'));
    }

    // ── canAccess() ───────────────────────────────────────────────────────────

    public function test_admin_can_always_access_any_module(): void
    {
        $tenant = $this->makeTenant($this->businessPlan, 'biz-admin-co');
        $admin  = $this->makeAdmin($tenant, 'admin@biz-admin.ng');

        $this->assertTrue($admin->canAccess('inventory'));
        $this->assertTrue($admin->canAccess('manufacturing'));
        $this->assertTrue($admin->canAccess('payroll'));
        $this->assertTrue($admin->canAccess('reports'));
    }

    public function test_staff_with_no_module_access_defaults_to_all_off(): void
    {
        $tenant = $this->makeTenant($this->businessPlan, 'biz-staff-co');
        $staff  = User::create([
            'tenant_id'         => $tenant->id,
            'name'              => 'Warehouse Staff',
            'email'             => 'staff@biz-staff.ng',
            'password'          => bcrypt('password'),
            'role'              => 'staff',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        // Staff default is all modules off
        $this->assertFalse($staff->canAccess('inventory'));
        $this->assertFalse($staff->canAccess('payroll'));
        $this->assertFalse($staff->canAccess('reports'));
    }

    public function test_staff_module_access_can_be_selectively_granted(): void
    {
        $tenant = $this->makeTenant($this->businessPlan, 'biz-select-co');
        $staff  = User::create([
            'tenant_id'         => $tenant->id,
            'name'              => 'Partial Staff',
            'email'             => 'partial@biz-select.ng',
            'password'          => bcrypt('password'),
            'role'              => 'staff',
            'is_active'         => true,
            'email_verified_at' => now(),
            'module_access'     => ['inventory' => true, 'invoices' => false],
        ]);

        $this->assertTrue($staff->canAccess('inventory'));
        $this->assertFalse($staff->canAccess('invoices'));
    }

    public function test_accountant_defaults_to_all_modules_on(): void
    {
        $tenant    = $this->makeTenant($this->businessPlan, 'biz-acct-co');
        $accountant = User::create([
            'tenant_id'         => $tenant->id,
            'name'              => 'Accountant',
            'email'             => 'acct@biz-acct.ng',
            'password'          => bcrypt('password'),
            'role'              => 'accountant',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        // Accountant default is all modules on
        $this->assertTrue($accountant->canAccess('inventory'));
        $this->assertTrue($accountant->canAccess('payroll'));
    }
}
