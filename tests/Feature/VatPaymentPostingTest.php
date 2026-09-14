<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatPaymentPostingTest extends TestCase
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
            'name'                    => 'GL Test Co',
            'slug'                    => 'gl-test-co',
            'email'                   => 'gl@testco.ng',
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
            'email'             => 'admin@gltest.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($this->tenant);
    }

    private function makeFiledVatReturn(float $netVatPayable = 10000): VatReturn
    {
        return VatReturn::create([
            'tenant_id'         => $this->tenant->id,
            'tax_year'          => now()->year,
            'tax_month'         => now()->month,
            'period_start'      => now()->startOfMonth(),
            'period_end'        => now()->endOfMonth(),
            'output_vat'        => $netVatPayable,
            'input_vat'         => 0,
            'net_vat_payable'   => $netVatPayable,
            'due_date'          => now()->addDays(14),
            'status'            => 'filed',
            'filed_date'        => now(),
            'filing_reference'  => 'NRS-TEST-001',
            'filed_by'          => $this->admin->id,
        ]);
    }

    // ── VAT remittance posting ──────────────────────────────────────────────

    public function test_marking_a_filed_return_paid_requires_a_payment_account(): void
    {
        $vatReturn = $this->makeFiledVatReturn();

        $this->actingAs($this->admin)
            ->post(route('tax.vat.paid', $vatReturn), [
                'paid_date'   => now()->toDateString(),
                'amount_paid' => 10000,
            ])
            ->assertSessionHasErrors('payment_account');

        $this->assertEquals('filed', $vatReturn->fresh()->status);
    }

    public function test_marking_a_filed_return_paid_posts_a_balanced_journal_entry(): void
    {
        $vatReturn = $this->makeFiledVatReturn(10000);
        $cashAccount = Account::where('tenant_id', $this->tenant->id)->where('code', '1001')->firstOrFail();
        $vatPayable  = Account::where('tenant_id', $this->tenant->id)->where('code', '2100')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('tax.vat.paid', $vatReturn), [
                'paid_date'       => now()->toDateString(),
                'amount_paid'     => 10000,
                'payment_account' => $cashAccount->id,
            ])
            ->assertRedirect();

        $vatReturn->refresh();
        $this->assertEquals('paid', $vatReturn->status);
        $this->assertNotNull($vatReturn->transaction_id);

        $this->assertEquals(-10000.0, (float) $vatPayable->fresh()->current_balance);
        $this->assertEquals(-10000.0, (float) $cashAccount->fresh()->current_balance);

        $entries = JournalEntry::where('transaction_id', $vatReturn->transaction_id)->get();
        $this->assertEquals(
            $entries->where('entry_type', 'debit')->sum('amount'),
            $entries->where('entry_type', 'credit')->sum('amount')
        );
    }

    public function test_a_pending_return_cannot_be_marked_paid(): void
    {
        $vatReturn = $this->makeFiledVatReturn();
        $vatReturn->update(['status' => 'pending']);
        $cashAccount = Account::where('tenant_id', $this->tenant->id)->where('code', '1001')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('tax.vat.paid', $vatReturn), [
                'paid_date'       => now()->toDateString(),
                'amount_paid'     => 10000,
                'payment_account' => $cashAccount->id,
            ])
            ->assertRedirect();

        $this->assertEquals('pending', $vatReturn->fresh()->status);
    }

    // ── Input VAT posting on expense approval ───────────────────────────────

    private function makeExpense(float $amount, bool $vatApplicable): Expense
    {
        $expenseAccount = Account::where('tenant_id', $this->tenant->id)->where('code', '5500')->firstOrFail();
        $vatAmount = $vatApplicable ? round($amount * 7.5 / 107.5, 2) : 0;

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
            'status'         => 'pending',
            'created_by'     => $this->admin->id,
        ]);
    }

    public function test_approving_a_vat_applicable_expense_posts_input_vat_control(): void
    {
        $expense = $this->makeExpense(10750, vatApplicable: true); // 10000 net + 750 VAT

        $this->actingAs($this->admin)
            ->post(route('transactions.expenses.approve', $expense))
            ->assertRedirect();

        $expense->refresh();
        $this->assertEquals('approved', $expense->status);

        $inputVat = Account::where('tenant_id', $this->tenant->id)->where('code', '2101')->firstOrFail();
        $expenseAccount = Account::find($expense->account_id);

        $inputVatEntry = JournalEntry::where('transaction_id', $expense->transaction_id)
            ->where('account_id', $inputVat->id)->first();
        $this->assertNotNull($inputVatEntry);
        $this->assertEquals('debit', $inputVatEntry->entry_type);
        $this->assertEquals((float) $expense->vat_amount, (float) $inputVatEntry->amount);

        $expenseEntry = JournalEntry::where('transaction_id', $expense->transaction_id)
            ->where('account_id', $expenseAccount->id)->first();
        $this->assertEquals((float) $expense->amount - (float) $expense->vat_amount, (float) $expenseEntry->amount);

        $entries = JournalEntry::where('transaction_id', $expense->transaction_id)->get();
        $this->assertEquals(
            $entries->where('entry_type', 'debit')->sum('amount'),
            $entries->where('entry_type', 'credit')->sum('amount')
        );
    }

    public function test_approving_a_non_vat_expense_does_not_touch_input_vat_control(): void
    {
        $expense = $this->makeExpense(5000, vatApplicable: false);

        $this->actingAs($this->admin)
            ->post(route('transactions.expenses.approve', $expense))
            ->assertRedirect();

        $expense->refresh();

        $inputVat = Account::where('tenant_id', $this->tenant->id)->where('code', '2101')->firstOrFail();
        $this->assertEquals(0.0, (float) $inputVat->fresh()->current_balance);

        $expenseEntry = JournalEntry::where('transaction_id', $expense->transaction_id)
            ->where('account_id', $expense->account_id)->first();
        $this->assertEquals(5000.0, (float) $expenseEntry->amount);
    }
}
