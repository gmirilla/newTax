<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;
    private User   $staff;

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
            'name'                    => 'Test Trading Co',
            'slug'                    => 'test-trading-co-coa',
            'email'                   => 'coa@testtrading.ng',
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
            'email'             => 'admin@testtrading-coa.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->staff = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Staff User',
            'email'             => 'staff@testtrading-coa.ng',
            'password'          => bcrypt('password'),
            'role'              => 'staff',
            'is_active'         => true,
            'email_verified_at' => now(),
            'module_access'     => [],
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($this->tenant);
    }

    public function test_admin_sees_the_full_chart_of_accounts(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.chart-of-accounts.index'))
            ->assertOk()
            ->assertSee('Sales Revenue')
            ->assertSee('4001');

        $this->assertEquals(29, Account::where('tenant_id', $this->tenant->id)->count());
    }

    public function test_staff_cannot_reach_chart_of_accounts(): void
    {
        // Chart of Accounts sits behind role:admin (same as Bank Accounts) — staff
        // never reach the controller/policy at all, they're redirected first.
        $this->actingAs($this->staff)
            ->get(route('settings.chart-of-accounts.index'))
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_admin_can_create_a_custom_account_with_a_valid_code(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.chart-of-accounts.store'), [
                'name' => 'Equipment Lease Expense',
                'type' => 'expense',
                'code' => '5600',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('accounts', [
            'tenant_id' => $this->tenant->id,
            'code'      => '5600',
            'type'      => 'expense',
            'is_system' => false,
        ]);
    }

    public function test_creating_an_account_with_a_code_that_does_not_match_its_type_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.chart-of-accounts.store'), [
                'name' => 'Bad Code',
                'type' => 'asset',
                'code' => '4999',
            ])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('accounts', ['tenant_id' => $this->tenant->id, 'code' => '4999']);
    }

    public function test_creating_a_duplicate_code_for_the_same_tenant_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.chart-of-accounts.store'), [
                'name' => 'Duplicate',
                'type' => 'asset',
                'code' => '1001', // already exists as a default account
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_can_edit_a_custom_accounts_code_and_type(): void
    {
        $account = Account::create([
            'tenant_id' => $this->tenant->id,
            'code'      => '5600',
            'name'      => 'Custom Expense',
            'type'      => 'expense',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('settings.chart-of-accounts.update', $account), [
                'name' => 'Renamed Expense',
                'type' => 'expense',
                'code' => '5601',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $account->refresh();
        $this->assertEquals('Renamed Expense', $account->name);
        $this->assertEquals('5601', $account->code);
    }

    public function test_a_system_accounts_code_and_type_cannot_be_changed_even_if_submitted(): void
    {
        $account = Account::where('tenant_id', $this->tenant->id)->where('code', '4001')->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('settings.chart-of-accounts.update', $account), [
                'name'      => 'Sales Revenue (renamed)',
                'type'      => 'expense', // attempted tamper
                'code'      => '9999',    // attempted tamper
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $account->refresh();
        $this->assertEquals('Sales Revenue (renamed)', $account->name);
        $this->assertEquals('4001', $account->code);
        $this->assertEquals('revenue', $account->type);
    }

    public function test_admin_can_deactivate_a_system_account(): void
    {
        $account = Account::where('tenant_id', $this->tenant->id)->where('code', '4002')->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('settings.chart-of-accounts.update', $account), [
                'name'      => $account->name,
                'is_active' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse((bool) $account->fresh()->is_active);
    }

    public function test_admin_cannot_delete_a_system_account(): void
    {
        $account = Account::where('tenant_id', $this->tenant->id)->where('code', '4001')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('settings.chart-of-accounts.destroy', $account))
            ->assertForbidden();

        $this->assertNotNull($account->fresh());
    }

    public function test_admin_cannot_delete_a_custom_account_with_journal_entries(): void
    {
        $account = Account::create([
            'tenant_id' => $this->tenant->id,
            'code'      => '5600',
            'name'      => 'Custom Expense',
            'type'      => 'expense',
            'is_system' => false,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'tenant_id'        => $this->tenant->id,
            'reference'        => 'TEST-0001',
            'transaction_date' => now()->toDateString(),
            'type'             => 'journal',
            'amount'           => 5000,
            'currency'         => 'NGN',
            'status'           => 'posted',
            'created_by'       => $this->admin->id,
        ]);

        JournalEntry::create([
            'tenant_id'      => $this->tenant->id,
            'transaction_id' => $transaction->id,
            'account_id'     => $account->id,
            'entry_type'     => 'debit',
            'amount'         => 5000,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('settings.chart-of-accounts.destroy', $account))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotNull($account->fresh());
    }

    public function test_admin_can_delete_a_custom_account_with_no_journal_entries(): void
    {
        $account = Account::create([
            'tenant_id' => $this->tenant->id,
            'code'      => '5600',
            'name'      => 'Custom Expense',
            'type'      => 'expense',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('settings.chart-of-accounts.destroy', $account))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull(Account::find($account->id));
    }

    public function test_tenant_isolation_admin_cannot_touch_another_tenants_account(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Co', 'slug' => 'other-co-coa', 'email' => 'other@coa.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $this->tenant->plan_id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $otherAccount = Account::create([
            'tenant_id' => $otherTenant->id,
            'code'      => '5600',
            'name'      => 'Other Tenant Expense',
            'type'      => 'expense',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('settings.chart-of-accounts.update', $otherAccount), ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->delete(route('settings.chart-of-accounts.destroy', $otherAccount))
            ->assertForbidden();
    }
}
