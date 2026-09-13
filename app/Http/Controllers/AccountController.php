<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    /** Leading code digit expected for each account type, per the Nigerian SME chart convention. */
    private const TYPE_CODE_PREFIX = [
        'asset'     => '1',
        'liability' => '2',
        'equity'    => '3',
        'revenue'   => '4',
        'expense'   => '5',
    ];

    /** Matches the `sub_type` enum on the accounts table exactly — an invalid value errors at the DB level, not just validation. */
    public const SUB_TYPES = [
        'cash', 'bank', 'accounts_receivable', 'inventory', 'fixed_asset', 'other_asset',
        'accounts_payable', 'vat_payable', 'wht_payable', 'paye_payable', 'loan', 'other_liability',
        'owners_equity', 'retained_earnings',
        'sales_revenue', 'service_revenue', 'other_revenue',
        'cost_of_goods_sold', 'salaries', 'rent', 'utilities', 'transport', 'other_expense',
        'cit_payable', 'vat_control', 'wht_control', 'paye_control',
    ];

    public function index(): View
    {
        $this->authorize('viewAny', Account::class);

        $tenantId = auth()->user()->tenant_id;

        $accounts = Account::where('tenant_id', $tenantId)
            ->withCount('journalEntries')
            ->orderBy('type')
            ->orderBy('code')
            ->get()
            ->groupBy('type');

        return view('settings.chart-of-accounts.index', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Account::class);

        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'type'        => ['required', Rule::in(array_keys(self::TYPE_CODE_PREFIX))],
            'code'        => [
                'required', 'string', 'max:20',
                Rule::unique('accounts', 'code')->where('tenant_id', $tenantId),
            ],
            'sub_type'    => ['nullable', Rule::in(self::SUB_TYPES)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $this->codeMatchesType($validated['code'], $validated['type'])) {
            return back()->withErrors([
                'code' => ucfirst($validated['type']) . ' account codes must start with '
                    . self::TYPE_CODE_PREFIX[$validated['type']] . ' (e.g. '
                    . self::TYPE_CODE_PREFIX[$validated['type']] . '600).',
            ])->withInput();
        }

        Account::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'is_system' => false,
            'is_active' => true,
        ]);

        return back()->with('success', 'Account created.');
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $tenantId = $account->tenant_id;

        // System accounts are load-bearing elsewhere in the app (GL posting
        // looks them up by exact code, reports aggregate by exact type) —
        // never let code/type change for them, no matter what was submitted;
        // the edit form doesn't even offer these fields for a system account.
        $codeTypeRules = $account->is_system
            ? ['type' => ['sometimes'], 'code' => ['sometimes']]
            : [
                'type' => ['required', Rule::in(array_keys(self::TYPE_CODE_PREFIX))],
                'code' => [
                    'required', 'string', 'max:20',
                    Rule::unique('accounts', 'code')->where('tenant_id', $tenantId)->ignore($account->id),
                ],
            ];

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'sub_type'    => ['nullable', Rule::in(self::SUB_TYPES)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
            ...$codeTypeRules,
        ]);

        if ($account->is_system) {
            unset($validated['code'], $validated['type']);
        } elseif (! $this->codeMatchesType($validated['code'], $validated['type'])) {
            return back()->withErrors([
                'code' => ucfirst($validated['type']) . ' account codes must start with '
                    . self::TYPE_CODE_PREFIX[$validated['type']] . ' (e.g. '
                    . self::TYPE_CODE_PREFIX[$validated['type']] . '600).',
            ])->withInput();
        }

        $account->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Account updated.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        if ($account->journalEntries()->exists()) {
            return back()->with('error', 'This account has transaction history and cannot be deleted. Deactivate it instead.');
        }

        $account->delete();

        return back()->with('success', 'Account deleted.');
    }

    private function codeMatchesType(string $code, string $type): bool
    {
        return str_starts_with($code, self::TYPE_CODE_PREFIX[$type]);
    }
}
