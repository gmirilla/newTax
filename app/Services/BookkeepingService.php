<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\Payroll;
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
     *
     * @param  string  $basis  'accrual' (default) or 'cash'
     *
     * Accrual: revenue on invoice_date — the FIRS-compliant default for CAC-registered companies.
     * Cash:    revenue only when payment_date falls in the period (from invoice_payments table).
     *
     * Aggregates from multiple sources so the report works whether or not the
     * user has posted manual journal entries:
     *  1. Journal entries on revenue/expense GL accounts (manual double-entry)
     *  2. Invoices / payments (depending on basis)
     *  3. Expense records not yet linked to a journal entry
     *  4. Approved payrolls as a Salaries & Wages expense line
     */
    public function getProfitAndLoss(Tenant $tenant, Carbon $start, Carbon $end, string $basis = 'accrual'): array
    {
        $isCash = $basis === 'cash';

        // ── Source 1: manual journal entries ─────────────────────────────────
        $journalRevenue  = $this->getJournalAccountTotals($tenant, 'revenue', $start, $end);
        $journalExpenses = $this->getJournalAccountTotals($tenant, 'expense', $start, $end);

        // ── Source 2: invoice revenue ─────────────────────────────────────────
        $invoiceRevenue = $isCash
            ? $this->getCashBasisRevenue($tenant, $start, $end)
            : $this->getInvoiceRevenue($tenant, $start, $end);

        // ── Source 3: expense records (not already journalised) ───────────────
        $expenseLines  = $this->getDirectExpenseLines($tenant, $start, $end);

        // ── Source 4: approved payrolls as salary expense ─────────────────────
        $payrollExpense = $this->getPayrollExpenseLines($tenant, $start, $end);

        // ── Merge and deduplicate by account code ─────────────────────────────
        $revenue  = $this->mergeAccountLines($journalRevenue,  $invoiceRevenue);
        $expenses = $this->mergeAccountLines($journalExpenses, array_merge($expenseLines, $payrollExpense));

        $totalRevenue  = array_sum(array_column($revenue,  'balance'));
        $totalExpenses = array_sum(array_column($expenses, 'balance'));
        $netProfit     = $totalRevenue - $totalExpenses;

        return [
            'period_start'   => $start->toDateString(),
            'period_end'     => $end->toDateString(),
            'basis'          => $isCash ? 'cash' : 'accrual',
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
     *
     * Supplements journal entries with direct-table data so the report is
     * meaningful even when the user has not posted all transactions manually:
     *
     * Assets:
     *   - Journal GL entries (asset accounts)
     *   - Accounts Receivable: outstanding invoice balances (1100)
     *   - Bank / Cash inflows: invoice payments actually received (1002)
     *
     * Liabilities:
     *   - Journal GL entries (liability accounts)
     *   - Accounts Payable: pending/approved expenses not yet paid (2001)
     *   - PAYE Payable: from approved payrolls up to asOf (2300)
     *   - Pension Payable: employee + employer contributions from payrolls (new line)
     *   - NHF Payable: NHF deductions from payrolls (new line)
     *
     * Equity:
     *   - Journal GL entries (equity accounts)
     *   - Retained Earnings: net of all accrual-basis P&L up to asOf (3100),
     *     supplemented where not already in journals
     *
     * Note: because not every cash outflow (expense payment) is journalised,
     * the sheet may not balance exactly — is_balanced uses a 1% tolerance and
     * the result is marked as approximate.
     */
    public function getBalanceSheet(Tenant $tenant, ?Carbon $asOf = null): array
    {
        $asOf ??= now();

        // ── Journal-based lines ───────────────────────────────────────────────
        $assets      = $this->getJournalAccountTotals($tenant, 'asset',     null, $asOf);
        $liabilities = $this->getJournalAccountTotals($tenant, 'liability', null, $asOf);
        $equity      = $this->getJournalAccountTotals($tenant, 'equity',    null, $asOf);

        // ── Asset supplements ─────────────────────────────────────────────────
        $assets = $this->mergeAccountLines($assets, $this->getOutstandingReceivables($tenant, $asOf));
        $assets = $this->mergeAccountLines($assets, $this->getCashFromPayments($tenant, $asOf));

        // ── Liability supplements ─────────────────────────────────────────────
        $liabilities = $this->mergeAccountLines($liabilities, $this->getDirectPayables($tenant, $asOf));
        $liabilities = $this->mergeAccountLines($liabilities, $this->getPayrollPayables($tenant, $asOf));

        // ── Equity supplement: retained earnings from operations ──────────────
        $equity = $this->mergeAccountLines($equity, $this->getOperationalRetainedEarnings($tenant, $asOf));

        // ── Totals ────────────────────────────────────────────────────────────
        $totalAssets      = array_sum(array_column($assets,      'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity      = array_sum(array_column($equity,      'balance'));
        $rightSide        = $totalLiabilities + $totalEquity;

        // 1% tolerance — sheet is approximate when not fully journalised
        $isBalanced = $totalAssets > 0
            ? abs($totalAssets - $rightSide) / $totalAssets < 0.01
            : $totalAssets == $rightSide;

        return [
            'as_of'             => $asOf->toDateString(),
            'assets'            => $assets,
            'liabilities'       => $liabilities,
            'equity'            => $equity,
            'total_assets'      => round($totalAssets, 2),
            'total_liabilities' => round($totalLiabilities, 2),
            'total_equity'      => round($totalEquity, 2),
            'is_balanced'       => $isBalanced,
            'is_approximate'    => true, // always approximate until all transactions are journalised
        ];
    }

    // ── Private: journal-based helpers ───────────────────────────────────────

    /**
     * Sum journal entry balances for a given account type within the date range.
     * Renamed from getAccountTotals to be explicit about data source.
     */
    private function getJournalAccountTotals(Tenant $tenant, string $type, ?Carbon $start, Carbon $end): array
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

            $debits  = (float) $query->clone()->where('entry_type', 'debit')->sum('amount');
            $credits = (float) $query->clone()->where('entry_type', 'credit')->sum('amount');

            $journalBalance = in_array($type, ['asset', 'expense'])
                ? $debits - $credits
                : $credits - $debits;

            // Include the account's opening balance for cumulative (balance sheet) queries.
            // Opening balance represents the position before the company started using NaijaBooks.
            // Only applied when $start is null (balance-sheet / all-time mode), not for P&L periods.
            $openingBalance = ($start === null) ? (float) $account->opening_balance : 0.0;

            $balance = $journalBalance + $openingBalance;

            if (round($balance, 2) != 0) {
                $result[] = [
                    'code'    => $account->code,
                    'name'    => $account->name,
                    'balance' => round($balance, 2),
                    'source'  => 'journal',
                ];
            }
        }

        return $result;
    }

    // ── Private: direct-table helpers ────────────────────────────────────────

    /**
     * Revenue from invoices that have NOT been posted to the journal yet
     * (transaction_id IS NULL). Groups by the most common account code on the
     * invoice's line items; falls back to account 4001 (Sales Revenue).
     */
    private function getInvoiceRevenue(Tenant $tenant, Carbon $start, Carbon $end): array
    {
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->whereNull('transaction_id')
            ->whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->with('items')
            ->get();

        if ($invoices->isEmpty()) {
            return [];
        }

        // Group invoice subtotals by the account code on their line items
        $grouped = [];
        foreach ($invoices as $invoice) {
            // Determine dominant account code across all line items
            $codes = $invoice->items->pluck('account_code')->filter()->countBy()->sortDesc();
            $code  = $codes->keys()->first() ?? '4001';

            $grouped[$code] = ($grouped[$code] ?? 0) + (float) $invoice->subtotal;
        }

        // Resolve account names from chart of accounts
        $accounts = Account::where('tenant_id', $tenant->id)
            ->whereIn('code', array_keys($grouped))
            ->pluck('name', 'code');

        // Default names for codes not in chart of accounts
        $defaultNames = [
            '4001' => 'Sales Revenue',
            '4002' => 'Service Income',
            '4003' => 'Other Income',
        ];

        $result = [];
        foreach ($grouped as $code => $total) {
            if (round($total, 2) == 0) continue;
            $result[] = [
                'code'    => $code,
                'name'    => $accounts[$code] ?? ($defaultNames[$code] ?? "Revenue ({$code})"),
                'balance' => round($total, 2),
                'source'  => 'invoices',
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['code'], $b['code']));

        return $result;
    }

    /**
     * Cash-basis revenue: sum of actual payments received (invoice_payments.payment_date)
     * within the period, grouped by the dominant account code on the parent invoice's items.
     * Only covers payments against un-journalised invoices (transaction_id IS NULL on invoice).
     */
    private function getCashBasisRevenue(Tenant $tenant, Carbon $start, Carbon $end): array
    {
        $payments = InvoicePayment::where('invoice_payments.tenant_id', $tenant->id)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->whereNull('invoices.transaction_id')
            ->select('invoice_payments.amount', 'invoice_payments.invoice_id')
            ->with('invoice.items')
            ->get();

        if ($payments->isEmpty()) {
            return [];
        }

        $grouped = [];
        foreach ($payments as $payment) {
            $invoice = $payment->invoice;
            $codes   = $invoice?->items?->pluck('account_code')->filter()->countBy()->sortDesc();
            $code    = $codes?->keys()->first() ?? '4001';

            $grouped[$code] = ($grouped[$code] ?? 0) + (float) $payment->amount;
        }

        $accounts = Account::where('tenant_id', $tenant->id)
            ->whereIn('code', array_keys($grouped))
            ->pluck('name', 'code');

        $defaultNames = ['4001' => 'Sales Revenue', '4002' => 'Service Income', '4003' => 'Other Income'];

        $result = [];
        foreach ($grouped as $code => $total) {
            if (round($total, 2) == 0) continue;
            $result[] = [
                'code'    => $code,
                'name'    => $accounts[$code] ?? ($defaultNames[$code] ?? "Revenue ({$code})"),
                'balance' => round($total, 2),
                'source'  => 'payments',
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['code'], $b['code']));

        return $result;
    }

    /**
     * Expense lines from the expenses table for records NOT posted to the
     * journal (transaction_id IS NULL), grouped by their linked GL account.
     */
    private function getDirectExpenseLines(Tenant $tenant, Carbon $start, Carbon $end): array
    {
        $expenses = Expense::where('tenant_id', $tenant->id)
            ->whereNull('transaction_id')
            ->whereNotIn('status', ['rejected'])
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->with('account')
            ->get();

        if ($expenses->isEmpty()) {
            return [];
        }

        $grouped = [];
        foreach ($expenses as $exp) {
            if ($exp->account) {
                $key  = $exp->account->code;
                $name = $exp->account->name;
            } else {
                // Map free-text category to a best-guess account code
                [$key, $name] = $this->mapExpenseCategoryToAccount($exp->category);
            }

            if (! isset($grouped[$key])) {
                $grouped[$key] = ['code' => $key, 'name' => $name, 'balance' => 0.0, 'source' => 'expenses'];
            }
            $grouped[$key]['balance'] += (float) $exp->amount;
        }

        foreach ($grouped as &$row) {
            $row['balance'] = round($row['balance'], 2);
        }

        usort($grouped, fn($a, $b) => strcmp($a['code'], $b['code']));

        return array_values(array_filter($grouped, fn($r) => $r['balance'] != 0));
    }

    /**
     * Approved payrolls mapped to account 5100 (Salaries & Wages).
     * Uses pay_date to place in the period.
     */
    private function getPayrollExpenseLines(Tenant $tenant, Carbon $start, Carbon $end): array
    {
        $total = Payroll::where('tenant_id', $tenant->id)
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('pay_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_gross');

        if ((float) $total == 0) {
            return [];
        }

        // Resolve account name from chart
        $account = Account::where('tenant_id', $tenant->id)
            ->where('code', '5100')
            ->first();

        return [[
            'code'    => '5100',
            'name'    => $account?->name ?? 'Salaries & Wages',
            'balance' => round((float) $total, 2),
            'source'  => 'payroll',
        ]];
    }

    /**
     * Outstanding invoice receivables for Balance Sheet (balance_due > 0, not paid/void).
     */
    private function getOutstandingReceivables(Tenant $tenant, Carbon $asOf): array
    {
        $total = Invoice::where('tenant_id', $tenant->id)
            ->whereNull('transaction_id')
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->where('invoice_date', '<=', $asOf->toDateString())
            ->sum('balance_due');

        if ((float) $total == 0) {
            return [];
        }

        $account = Account::where('tenant_id', $tenant->id)
            ->where('code', '1100')
            ->first();

        return [[
            'code'    => '1100',
            'name'    => $account?->name ?? 'Accounts Receivable',
            'balance' => round((float) $total, 2),
            'source'  => 'invoices',
        ]];
    }

    /**
     * Cash/bank inflows: sum of invoice payments received up to asOf.
     * These represent cash that actually landed in the bank (1002).
     * Only covers un-journalised invoices to avoid double-counting.
     */
    private function getCashFromPayments(Tenant $tenant, Carbon $asOf): array
    {
        $total = InvoicePayment::where('invoice_payments.tenant_id', $tenant->id)
            ->where('payment_date', '<=', $asOf->toDateString())
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->whereNull('invoices.transaction_id')
            ->sum('invoice_payments.amount');

        if ((float) $total == 0) {
            return [];
        }

        $account = Account::where('tenant_id', $tenant->id)->where('code', '1002')->first();

        return [[
            'code'    => '1002',
            'name'    => $account?->name ?? 'Bank Account - Current',
            'balance' => round((float) $total, 2),
            'source'  => 'payments',
        ]];
    }

    /**
     * Accounts payable from expenses with status pending/approved (i.e. not yet paid).
     * Maps to account 2001 (Accounts Payable).
     */
    private function getDirectPayables(Tenant $tenant, Carbon $asOf): array
    {
        $total = Expense::where('tenant_id', $tenant->id)
            ->whereNull('transaction_id')
            ->whereIn('status', ['pending', 'approved'])
            ->where('expense_date', '<=', $asOf->toDateString())
            ->sum('net_payable');

        if ((float) $total == 0) {
            return [];
        }

        $account = Account::where('tenant_id', $tenant->id)->where('code', '2001')->first();

        return [[
            'code'    => '2001',
            'name'    => $account?->name ?? 'Accounts Payable',
            'balance' => round((float) $total, 2),
            'source'  => 'expenses',
        ]];
    }

    /**
     * Statutory payroll liabilities from approved payrolls up to asOf:
     *   2300  PAYE Payable       = total_paye
     *   2201  Pension Payable    = total_pension (employee 8%) + total_employer_pension (10%)
     *   2202  NHF Payable        = total_nhf
     *
     * Using 'approved' only — draft payrolls are not yet committed liabilities.
     */
    private function getPayrollPayables(Tenant $tenant, Carbon $asOf): array
    {
        $payrolls = Payroll::where('tenant_id', $tenant->id)
            ->where('status', 'approved')
            ->where('pay_date', '<=', $asOf->toDateString())
            ->selectRaw('SUM(total_paye) as paye, SUM(total_pension) as pension, SUM(total_nhf) as nhf')
            ->first();

        if (! $payrolls) {
            return [];
        }

        $payeAccount    = Account::where('tenant_id', $tenant->id)->where('code', '2300')->first();
        $pensionAccount = Account::where('tenant_id', $tenant->id)->where('code', '2301')->first();
        $nhfAccount     = Account::where('tenant_id', $tenant->id)->where('code', '2302')->first();

        $lines = [];

        if ((float) $payrolls->paye > 0) {
            $lines[] = [
                'code'    => '2300',
                'name'    => $payeAccount?->name ?? 'PAYE Tax Payable',
                'balance' => round((float) $payrolls->paye, 2),
                'source'  => 'payroll',
            ];
        }

        if ((float) $payrolls->pension > 0) {
            $lines[] = [
                'code'    => '2301',
                'name'    => $pensionAccount?->name ?? 'Pension Contributions Payable',
                'balance' => round((float) $payrolls->pension, 2),
                'source'  => 'payroll',
            ];
        }

        if ((float) $payrolls->nhf > 0) {
            $lines[] = [
                'code'    => '2302',
                'name'    => $nhfAccount?->name ?? 'NHF Contributions Payable',
                'balance' => round((float) $payrolls->nhf, 2),
                'source'  => 'payroll',
            ];
        }

        return $lines;
    }

    /**
     * Retained earnings from operations: net P&L from all sources up to asOf,
     * combining journal-based and supplement (non-journalised) data.
     *
     * Added to account 3100 only when no explicit journal entry exists for 3100
     * (i.e. the user hasn't closed the period manually to retained earnings).
     *
     * P&L sources:
     *   Journal revenue  = net credit balance on all revenue accounts
     *   Journal expenses = net debit  balance on all expense accounts
     *   Supplement revenue  = invoice subtotals (transaction_id IS NULL)
     *   Supplement expenses = expense amounts   (transaction_id IS NULL, not rejected)
     *   Supplement payroll  = approved payroll gross (no transaction_id on payrolls yet)
     */
    private function getOperationalRetainedEarnings(Tenant $tenant, Carbon $asOf): array
    {
        // If 3100 is already in journals the user has closed the period — don't overlay
        $existingEquity = $this->getJournalAccountTotals($tenant, 'equity', null, $asOf);
        if (collect($existingEquity)->where('code', '3100')->isNotEmpty()) {
            return [];
        }

        // ── Journal-based P&L ────────────────────────────────────────────────
        $journalRevRows  = $this->getJournalAccountTotals($tenant, 'revenue', null, $asOf);
        $journalExpRows  = $this->getJournalAccountTotals($tenant, 'expense', null, $asOf);
        $journalRevenue  = array_sum(array_column($journalRevRows,  'balance'));
        $journalExpenses = array_sum(array_column($journalExpRows, 'balance'));

        // ── Supplement P&L (non-journalised records only) ────────────────────
        $supplementRevenue = (float) Invoice::where('tenant_id', $tenant->id)
            ->whereNull('transaction_id')
            ->whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
            ->where('invoice_date', '<=', $asOf->toDateString())
            ->sum('subtotal');

        $supplementExpenses = (float) Expense::where('tenant_id', $tenant->id)
            ->whereNull('transaction_id')
            ->whereNotIn('status', ['rejected'])
            ->where('expense_date', '<=', $asOf->toDateString())
            ->sum('amount');

        $supplementPayroll = (float) Payroll::where('tenant_id', $tenant->id)
            ->whereIn('status', ['approved', 'paid'])
            ->where('pay_date', '<=', $asOf->toDateString())
            ->sum('total_gross');

        $retained = round(
            $journalRevenue  - $journalExpenses
            + $supplementRevenue - $supplementExpenses - $supplementPayroll,
            2
        );

        if ($retained == 0) {
            return [];
        }

        $account = Account::where('tenant_id', $tenant->id)->where('code', '3100')->first();

        return [[
            'code'    => '3100',
            'name'    => $account?->name ?? 'Retained Earnings',
            'balance' => $retained,
            'source'  => 'operations',
        ]];
    }

    /**
     * Merge two lists of account-line arrays, summing balances where account
     * codes overlap.  Lines from $base always appear first; new codes from
     * $supplement are appended, sorted by code.
     */
    private function mergeAccountLines(array $base, array $supplement): array
    {
        $index = [];
        foreach ($base as $row) {
            $index[$row['code']] = $row;
        }
        foreach ($supplement as $row) {
            if (isset($index[$row['code']])) {
                $index[$row['code']]['balance'] = round(
                    $index[$row['code']]['balance'] + $row['balance'], 2
                );
            } else {
                $index[$row['code']] = $row;
            }
        }

        // Sort by code, remove zero lines
        $result = array_values(array_filter($index, fn($r) => $r['balance'] != 0));
        usort($result, fn($a, $b) => strcmp($a['code'], $b['code']));

        return $result;
    }

    /**
     * Map a free-text expense category to an account code + name.
     */
    private function mapExpenseCategoryToAccount(string $category): array
    {
        $cat = strtolower($category);
        return match (true) {
            str_contains($cat, 'salary') || str_contains($cat, 'wage') || str_contains($cat, 'payroll')
                => ['5100', 'Salaries & Wages'],
            str_contains($cat, 'rent') || str_contains($cat, 'lease')
                => ['5200', 'Office Rent'],
            str_contains($cat, 'util') || str_contains($cat, 'electric') || str_contains($cat, 'water')
                => ['5300', 'Utilities'],
            str_contains($cat, 'transport') || str_contains($cat, 'travel') || str_contains($cat, 'fuel')
                => ['5400', 'Transport & Travel'],
            str_contains($cat, 'cogs') || str_contains($cat, 'cost of goods') || str_contains($cat, 'purchase')
                => ['5001', 'Cost of Goods Sold'],
            default
                => ['5500', 'Other Operating Expenses'],
        };
    }

    /**
     * Generate General Ledger for a period.
     *
     * For each active account (optionally filtered to one account code),
     * returns all posted journal entries in date order with a running balance,
     * plus the opening balance as of the day before $start.
     */
    public function getLedger(Tenant $tenant, Carbon $start, Carbon $end, ?string $accountCode = null): array
    {
        $accountsQuery = Account::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('code');

        if ($accountCode) {
            $accountsQuery->where('code', $accountCode);
        }

        $accounts       = $accountsQuery->get();
        $ledgerAccounts = [];

        foreach ($accounts as $account) {
            $normalDebit = in_array($account->type, ['asset', 'expense']);

            // Opening balance: all posted entries strictly before $start
            $openDebits  = (float) JournalEntry::where('tenant_id', $tenant->id)
                ->where('account_id', $account->id)
                ->where('entry_type', 'debit')
                ->whereHas('transaction', fn($q) => $q
                    ->where('status', 'posted')
                    ->where('transaction_date', '<', $start->toDateString()))
                ->sum('amount');

            $openCredits = (float) JournalEntry::where('tenant_id', $tenant->id)
                ->where('account_id', $account->id)
                ->where('entry_type', 'credit')
                ->whereHas('transaction', fn($q) => $q
                    ->where('status', 'posted')
                    ->where('transaction_date', '<', $start->toDateString()))
                ->sum('amount');

            $openingBalance = $normalDebit
                ? ($openDebits  - $openCredits)
                : ($openCredits - $openDebits);

            // Period entries
            $entries = JournalEntry::where('tenant_id', $tenant->id)
                ->where('account_id', $account->id)
                ->whereHas('transaction', fn($q) => $q
                    ->where('status', 'posted')
                    ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]))
                ->with(['transaction' => fn($q) => $q->select('id', 'reference', 'transaction_date', 'description')])
                ->get()
                ->sortBy('transaction.transaction_date');

            // Skip accounts with no activity and zero opening balance
            if ($entries->isEmpty() && round($openingBalance, 2) == 0) {
                continue;
            }

            $running = round($openingBalance, 2);
            $lines   = [];

            foreach ($entries as $entry) {
                $amount = (float) $entry->amount;

                if ($normalDebit) {
                    $running += $entry->entry_type === 'debit' ? $amount : -$amount;
                } else {
                    $running += $entry->entry_type === 'credit' ? $amount : -$amount;
                }

                $lines[] = [
                    'date'        => $entry->transaction->transaction_date->toDateString(),
                    'reference'   => $entry->transaction->reference,
                    'description' => $entry->description ?: $entry->transaction->description,
                    'debit'       => $entry->entry_type === 'debit'  ? round($amount, 2) : null,
                    'credit'      => $entry->entry_type === 'credit' ? round($amount, 2) : null,
                    'balance'     => round($running, 2),
                ];
            }

            $ledgerAccounts[] = [
                'code'            => $account->code,
                'name'            => $account->name,
                'type'            => $account->type,
                'opening_balance' => round($openingBalance, 2),
                'closing_balance' => round($running, 2),
                'lines'           => $lines,
            ];
        }

        return [
            'period_start' => $start->toDateString(),
            'period_end'   => $end->toDateString(),
            'account_code' => $accountCode,
            'accounts'     => $ledgerAccounts,
        ];
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
