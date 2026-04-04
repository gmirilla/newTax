<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Tenant;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookkeepingService
{
    /**
     * Post a double-entry journal transaction.
     * Every debit must equal every credit (double-entry principle).
     */
    public function postJournalEntry(Tenant $tenant, array $data, array $entries): Transaction
    {
        return DB::transaction(function () use ($tenant, $data, $entries) {
            // Validate double-entry balance
            $totalDebits  = collect($entries)->where('entry_type', 'debit')->sum('amount');
            $totalCredits = collect($entries)->where('entry_type', 'credit')->sum('amount');

            if (round($totalDebits, 2) !== round($totalCredits, 2)) {
                throw new \InvalidArgumentException(
                    "Journal entry imbalanced: Debits ₦{$totalDebits} ≠ Credits ₦{$totalCredits}"
                );
            }

            $transaction = Transaction::create([
                'tenant_id'        => $tenant->id,
                'reference'        => $data['reference'] ?? $this->generateReference($tenant),
                'transaction_date' => $data['transaction_date'],
                'type'             => $data['type'],
                'amount'           => $totalDebits,
                'currency'         => 'NGN',
                'description'      => $data['description'],
                'notes'            => $data['notes'] ?? null,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            foreach ($entries as $entry) {
                JournalEntry::create([
                    'tenant_id'      => $tenant->id,
                    'transaction_id' => $transaction->id,
                    'account_id'     => $entry['account_id'],
                    'entry_type'     => $entry['entry_type'],
                    'amount'         => $entry['amount'],
                    'description'    => $entry['description'] ?? null,
                ]);

                // Update account balance
                $account = Account::find($entry['account_id']);
                if ($account) {
                    $this->updateAccountBalance($account, $entry['entry_type'], $entry['amount']);
                }
            }

            return $transaction->load('journalEntries.account');
        });
    }

    /**
     * Update account balance based on debit/credit and account normal balance.
     * Normal balances:
     * - Assets & Expenses: Debit increases, Credit decreases
     * - Liabilities, Equity & Revenue: Credit increases, Debit decreases
     */
    private function updateAccountBalance(Account $account, string $entryType, float $amount): void
    {
        $normalDebitAccounts  = ['asset', 'expense'];
        $normalCreditAccounts = ['liability', 'equity', 'revenue'];

        if (in_array($account->type, $normalDebitAccounts)) {
            $account->current_balance += $entryType === 'debit' ? $amount : -$amount;
        } else {
            $account->current_balance += $entryType === 'credit' ? $amount : -$amount;
        }

        $account->save();
    }

    /**
     * Generate Trial Balance for a period.
     */
    public function getTrialBalance(Tenant $tenant, ?Carbon $asOf = null): array
    {
        $asOf ??= now();

        $accounts = Account::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $rows         = [];
        $totalDebits  = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $debits = JournalEntry::where('tenant_id', $tenant->id)
                ->where('account_id', $account->id)
                ->where('entry_type', 'debit')
                ->whereHas('transaction', fn($q) => $q->where('transaction_date', '<=', $asOf)->where('status', 'posted'))
                ->sum('amount');

            $credits = JournalEntry::where('tenant_id', $tenant->id)
                ->where('account_id', $account->id)
                ->where('entry_type', 'credit')
                ->whereHas('transaction', fn($q) => $q->where('transaction_date', '<=', $asOf)->where('status', 'posted'))
                ->sum('amount');

            if ($debits > 0 || $credits > 0) {
                $balance = in_array($account->type, ['asset', 'expense'])
                    ? $debits - $credits
                    : $credits - $debits;

                $rows[] = [
                    'code'    => $account->code,
                    'name'    => $account->name,
                    'type'    => $account->type,
                    'debits'  => round((float)$debits, 2),
                    'credits' => round((float)$credits, 2),
                    'balance' => round($balance, 2),
                ];

                $totalDebits  += (float)$debits;
                $totalCredits += (float)$credits;
            }
        }

        return [
            'as_of'         => $asOf->toDateString(),
            'rows'          => $rows,
            'total_debits'  => round($totalDebits, 2),
            'total_credits' => round($totalCredits, 2),
            'is_balanced'   => round($totalDebits, 2) === round($totalCredits, 2),
        ];
    }

    /**
     * Generate Profit & Loss Statement.
     */
    public function getProfitAndLoss(Tenant $tenant, Carbon $start, Carbon $end): array
    {
        $revenue  = $this->getAccountTotals($tenant, 'revenue', $start, $end);
        $expenses = $this->getAccountTotals($tenant, 'expense', $start, $end);

        $totalRevenue  = array_sum(array_column($revenue, 'balance'));
        $totalExpenses = array_sum(array_column($expenses, 'balance'));
        $netProfit     = $totalRevenue - $totalExpenses;

        return [
            'period_start'   => $start->toDateString(),
            'period_end'     => $end->toDateString(),
            'revenue'        => $revenue,
            'expenses'       => $expenses,
            'total_revenue'  => round($totalRevenue, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit'     => round($netProfit, 2),
            'is_profit'      => $netProfit >= 0,
        ];
    }

    /**
     * Generate Balance Sheet.
     */
    public function getBalanceSheet(Tenant $tenant, ?Carbon $asOf = null): array
    {
        $asOf ??= now();

        $assets      = $this->getAccountTotals($tenant, 'asset', null, $asOf);
        $liabilities = $this->getAccountTotals($tenant, 'liability', null, $asOf);
        $equity      = $this->getAccountTotals($tenant, 'equity', null, $asOf);

        $totalAssets      = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity      = array_sum(array_column($equity, 'balance'));

        return [
            'as_of'             => $asOf->toDateString(),
            'assets'            => $assets,
            'liabilities'       => $liabilities,
            'equity'            => $equity,
            'total_assets'      => round($totalAssets, 2),
            'total_liabilities' => round($totalLiabilities, 2),
            'total_equity'      => round($totalEquity, 2),
            'is_balanced'       => round($totalAssets, 2) === round($totalLiabilities + $totalEquity, 2),
        ];
    }

    private function getAccountTotals(Tenant $tenant, string $type, ?Carbon $start, Carbon $end): array
    {
        $accounts = Account::where('tenant_id', $tenant->id)
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $result = [];
        foreach ($accounts as $account) {
            $query = JournalEntry::where('tenant_id', $tenant->id)
                ->where('account_id', $account->id)
                ->whereHas('transaction', function ($q) use ($start, $end) {
                    $q->where('status', 'posted')
                      ->when($start, fn($q) => $q->where('transaction_date', '>=', $start))
                      ->where('transaction_date', '<=', $end);
                });

            $debits  = (float)$query->clone()->where('entry_type', 'debit')->sum('amount');
            $credits = (float)$query->clone()->where('entry_type', 'credit')->sum('amount');

            $balance = in_array($type, ['asset', 'expense'])
                ? $debits - $credits
                : $credits - $debits;

            if ($balance != 0) {
                $result[] = [
                    'code'    => $account->code,
                    'name'    => $account->name,
                    'balance' => round($balance, 2),
                ];
            }
        }

        return $result;
    }

    private function generateReference(Tenant $tenant): string
    {
        return 'TXN-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Provision chart of accounts for a new tenant.
     */
    public function provisionDefaultAccounts(Tenant $tenant): void
    {
        foreach (Account::DEFAULT_ACCOUNTS as $accountData) {
            Account::create(array_merge($accountData, [
                'tenant_id'  => $tenant->id,
                'is_system'  => true,
                'is_active'  => true,
            ]));
        }
    }
}
