<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillExpensePaymentLinksTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Business', 'slug' => 'business', 'price_monthly' => 25000,
            'limits' => [], 'is_active' => true, 'is_public' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Backfill Test Co', 'slug' => 'backfill-test-co', 'email' => 'backfill@testco.ng',
            'tax_category' => 'small', 'annual_turnover' => 10_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $this->admin = User::forceCreate([
            'tenant_id' => $this->tenant->id, 'name' => 'Admin User', 'email' => 'admin@backfilltest.ng',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(),
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($this->tenant);
    }

    private function makeLegacyPaidExpense(float $netPayable = 10_000): Expense
    {
        $account = Account::where('tenant_id', $this->tenant->id)->where('code', '5500')->firstOrFail();

        return Expense::withoutGlobalScope('tenant')->create([
            'tenant_id'      => $this->tenant->id,
            'account_id'     => $account->id,
            'reference'      => 'EXP-' . uniqid(),
            'expense_date'   => now(),
            'category'       => 'other',
            'description'    => 'Legacy paid expense',
            'amount'         => $netPayable,
            'vat_applicable' => false,
            'vat_amount'     => 0,
            'net_payable'    => $netPayable,
            'status'         => 'paid',
            'payment_transaction_id' => null,
            'created_by'     => $this->admin->id,
        ]);
    }

    private function makeLegacyPaymentTransaction(Expense $expense, float $amount, bool $withJournalEntries = false): Transaction
    {
        $transaction = Transaction::create([
            'tenant_id'        => $this->tenant->id,
            'reference'        => 'PAY-' . $expense->reference,
            'transaction_date' => now()->toDateString(),
            'type'             => 'payment',
            'amount'           => $amount,
            'currency'         => 'NGN',
            'description'      => "Expense paid: {$expense->reference}",
            'status'           => 'posted',
            'created_by'       => $this->admin->id,
        ]);

        if ($withJournalEntries) {
            $ap   = Account::where('tenant_id', $this->tenant->id)->where('code', '2001')->firstOrFail();
            $cash = Account::where('tenant_id', $this->tenant->id)->where('code', '1001')->firstOrFail();

            JournalEntry::create([
                'tenant_id' => $this->tenant->id, 'transaction_id' => $transaction->id,
                'account_id' => $ap->id, 'entry_type' => 'debit', 'amount' => $amount,
                'description' => "Settle payable: {$expense->reference}",
            ]);
            JournalEntry::create([
                'tenant_id' => $this->tenant->id, 'transaction_id' => $transaction->id,
                'account_id' => $cash->id, 'entry_type' => 'credit', 'amount' => $amount,
                'description' => "Payment: {$expense->reference}",
            ]);
        }

        return $transaction;
    }

    public function test_dry_run_reports_a_match_without_writing_it(): void
    {
        $expense = $this->makeLegacyPaidExpense(10_000);
        $transaction = $this->makeLegacyPaymentTransaction($expense, 10_000);

        $this->artisan('expenses:backfill-payment-links')
            ->assertExitCode(0);

        $this->assertNull($expense->fresh()->payment_transaction_id);
    }

    public function test_apply_links_a_matching_transaction(): void
    {
        $expense = $this->makeLegacyPaidExpense(10_000);
        $transaction = $this->makeLegacyPaymentTransaction($expense, 10_000);

        $this->artisan('expenses:backfill-payment-links', ['--apply' => true])
            ->assertExitCode(0);

        $this->assertEquals($transaction->id, $expense->fresh()->payment_transaction_id);
    }

    public function test_an_expense_with_no_matching_transaction_is_left_unlinked(): void
    {
        $expense = $this->makeLegacyPaidExpense(10_000);
        // No matching transaction created.

        $this->artisan('expenses:backfill-payment-links', ['--apply' => true])
            ->assertExitCode(0);

        $this->assertNull($expense->fresh()->payment_transaction_id);
    }

    public function test_an_amount_mismatch_is_skipped_for_safety(): void
    {
        $expense = $this->makeLegacyPaidExpense(10_000);
        $this->makeLegacyPaymentTransaction($expense, 5_000); // wrong amount

        $this->artisan('expenses:backfill-payment-links', ['--apply' => true])
            ->assertExitCode(0);

        $this->assertNull($expense->fresh()->payment_transaction_id);
    }

    public function test_an_already_linked_expense_is_ignored_on_rerun(): void
    {
        $expense = $this->makeLegacyPaidExpense(10_000);
        $transaction = $this->makeLegacyPaymentTransaction($expense, 10_000);
        $expense->update(['payment_transaction_id' => $transaction->id]);

        $this->artisan('expenses:backfill-payment-links', ['--apply' => true])
            ->assertExitCode(0);

        $this->assertEquals($transaction->id, $expense->fresh()->payment_transaction_id);
    }

    public function test_linked_expense_can_then_have_its_payment_reversed(): void
    {
        $expense = $this->makeLegacyPaidExpense(10_000);
        $this->makeLegacyPaymentTransaction($expense, 10_000, withJournalEntries: true);

        $this->artisan('expenses:backfill-payment-links', ['--apply' => true]);
        $expense->refresh();
        $this->assertNotNull($expense->payment_transaction_id);

        // Previously this would have been refused with "reverse it manually in the ledger".
        $this->actingAs($this->admin)
            ->post(route('transactions.expenses.unpay', $expense))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertEquals('approved', $expense->fresh()->status);
    }
}
