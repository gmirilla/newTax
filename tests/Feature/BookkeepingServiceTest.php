<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookkeepingServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;
    private BookkeepingService $bookkeeping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'            => 'Test Company',
            'slug'            => 'test-company',
            'email'           => 'test@company.ng',
            'tax_category'    => 'small',
            'annual_turnover' => 10_000_000,
            'currency'        => 'NGN',
            'is_active'       => true,
        ]);

        $this->admin = User::create([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Test Admin',
            'email'             => 'admin@test.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->admin);
        $this->bookkeeping = app(BookkeepingService::class);
        $this->bookkeeping->provisionDefaultAccounts($this->tenant);
    }

    private function account(string $code): Account
    {
        return Account::where('tenant_id', $this->tenant->id)->where('code', $code)->firstOrFail();
    }

    public function test_post_journal_entry_creates_transaction_and_entries(): void
    {
        $inventory = $this->account('1200');
        $ap        = $this->account('2001');

        $transaction = $this->bookkeeping->postJournalEntry(
            $this->tenant,
            [
                'transaction_date' => now()->toDateString(),
                'type'             => 'purchase',
                'description'      => 'Stock received: RST-202505-0001',
                'reference'        => 'BILL-202505-0001',
            ],
            [
                ['account_id' => $inventory->id, 'entry_type' => 'debit',  'amount' => 10000, 'description' => 'Inventory in'],
                ['account_id' => $ap->id,        'entry_type' => 'credit', 'amount' => 10000, 'description' => 'AP created'],
            ]
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(10000, $transaction->amount);
        $this->assertEquals('purchase', $transaction->type);
        $this->assertEquals('posted', $transaction->status);

        $entries = JournalEntry::where('transaction_id', $transaction->id)->get();
        $this->assertCount(2, $entries);
        $this->assertEquals(10000, $entries->where('entry_type', 'debit')->sum('amount'));
        $this->assertEquals(10000, $entries->where('entry_type', 'credit')->sum('amount'));
    }

    public function test_post_journal_entry_throws_on_imbalanced_entries(): void
    {
        $inventory = $this->account('1200');
        $ap        = $this->account('2001');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/imbalanced/i');

        $this->bookkeeping->postJournalEntry(
            $this->tenant,
            ['transaction_date' => now()->toDateString(), 'type' => 'purchase', 'description' => 'Bad entry'],
            [
                ['account_id' => $inventory->id, 'entry_type' => 'debit',  'amount' => 10000, 'description' => 'Dr'],
                ['account_id' => $ap->id,        'entry_type' => 'credit', 'amount' => 9999,  'description' => 'Cr (wrong)'],
            ]
        );
    }

    public function test_multiple_debit_lines_balance_against_single_credit(): void
    {
        $inventory1 = $this->account('1200');
        $equity     = $this->account('3001');

        $transaction = $this->bookkeeping->postJournalEntry(
            $this->tenant,
            ['transaction_date' => now()->toDateString(), 'type' => 'adjustment', 'description' => 'Opening stock split'],
            [
                ['account_id' => $inventory1->id, 'entry_type' => 'debit',  'amount' => 3000, 'description' => 'Stock A'],
                ['account_id' => $inventory1->id, 'entry_type' => 'debit',  'amount' => 7000, 'description' => 'Stock B'],
                ['account_id' => $equity->id,     'entry_type' => 'credit', 'amount' => 10000,'description' => 'Equity offset'],
            ]
        );

        $this->assertEquals(10000, $transaction->amount);
    }

    public function test_provision_default_accounts_creates_standard_chart(): void
    {
        $codes = Account::where('tenant_id', $this->tenant->id)
            ->pluck('code')
            ->toArray();

        // Spot-check critical accounts
        foreach (['1100', '1200', '2001', '2100', '3001', '4001'] as $code) {
            $this->assertContains($code, $codes, "Account {$code} not provisioned");
        }
    }

    public function test_journal_entries_are_tenant_isolated(): void
    {
        // Second tenant
        $tenant2 = Tenant::create([
            'name' => 'Other Company', 'slug' => 'other-co', 'email' => 'other@co.ng',
            'tax_category' => 'small', 'annual_turnover' => 5_000_000, 'currency' => 'NGN', 'is_active' => true,
        ]);
        $this->bookkeeping->provisionDefaultAccounts($tenant2);

        $inv1 = $this->account('1200');
        $ap1  = $this->account('2001');

        $this->bookkeeping->postJournalEntry(
            $this->tenant,
            ['transaction_date' => now()->toDateString(), 'type' => 'purchase', 'description' => 'Tenant 1 purchase'],
            [
                ['account_id' => $inv1->id, 'entry_type' => 'debit',  'amount' => 5000, 'description' => 'Dr'],
                ['account_id' => $ap1->id,  'entry_type' => 'credit', 'amount' => 5000, 'description' => 'Cr'],
            ]
        );

        $tenant1TxCount = Transaction::where('tenant_id', $this->tenant->id)->count();
        $tenant2TxCount = Transaction::where('tenant_id', $tenant2->id)->count();

        $this->assertEquals(1, $tenant1TxCount);
        $this->assertEquals(0, $tenant2TxCount);
    }
}
