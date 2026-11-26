<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\MaintenanceAsset;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BookkeepingService;
use App\Services\MaintenanceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests: a user of tenant B must never be able to read or change
 * tenant A's ledger, expenses, transactions or maintenance assets.
 */
class CrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'slug' => 'business', 'name' => 'Business', 'price_monthly' => 0, 'is_active' => true,
            'limits' => ['maintenance' => true],
        ]);
    }

    private function makeTenant(string $name): array
    {
        $tenant = Tenant::create([
            'name' => $name, 'slug' => strtolower($name) . '-' . uniqid(), 'email' => strtolower($name) . '@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $this->plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);
        app(BookkeepingService::class)->provisionDefaultAccounts($tenant);

        $user = User::forceCreate([
            'tenant_id' => $tenant->id, 'name' => $name . ' Admin', 'email' => strtolower($name) . '-' . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);

        return [$tenant, $user];
    }

    private function account(Tenant $tenant, string $type): Account
    {
        return Account::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('type', $type)->firstOrFail();
    }

    private function makeExpense(Tenant $tenant, User $user, Account $account): Expense
    {
        return Expense::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id, 'account_id' => $account->id, 'reference' => 'EXP-' . uniqid(),
            'expense_date' => now()->toDateString(), 'category' => 'office', 'description' => 'Paper',
            'amount' => 1000, 'net_payable' => 1000, 'status' => 'pending', 'created_by' => $user->id,
        ]);
    }

    public function test_user_cannot_edit_update_or_delete_another_tenants_expense(): void
    {
        [$tenantA, $userA] = $this->makeTenant('Alpha');
        [, $userB]         = $this->makeTenant('Beta');
        $expense = $this->makeExpense($tenantA, $userA, $this->account($tenantA, 'expense'));

        $this->actingAs($userB)->get(route('transactions.expenses.edit', $expense->id))->assertNotFound();

        $this->actingAs($userB)->put(route('transactions.expenses.update', $expense->id), [
            'expense_date' => now()->toDateString(), 'account_id' => $expense->account_id,
            'category' => 'x', 'description' => 'hacked', 'amount' => 5,
        ])->assertNotFound();

        $this->actingAs($userB)->delete(route('transactions.expenses.destroy', $expense->id))->assertNotFound();

        $fresh = Expense::withoutGlobalScope('tenant')->find($expense->id);
        $this->assertNotNull($fresh);
        $this->assertSame('Paper', $fresh->description);
    }

    public function test_user_cannot_approve_another_tenants_expense(): void
    {
        [$tenantA, $userA] = $this->makeTenant('Alpha');
        [, $userB]         = $this->makeTenant('Beta');
        $expense = $this->makeExpense($tenantA, $userA, $this->account($tenantA, 'expense'));

        $this->actingAs($userB)->post(route('transactions.expenses.approve', $expense->id))->assertNotFound();

        $this->assertSame('pending', Expense::withoutGlobalScope('tenant')->find($expense->id)->status);
    }

    public function test_user_can_still_edit_own_expense(): void
    {
        [$tenantA, $userA] = $this->makeTenant('Alpha');
        $expense = $this->makeExpense($tenantA, $userA, $this->account($tenantA, 'expense'));

        $this->actingAs($userA)->get(route('transactions.expenses.edit', $expense->id))->assertOk();
    }

    public function test_user_cannot_view_another_tenants_transaction(): void
    {
        [$tenantA, $userA] = $this->makeTenant('Alpha');
        [, $userB]         = $this->makeTenant('Beta');

        $this->actingAs($userA); // services stamp created_by from the authenticated user
        $txn = app(BookkeepingService::class)->postJournalEntry($tenantA, [
            'transaction_date' => now()->toDateString(), 'type' => 'journal', 'description' => 'Secret',
        ], [
            ['account_id' => $this->account($tenantA, 'asset')->id,   'entry_type' => 'debit',  'amount' => 100],
            ['account_id' => $this->account($tenantA, 'revenue')->id, 'entry_type' => 'credit', 'amount' => 100],
        ]);

        $this->actingAs($userB)->get(route('transactions.show', $txn->id))->assertNotFound();
        $this->actingAs($userA)->get(route('transactions.show', $txn->id))->assertOk();
    }

    public function test_journal_posting_to_another_tenants_account_is_rejected_over_http(): void
    {
        [$tenantA]       = $this->makeTenant('Alpha');
        [$tenantB, $userB] = $this->makeTenant('Beta');
        $foreign  = $this->account($tenantA, 'asset');
        $own      = $this->account($tenantB, 'revenue');
        $before   = (string) $foreign->current_balance;

        $response = $this->actingAs($userB)->post(route('transactions.store'), [
            'transaction_date' => now()->toDateString(), 'type' => 'journal', 'description' => 'Attack',
            'entries' => [
                ['account_id' => $foreign->id, 'entry_type' => 'debit',  'amount' => 500],
                ['account_id' => $own->id,     'entry_type' => 'credit', 'amount' => 500],
            ],
        ]);

        $response->assertSessionHasErrors('entries.0.account_id');
        $this->assertSame($before, (string) Account::withoutGlobalScope('tenant')->find($foreign->id)->current_balance);
        $this->assertSame(0, Transaction::withoutGlobalScope('tenant')->where('tenant_id', $tenantB->id)->count());
    }

    public function test_bookkeeping_service_refuses_foreign_accounts_even_without_http_validation(): void
    {
        [$tenantA]  = $this->makeTenant('Alpha');
        [$tenantB]  = $this->makeTenant('Beta');
        $foreign = $this->account($tenantA, 'asset');
        $own     = $this->account($tenantB, 'revenue');
        $before  = (string) $foreign->current_balance;

        try {
            app(BookkeepingService::class)->postJournalEntry($tenantB, [
                'transaction_date' => now()->toDateString(), 'type' => 'journal', 'description' => 'Attack',
            ], [
                ['account_id' => $foreign->id, 'entry_type' => 'debit',  'amount' => 500],
                ['account_id' => $own->id,     'entry_type' => 'credit', 'amount' => 500],
            ]);
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('do not belong', $e->getMessage());
        }

        $this->assertSame($before, (string) Account::withoutGlobalScope('tenant')->find($foreign->id)->current_balance);
        $this->assertSame(0, Transaction::withoutGlobalScope('tenant')->where('tenant_id', $tenantB->id)->count());
    }

    public function test_own_journal_posting_balances_and_updates_accounts(): void
    {
        [$tenantA, $userA] = $this->makeTenant('Alpha');
        $this->actingAs($userA);
        $asset   = $this->account($tenantA, 'asset');
        $revenue = $this->account($tenantA, 'revenue');
        $assetBefore = (float) $asset->current_balance;

        $txn = app(BookkeepingService::class)->postJournalEntry($tenantA, [
            'transaction_date' => now()->toDateString(), 'type' => 'journal', 'description' => 'Sale',
        ], [
            ['account_id' => $asset->id,   'entry_type' => 'debit',  'amount' => 250],
            ['account_id' => $revenue->id, 'entry_type' => 'credit', 'amount' => 250],
        ]);

        $debits  = $txn->journalEntries->where('entry_type', 'debit')->sum('amount');
        $credits = $txn->journalEntries->where('entry_type', 'credit')->sum('amount');
        $this->assertEquals($debits, $credits);
        $this->assertEquals($assetBefore + 250,Account::withoutGlobalScope('tenant')->find($asset->id)->current_balance);
    }

    private function makeAsset(Tenant $tenant, User $user): MaintenanceAsset
    {
        return MaintenanceAsset::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id, 'asset_code' => 'AST-' . uniqid(), 'asset_name' => 'Generator',
            'status' => MaintenanceAsset::STATUS_ACTIVE, 'created_by' => $user->id,
        ]);
    }

    public function test_breakdown_cannot_target_another_tenants_asset_over_http(): void
    {
        [$tenantA, $userA] = $this->makeTenant('Alpha');
        [, $userB]         = $this->makeTenant('Beta');
        $asset = $this->makeAsset($tenantA, $userA);

        $response = $this->actingAs($userB)->post(route('maintenance.breakdowns.store'), [
            'asset_id' => $asset->id, 'issue_description' => 'Smoke', 'severity' => 'high',
            'downtime_start' => now()->toDateTimeString(),
        ]);

        $response->assertSessionHasErrors('asset_id');
        $this->assertSame(MaintenanceAsset::STATUS_ACTIVE, MaintenanceAsset::withoutGlobalScope('tenant')->find($asset->id)->status);
    }

    public function test_maintenance_service_refuses_another_tenants_asset(): void
    {
        [$tenantA, $userA] = $this->makeTenant('Alpha');
        [$tenantB, $userB] = $this->makeTenant('Beta');
        $asset = $this->makeAsset($tenantA, $userA);

        $this->expectException(ModelNotFoundException::class);

        try {
            app(MaintenanceService::class)->reportBreakdown($tenantB, [
                'asset_id' => $asset->id, 'reported_by' => $userB->id, 'issue_description' => 'Smoke',
            ]);
        } finally {
            $this->assertSame(MaintenanceAsset::STATUS_ACTIVE, MaintenanceAsset::withoutGlobalScope('tenant')->find($asset->id)->status);
        }
    }
}
