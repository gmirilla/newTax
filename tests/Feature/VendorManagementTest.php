<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;
    private User   $staff;
    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name'          => 'Business',
            'slug'          => 'business',
            'price_monthly' => 25000,
            'limits'        => [],
            'is_active'     => true,
            'is_public'     => true,
        ]);

        $this->tenant = Tenant::create([
            'name'                    => 'Vendor Test Co',
            'slug'                    => 'vendor-test-co',
            'email'                   => 'vendors@testco.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 10_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $plan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $this->admin = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Admin User',
            'email'             => 'admin@vendortest.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->staff = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Staff User',
            'email'             => 'staff@vendortest.ng',
            'password'          => bcrypt('password'),
            'role'              => 'staff',
            'is_active'         => true,
            'email_verified_at' => now(),
            'module_access'     => [],
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($this->tenant);

        $this->vendor = Vendor::withoutGlobalScope('tenant')->create([
            'tenant_id'   => $this->tenant->id,
            'name'        => 'Zenith Supplies Ltd',
            'email'       => 'contact@zenith.ng',
            'vendor_type' => 'services',
            'wht_rate'    => 5.0,
            'is_active'   => true,
        ]);
    }

    public function test_admin_can_view_vendor_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('vendors.index'))
            ->assertOk()
            ->assertSee('Zenith Supplies Ltd');
    }

    public function test_admin_can_update_vendor_details(): void
    {
        $this->actingAs($this->admin)
            ->put(route('vendors.update', $this->vendor), [
                'name'              => 'Zenith Supplies Nigeria Ltd',
                'email'             => 'info@zenith.ng',
                'vendor_type'       => 'rent',
                'wht_rate'          => 10,
                'wht_exempt'        => '0',
                'is_active'         => '1',
            ])
            ->assertRedirect(route('vendors.index'));

        $this->vendor->refresh();
        $this->assertEquals('Zenith Supplies Nigeria Ltd', $this->vendor->name);
        $this->assertEquals('rent', $this->vendor->vendor_type);
        $this->assertEquals(10.0, (float) $this->vendor->wht_rate);
        $this->assertFalse($this->vendor->wht_exempt);
    }

    public function test_admin_can_mark_vendor_wht_exempt_with_reason(): void
    {
        $this->actingAs($this->admin)
            ->put(route('vendors.update', $this->vendor), [
                'name'              => $this->vendor->name,
                'vendor_type'       => 'services',
                'wht_rate'          => 5,
                'wht_exempt'        => '1',
                'wht_exempt_reason' => 'diplomatic',
                'is_active'         => '1',
            ])
            ->assertRedirect(route('vendors.index'));

        $this->vendor->refresh();
        $this->assertTrue($this->vendor->wht_exempt);
        $this->assertEquals('diplomatic', $this->vendor->wht_exempt_reason);
        $this->assertEquals(0.0, (float) $this->vendor->wht_rate);
    }

    public function test_staff_without_accountant_role_cannot_view_or_edit_vendors(): void
    {
        $this->actingAs($this->staff)->get(route('vendors.index'))->assertRedirect(route('staff.dashboard'));
        $this->actingAs($this->staff)->get(route('vendors.edit', $this->vendor))->assertRedirect(route('staff.dashboard'));
    }

    public function test_admin_can_delete_a_vendor_with_no_history(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('vendors.destroy', $this->vendor))
            ->assertRedirect();

        $this->assertSoftDeleted('vendors', ['id' => $this->vendor->id]);
    }

    public function test_deleting_a_vendor_with_expenses_is_blocked(): void
    {
        $account = Account::where('tenant_id', $this->tenant->id)->firstOrFail();

        Expense::withoutGlobalScope('tenant')->create([
            'tenant_id'    => $this->tenant->id,
            'vendor_id'    => $this->vendor->id,
            'account_id'   => $account->id,
            'reference'    => 'EXP-0001',
            'expense_date' => now(),
            'category'     => 'services',
            'description'  => 'Consulting services',
            'amount'       => 50000,
            'created_by'   => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('vendors.destroy', $this->vendor))
            ->assertRedirect();

        $this->assertDatabaseHas('vendors', ['id' => $this->vendor->id, 'deleted_at' => null]);
    }

    public function test_vendor_from_another_tenant_is_not_editable(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Co', 'slug' => 'other-co', 'email' => 'other@co.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $this->tenant->plan_id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $otherVendor = Vendor::withoutGlobalScope('tenant')->create([
            'tenant_id'   => $otherTenant->id,
            'name'        => 'Other Tenant Vendor',
            'vendor_type' => 'services',
            'wht_rate'    => 5.0,
            'is_active'   => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('vendors.edit', $otherVendor))
            ->assertForbidden();
    }
}
