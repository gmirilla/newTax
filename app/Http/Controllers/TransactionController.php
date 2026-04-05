<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Repositories\TransactionRepository;
use App\Services\BookkeepingService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
        $tenant   = $request->user()->tenant;
        $expenses = Expense::where('tenant_id', $tenant->id)
            ->with(['vendor', 'account', 'creator'])
            ->orderBy('expense_date', 'desc')
            ->paginate(20);

        return view('transactions.expenses', compact('expenses'));
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
}
