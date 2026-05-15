<?php

namespace App\Http\Controllers;

use App\Exports\TransactionExport;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Repositories\TransactionRepository;
use App\Services\BookkeepingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    public function __construct(
        private readonly BookkeepingService   $bookkeepingService,
        private readonly TransactionRepository $transactionRepository
    ) {}

    public function index(Request $request): View
    {
        $tenant       = $request->user()->tenant;
        $transactions = $this->transactionRepository->paginate($tenant, $request->only([
            'type', 'status', 'date_from', 'date_to', 'search',
        ]));

        return view('transactions.index', compact('transactions'));
    }

    public function exportExcel(Request $request)
    {
        $tenant  = $request->user()->tenant;
        $filters = $request->only(['type', 'status', 'date_from', 'date_to', 'search']);

        $transactions = $this->transactionRepository->filtered($tenant, $filters);

        $label    = $this->exportLabel($filters);
        $filename = "Transactions_{$label}.xlsx";

        return Excel::download(new TransactionExport($transactions, $tenant, $filters), $filename);
    }

    public function exportPdf(Request $request)
    {
        $tenant  = $request->user()->tenant;
        $filters = $request->only(['type', 'status', 'date_from', 'date_to', 'search']);

        $transactions = $this->transactionRepository->filtered($tenant, $filters);

        $pdf = Pdf::loadView('transactions.export-pdf', [
            'transactions' => $transactions,
            'tenant'       => $tenant,
            'filters'      => $filters,
        ])->setPaper('a4', 'landscape');

        $label = $this->exportLabel($filters);
        return $pdf->download("Transactions_{$label}.pdf");
    }

    private function exportLabel(array $filters): string
    {
        $parts = [];
        if (!empty($filters['date_from'])) $parts[] = $filters['date_from'];
        if (!empty($filters['date_to']))   $parts[] = $filters['date_to'];
        if (!empty($filters['type']))      $parts[] = $filters['type'];
        return $parts ? implode('_', $parts) : now()->format('Ymd');
    }

    public function create(Request $request): View
    {
        $tenant   = $request->user()->tenant;
        $accounts = Account::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('transactions.create', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'type'             => 'required|in:sale,purchase,expense,income,payment,receipt,journal',
            'description'      => 'required|string|max:500',
            'entries'          => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:accounts,id',
            'entries.*.entry_type' => 'required|in:debit,credit',
            'entries.*.amount'     => 'required|numeric|min:0.01',
        ]);

        $tenant      = $request->user()->tenant;
        $transaction = $this->bookkeepingService->postJournalEntry(
            $tenant,
            $request->only(['transaction_date', 'type', 'description', 'notes', 'reference']),
            $request->input('entries')
        );

        return redirect()->route('transactions.show', $transaction)
            ->with('success', "Transaction {$transaction->reference} posted.");
    }

    public function show($id): View
    {
        $transaction = \App\Models\Transaction::with([
            'journalEntries.account', 'creator',
        ])->findOrFail($id);

        return view('transactions.show', compact('transaction'));
    }

    public function expenses(Request $request): View
    {
        $tenant = $request->user()->tenant;

        $query = Expense::where('tenant_id', $tenant->id)
            ->with(['vendor', 'account', 'creator']);

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }
        if ($request->filled('vendor_id')) {
            if ($request->vendor_id === 'none') {
                $query->whereNull('vendor_id');
            } else {
                $query->where('vendor_id', $request->vendor_id);
            }
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', $search)
                  ->orWhere('reference', 'like', $search)
                  ->orWhereHas('vendor', fn($q) => $q->where('name', 'like', $search));
            });
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(20)->withQueryString();

        $vendors = \App\Models\Vendor::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Totals across the full filtered result set (not just the current page).
        // reorder() strips the orderBy before aggregating — required for PostgreSQL
        // which rejects ORDER BY on non-grouped columns inside an aggregate SELECT.
        $filteredTotals = (clone $query)->reorder()->selectRaw('
            SUM(amount) as total_amount,
            SUM(wht_amount) as total_wht,
            SUM(net_payable) as total_net
        ')->first();

        return view('transactions.expenses', compact('expenses', 'vendors', 'filteredTotals'));
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $request->validate([
            'expense_date' => 'required|date',
            'account_id'   => 'required|exists:accounts,id',
            'category'     => 'required|string',
            'description'  => 'required|string',
            'amount'       => 'required|numeric|min:0.01',
            'vendor_id'    => 'nullable|exists:vendors,id',
            'receipt'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        $tenant = $request->user()->tenant;

        // Auto-compute WHT if vendor is set and not WHT-exempt
        $vendor      = null;
        $whtAmount   = 0;
        $whtRate     = 0;
        $whtApplicable = false;
        if ($request->vendor_id) {
            $vendor = \App\Models\Vendor::find($request->vendor_id);
            if (!$vendor->wht_exempt) {
                $whtRate       = $vendor->wht_rate;
                $whtAmount     = round($request->amount * $whtRate / 100, 2);
                $whtApplicable = true;
            }
        }

        $expense = Expense::create([
            'tenant_id'      => $tenant->id,
            'vendor_id'      => $request->vendor_id,
            'account_id'     => $request->account_id,
            'reference'      => 'EXP-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
            'expense_date'   => $request->expense_date,
            'category'       => $request->category,
            'description'    => $request->description,
            'amount'         => $request->amount,
            'vat_applicable' => $request->boolean('vat_applicable'),
            'vat_amount'     => $request->boolean('vat_applicable')
                ? round($request->amount * 7.5 / 107.5, 2) : 0,
            'wht_applicable' => $whtApplicable,
            'wht_rate'       => $whtRate,
            'wht_amount'     => $whtAmount,
            'net_payable'    => $request->amount - $whtAmount,
            'status'         => 'pending',
            'receipt_path'   => $request->hasFile('receipt')
                ? $request->file('receipt')->store("receipts/{$tenant->id}", 'public')
                : null,
            'notes'          => $request->notes,
            'created_by'     => Auth::id(),
        ]);

        // Create WHT record only for non-exempt vendors with a positive WHT amount
        if ($vendor && $whtApplicable && $whtAmount > 0) {
            app(\App\Services\WhtService::class)->deductFromExpense($expense, $vendor);
        }

        return redirect()->route('transactions.expenses')
            ->with('success', 'Expense recorded.');
    }

    public function editExpense(Expense $expense): RedirectResponse|View
    {
        if ($expense->status !== 'pending') {
            return redirect()->route('transactions.expenses')
                ->with('error', 'Only pending expenses can be edited.');
        }

        $tenant   = Auth::user()->tenant;
        $accounts = Account::where('tenant_id', $tenant->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $vendors = \App\Models\Vendor::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('transactions.expense-edit', compact('expense', 'accounts', 'vendors'));
    }

    public function updateExpense(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->status !== 'pending') {
            return back()->with('error', 'Only pending expenses can be edited.');
        }

        $request->validate([
            'expense_date'   => 'required|date',
            'account_id'     => 'required|exists:accounts,id',
            'category'       => 'required|string',
            'description'    => 'required|string',
            'amount'         => 'required|numeric|min:0.01',
            'vendor_id'      => 'nullable|exists:vendors,id',
            'receipt'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        $vendor        = null;
        $whtAmount     = 0;
        $whtRate       = 0;
        $whtApplicable = false;

        if ($request->vendor_id) {
            $vendor = \App\Models\Vendor::find($request->vendor_id);
            if (!$vendor->wht_exempt) {
                $whtRate       = $vendor->wht_rate;
                $whtAmount     = round($request->amount * $whtRate / 100, 2);
                $whtApplicable = true;
            }
        }

        $receiptPath = $expense->receipt_path;
        if ($request->hasFile('receipt')) {
            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }
            $receiptPath = $request->file('receipt')
                ->store("receipts/{$expense->tenant_id}", 'public');
        }

        $expense->update([
            'expense_date'   => $request->expense_date,
            'account_id'     => $request->account_id,
            'category'       => $request->category,
            'description'    => $request->description,
            'amount'         => $request->amount,
            'vendor_id'      => $request->vendor_id,
            'vat_applicable' => $request->boolean('vat_applicable'),
            'vat_amount'     => $request->boolean('vat_applicable')
                ? round($request->amount * 7.5 / 107.5, 2) : 0,
            'wht_applicable' => $whtApplicable,
            'wht_rate'       => $whtRate,
            'wht_amount'     => $whtAmount,
            'net_payable'    => $request->amount - $whtAmount,
            'notes'          => $request->notes,
            'receipt_path'   => $receiptPath,
        ]);

        return redirect()->route('transactions.expenses')
            ->with('success', 'Expense updated.');
    }

    public function destroyExpense(Expense $expense): RedirectResponse
    {
        if (!in_array($expense->status, ['pending', 'rejected'])) {
            return back()->with('error', 'Only pending or rejected expenses can be deleted.');
        }

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();

        return redirect()->route('transactions.expenses')
            ->with('success', 'Expense deleted.');
    }

    /**
     * Approve an expense and post the recognition journal entry:
     *   DR  Expense account        (full gross amount)
     *   CR  WHT Payable  [2200]    (wht_amount, if applicable)
     *   CR  Accounts Payable [2001] (net_payable)
     */
    public function approveExpense(Expense $expense): RedirectResponse
    {
        if ($expense->status !== 'pending') {
            return back()->with('error', 'Only pending expenses can be approved.');
        }

        $tenant = Auth::user()->tenant;

        DB::transaction(function () use ($expense, $tenant) {
            $entries = [];

            // DR expense GL account
            $entries[] = [
                'account_id'  => $expense->account_id,
                'entry_type'  => 'debit',
                'amount'      => (float) $expense->amount,
                'description' => "Expense: {$expense->description}",
            ];

            // CR WHT Payable (2200) if WHT applies
            if ($expense->wht_amount > 0) {
                $whtAccount = Account::where('tenant_id', $tenant->id)
                    ->where('code', '2200')->first();
                if ($whtAccount) {
                    $entries[] = [
                        'account_id'  => $whtAccount->id,
                        'entry_type'  => 'credit',
                        'amount'      => (float) $expense->wht_amount,
                        'description' => "WHT on {$expense->reference}",
                    ];
                }
            }

            // CR Accounts Payable (2001)
            $apAccount = Account::where('tenant_id', $tenant->id)
                ->where('code', '2001')->firstOrFail();

            $creditAmount = $expense->wht_amount > 0
                ? (float) $expense->net_payable
                : (float) $expense->amount;

            $entries[] = [
                'account_id'  => $apAccount->id,
                'entry_type'  => 'credit',
                'amount'      => $creditAmount,
                'description' => "Payable: {$expense->reference}",
            ];

            $transaction = $this->bookkeepingService->postJournalEntry($tenant, [
                'transaction_date' => $expense->expense_date->toDateString(),
                'type'             => 'expense',
                'description'      => "Expense approved: {$expense->reference} — {$expense->description}",
                'reference'        => $expense->reference,
            ], $entries);

            $expense->update([
                'status'         => 'approved',
                'approved_by'    => Auth::id(),
                'transaction_id' => $transaction->id,
            ]);
        });

        $expense->loadMissing('creator');
        AuditLog::record('expense.approved', $expense,
            ['status' => 'pending'],
            [
                'reference'      => $expense->reference,
                'initiator_id'   => $expense->created_by,
                'initiator_name' => $expense->creator?->name ?? 'Unknown',
                'description'    => $expense->description,
                'amount'         => $expense->amount,
            ],
            'expense,approval'
        );

        return back()->with('success', "Expense {$expense->reference} approved and posted to ledger.");
    }

    public function rejectExpense(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->status !== 'pending') {
            return back()->with('error', 'Only pending expenses can be rejected.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $expense->loadMissing('creator');
        $expense->update([
            'status' => 'rejected',
            'notes'  => trim("REJECTED: {$request->rejection_reason}\n\n" . $expense->notes),
        ]);

        AuditLog::record('expense.rejected', $expense,
            ['status' => 'pending'],
            [
                'reference'        => $expense->reference,
                'initiator_id'     => $expense->created_by,
                'initiator_name'   => $expense->creator?->name ?? 'Unknown',
                'description'      => $expense->description,
                'amount'           => $expense->amount,
                'rejection_reason' => $request->rejection_reason,
            ],
            'expense,approval'
        );

        return back()->with('success', "Expense {$expense->reference} rejected.");
    }

    /**
     * Mark an approved expense as paid and settle the payable:
     *   DR  Accounts Payable [2001]  (net_payable)
     *   CR  Bank / Cash account      (net_payable)
     */
    public function payExpense(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->status !== 'approved') {
            return back()->with('error', 'Only approved expenses can be marked as paid.');
        }

        $request->validate([
            'payment_date'    => 'required|date',
            'payment_account' => 'required|exists:accounts,id',
        ]);

        $tenant = Auth::user()->tenant;

        DB::transaction(function () use ($expense, $request, $tenant) {
            $apAccount = Account::where('tenant_id', $tenant->id)
                ->where('code', '2001')->firstOrFail();

            $this->bookkeepingService->postJournalEntry($tenant, [
                'transaction_date' => $request->payment_date,
                'type'             => 'payment',
                'description'      => "Expense paid: {$expense->reference} — {$expense->description}",
                'reference'        => 'PAY-' . $expense->reference,
            ], [
                [
                    'account_id'  => $apAccount->id,
                    'entry_type'  => 'debit',
                    'amount'      => (float) $expense->net_payable,
                    'description' => "Settle payable: {$expense->reference}",
                ],
                [
                    'account_id'  => $request->payment_account,
                    'entry_type'  => 'credit',
                    'amount'      => (float) $expense->net_payable,
                    'description' => "Payment: {$expense->reference}",
                ],
            ]);

            $expense->update(['status' => 'paid']);
        });

        return back()->with('success', "Expense {$expense->reference} marked as paid.");
    }
}
