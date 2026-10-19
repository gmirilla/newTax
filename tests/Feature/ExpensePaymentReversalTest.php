<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BookkeepingService;
use App\Services\VatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensePaymentReversalTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;

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
            'name'                    => 'Reversal Test Co',
            'slug'                    => 'reversal-test-co',
            'email'                   => 'reversal@testco.ng',
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
            'email'             => 'admin@reversaltest.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($this->tenant);
    }

    private function makeApprovedExpense(float $amount = 10_000): Expense
    {
        $expenseAccount = Account::where('tenant_id', $this->tenant->id)->where('code', '5500')->firstOrFail();

        $expense = Expense::withoutGlobalScope('tenant')->create([
            'tenant_id'      => $this->tenant->id,
            'account_id'     => $expenseAccount->id,
            'reference'      => 'EXP-' . uniqid(),
            'expense_date'   => now(),
            'category'       => 'other',
            'description'    => 'Test expense',
            'amount'         => $amount,
            'vat_applicable' => false,
            'vat_amount'     => 0,
            'net_payable'    => $amount,
            'status'         => 'pending',
            'created_by'     => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('transactions.expenses.approve', $expense));

        return $expense->fresh();
    }

    private function makePendingExpense(float $amount, bool $vatApplicable = false, float $vatAmount = 0): Expense
    {
        $expenseAccount = Account::where('tenant_id', $this->tenant->id)->where('code', '5500')->firstOrFail();

        return Expense::withoutGlobalScope('tenant')->create([
            'tenant_id'      => $this->tenant->id,
            'account_id'     => $expenseAccount->id,
            'reference'      => 'EXP-' . uniqid(),
            'expense_date'   => now(),
            'category'       => 'other',
            'description'    => 'Test expense',
            'amount'         => $amount,
            'vat_applicable' => $vatApplicable,
            'vat_amount'     => $vatAmount,
            'net_payable'    => $amount,
            'status'         => 'pending',
            'created_by'     => $this->admin->id,
        ]);
    }

    private function payExpense(Expense $expense, ?Account $cashAccount = null)
    {
        $cashAccount ??= Account::where('tenant_id', $this->tenant->id)->where('code', '1001')->firstOrFail();

        return $this->actingAs($this->admin)
            ->post(route('transactions.expenses.pay', $expense), [
                'payment_date'    => now()->toDateString(),
                'payment_account' => $cashAccount->id,
            ]);
    }

    public function test_paying_then_reversing_restores_balances_and_status(): void
    {
        $expense = $this->makeApprovedExpense(10_000);
        $cash    = Account::where('tenant_id', $this->tenant->id)->where('code', '1001')->firstOrFail();
        $ap      = Account::where('tenant_id', $this->tenant->id)->where('code', '2001')->firstOrFail();

        // Approval already credited Accounts Payable by 10,000; paying debits
        // it straight back to zero (settled) and moves the cash out.
        $this->payExpense($expense, $cash)->assertRedirect();
        $expense->refresh();
        $this->assertEquals('paid', $expense->status);
        $this->assertNotNull($expense->payment_transaction_id);
        $this->assertEquals(0.0, (float) $ap->fresh()->current_balance);
        $this->assertEquals(-10_000.0, (float) $cash->fresh()->current_balance);

        // Reversing the payment restores the still-owed payable and the cash.
        $this->actingAs($this->admin)
            ->post(route('transactions.expenses.unpay', $expense))
            ->assertRedirect();

        $expense->refresh();
        $this->assertEquals('approved', $expense->status);
        $this->assertNull($expense->payment_transaction_id);
        $this->assertEquals(10_000.0, (float) $ap->fresh()->current_balance);
        $this->assertEquals(0.0, (float) $cash->fresh()->current_balance);

        $entries = JournalEntry::where('description', 'like', 'Reversal:%')->get();
        $this->assertCount(2, $entries);
        $this->assertEquals(
            $entries->where('entry_type', 'debit')->sum('amount'),
            $entries->where('entry_type', 'credit')->sum('amount')
        );
    }

    public function test_reversing_a_non_paid_expense_is_rejected(): void
    {
        $expense = $this->makeApprovedExpense(10_000);

        $this->actingAs($this->admin)
            ->post(route('transactions.expenses.unpay', $expense))
            ->assertRedirect();

        $this->assertEquals('approved', $expense->fresh()->status);
    }

    public function test_reversing_a_legacy_paid_expense_without_payment_transaction_is_rejected(): void
    {
        $expense = $this->makeApprovedExpense(10_000);
        $this->payExpense($expense);
        $expense->refresh();

        // Simulate a pre-migration paid expense with no tracked settlement transaction.
        $expense->update(['payment_transaction_id' => null]);

        $this->actingAs($this->admin)
            ->post(route('transactions.expenses.unpay', $expense))
            ->assertSessionHas('error');

        $this->assertEquals('paid', $expense->fresh()->status);
    }

    public function test_an_expense_can_be_paid_again_after_its_payment_is_reversed(): void
    {
        $expense = $this->makeApprovedExpense(10_000);
        $this->payExpense($expense);
        $expense->refresh();
        $firstTransactionId = $expense->payment_transaction_id;

        $this->actingAs($this->admin)->post(route('transactions.expenses.unpay', $expense));
        $expense->refresh();

        $this->payExpense($expense)->assertRedirect();
        $expense->refresh();

        $this->assertEquals('paid', $expense->status);
        $this->assertNotNull($expense->payment_transaction_id);
        $this->assertNotEquals($firstTransactionId, $expense->payment_transaction_id);
    }

    // ── Approval reversal ("unapprove") ─────────────────────────────────────

    public function test_reversing_an_approved_expense_restores_pending_and_balances(): void
    {
        $expense = $this->makePendingExpense(10_750, vatApplicable: true, vatAmount: 750);
        $this->actingAs($this->admin)->post(route('transactions.expenses.approve', $expense));
        $expense->refresh();

        $expenseAccount = Account::find($expense->account_id);
        $inputVat = Account::where('tenant_id', $this->tenant->id)->where('code', '2101')->firstOrFail();
        $ap       = Account::where('tenant_id', $this->tenant->id)->where('code', '2001')->firstOrFail();

        // Input VAT Control (2101) is a liability-type account that only ever
        // gets debited here (contra-liability), so its balance moves negative.
        $this->assertEquals(10_000.0, (float) $expenseAccount->fresh()->current_balance);
        $this->assertEquals(-750.0, (float) $inputVat->fresh()->current_balance);
        $this->assertEquals(10_750.0, (float) $ap->fresh()->current_balance);

        $this->actingAs($this->admin)
            ->post(route('transactions.expenses.unapprove', $expense))
            ->assertRedirect();

        $expense->refresh();
        $this->assertEquals('pending', $expense->status);
        $this->assertNull($expense->approved_by);
        $this->assertNull($expense->transaction_id);

        $this->assertEquals(0.0, (float) $expenseAccount->fresh()->current_balance);
        $this->assertEquals(0.0, (float) $inputVat->fresh()->current_balance);
        $this->assertEquals(0.0, (float) $ap->fresh()->current_balance);

        $entries = JournalEntry::where('description', 'like', 'Reversal:%')->get();
        $this->assertEquals(
            $entries->where('entry_type', 'debit')->sum('amount'),
            $entries->where('entry_type', 'credit')->sum('amount')
        );
    }

    public function test_reversing_approval_is_rejected_unless_status_is_approved(): void
    {
        $pending = $this->makePendingExpense(10_000);
        $this->actingAs($this->admin)
            ->post(route('transactions.expenses.unapprove', $pending))
            ->assertRedirect();
        $this->assertEquals('pending', $pending->fresh()->status);

        $paid = $this->makeApprovedExpense(10_000);
        $this->payExpense($paid);
        $this->actingAs($this->admin)
            ->post(route('transactions.expenses.unapprove', $paid))
            ->assertRedirect();
        $this->assertEquals('paid', $paid->fresh()->status);
    }

    public function test_unapproving_an_expense_removes_it_from_input_vat_recompute(): void
    {
        $expense = $this->makePendingExpense(10_750, vatApplicable: true, vatAmount: 750);
        $this->actingAs($this->admin)->post(route('transactions.expenses.approve', $expense));
        $expense->refresh();

        $vatService = app(VatService::class);
        $before = $vatService->computeMonthlyReturn($this->tenant, now()->year, now()->month);
        $this->assertEquals(750.0, $before['input_vat']);

        $this->actingAs($this->admin)->post(route('transactions.expenses.unapprove', $expense));

        $after = $vatService->computeMonthlyReturn($this->tenant, now()->year, now()->month);
        $this->assertEquals(0.0, $after['input_vat']);
    }

    public function test_an_expense_can_be_approved_again_after_its_approval_is_reversed(): void
    {
        $expense = $this->makePendingExpense(10_000);
        $this->actingAs($this->admin)->post(route('transactions.expenses.approve', $expense));
        $expense->refresh();
        $firstTransactionId = $expense->transaction_id;

        $this->actingAs($this->admin)->post(route('transactions.expenses.unapprove', $expense));
        $expense->refresh();

        $this->actingAs($this->admin)
            ->post(route('transactions.expenses.approve', $expense))
            ->assertRedirect();
        $expense->refresh();

        $this->assertEquals('approved', $expense->status);
        $this->assertNotNull($expense->transaction_id);
        $this->assertNotEquals($firstTransactionId, $expense->transaction_id);
    }
}
