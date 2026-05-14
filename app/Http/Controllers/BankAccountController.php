<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', BankAccount::class);

        $accounts = BankAccount::withoutGlobalScope('tenant')
            ->where('bank_accounts.tenant_id', auth()->user()->tenant_id)
            ->with('glAccount')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('settings.bank-accounts', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', BankAccount::class);

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'bank_name'      => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'account_type'   => ['required', 'in:current,savings,other'],
            'opening_balance'=> ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        if (! empty($validated['account_number'])) {
            $exists = BankAccount::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('account_number', $validated['account_number'])
                ->exists();
            if ($exists) {
                return back()->withErrors(['account_number' => 'That account number is already registered.'])->withInput();
            }
        }

        DB::transaction(function () use ($validated, $tenantId) {
            // Auto-allocate the next free GL code in 1004–1099
            $code = BankAccount::nextGlCode($tenantId);

            $glAccount = Account::withoutGlobalScope('tenant')->create([
                'tenant_id'       => $tenantId,
                'code'            => $code,
                'name'            => $validated['name'],
                'type'            => 'asset',
                'sub_type'        => 'bank',
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'current_balance' => $validated['opening_balance'] ?? 0,
                'is_system'       => false,
                'is_active'       => true,
            ]);

            $isFirstAccount = ! BankAccount::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->exists();

            BankAccount::create([
                'tenant_id'       => $tenantId,
                'name'            => $validated['name'],
                'bank_name'       => $validated['bank_name'] ?? null,
                'account_number'  => $validated['account_number'] ?? null,
                'account_type'    => $validated['account_type'],
                'currency'        => 'NGN',
                'gl_account_id'   => $glAccount->id,
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'is_default'      => $isFirstAccount,
                'is_active'       => true,
                'sort_order'      => 0,
                'notes'           => $validated['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Bank account added.');
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $this->authorize('update', $bankAccount);

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'bank_name'      => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'account_type'   => ['required', 'in:current,savings,other'],
            'is_active'      => ['boolean'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        if (! empty($validated['account_number'])) {
            $exists = BankAccount::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('id', '!=', $bankAccount->id)
                ->where('account_number', $validated['account_number'])
                ->exists();
            if ($exists) {
                return back()->withErrors(['account_number' => 'That account number is already registered.'])->withInput();
            }
        }

        DB::transaction(function () use ($bankAccount, $validated) {
            $bankAccount->update($validated);
            // Keep GL account name in sync
            $bankAccount->glAccount?->update(['name' => $validated['name']]);
        });

        return back()->with('success', 'Bank account updated.');
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $this->authorize('delete', $bankAccount);

        // Prevent deletion if it has been used in any journal entry via its GL account
        $hasEntries = $bankAccount->glAccount?->journalEntries()->exists();
        if ($hasEntries) {
            return back()->with('error', 'This bank account has transaction history and cannot be deleted. Deactivate it instead.');
        }

        DB::transaction(function () use ($bankAccount) {
            $glAccount = $bankAccount->glAccount;
            $bankAccount->delete();
            $glAccount?->delete();
        });

        return back()->with('success', 'Bank account removed.');
    }

    public function setDefault(BankAccount $bankAccount): RedirectResponse
    {
        $this->authorize('update', $bankAccount);

        $tenantId = auth()->user()->tenant_id;

        DB::transaction(function () use ($bankAccount, $tenantId) {
            BankAccount::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->update(['is_default' => false]);

            $bankAccount->update(['is_default' => true]);
        });

        return back()->with('success', "{$bankAccount->name} is now the default bank account.");
    }
}
