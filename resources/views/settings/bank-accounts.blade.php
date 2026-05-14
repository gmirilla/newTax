@extends('layouts.app')
@section('page-title', 'Bank Accounts')

@section('content')
<div x-data="{ showForm: false }" class="max-w-3xl space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Bank Accounts</h1>
            <p class="text-xs text-gray-500 mt-0.5">Each bank account gets its own GL account for double-entry bookkeeping.</p>
        </div>
        @can('create', App\Models\BankAccount::class)
        <button @click="showForm = !showForm"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + Add Bank Account
        </button>
        @endcan
    </div>

    @if(session('success'))
        <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 space-y-1">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Add New Account Form --}}
    @can('create', App\Models\BankAccount::class)
    <div x-show="showForm" x-cloak class="bg-white rounded-lg shadow p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Add Bank Account</h2>
        <form method="POST" action="{{ route('settings.bank-accounts.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Account Label <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="e.g. GTBank Current Account">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" maxlength="100"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="e.g. Guaranty Trust Bank">
                    @error('bank_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Account Number</label>
                    <input type="text" name="account_number" value="{{ old('account_number') }}" maxlength="50"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="10-digit NUBAN">
                    @error('account_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Account Type <span class="text-red-500">*</span></label>
                    <select name="account_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        <option value="current"  {{ old('account_type') === 'current'  ? 'selected' : '' }}>Current</option>
                        <option value="savings"  {{ old('account_type') === 'savings'  ? 'selected' : '' }}>Savings</option>
                        <option value="other"    {{ old('account_type') === 'other'    ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Opening Balance (₦)</label>
                    <input type="number" name="opening_balance" value="{{ old('opening_balance', 0) }}" min="0" step="0.01"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('opening_balance')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" maxlength="500"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="Optional internal notes">
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-md px-3 py-2 text-xs text-blue-700">
                A new GL account (code 1004+) will be automatically created in your Chart of Accounts.
            </div>

            <div class="flex gap-2 pt-1">
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Add Account
                </button>
                <button type="button" @click="showForm = false"
                        class="px-4 py-2 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endcan

    {{-- Accounts List --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b">
            <h2 class="text-base font-semibold text-gray-900">Registered Accounts</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($accounts as $account)
            <div x-data="{ editing: false }" class="px-6 py-4">

                {{-- View row --}}
                <div x-show="!editing" class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-semibold text-gray-900">{{ $account->name }}</p>
                            @if($account->is_default)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-green-100 text-green-800">Default</span>
                            @endif
                            @unless($account->is_active)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-gray-100 text-gray-500">Inactive</span>
                            @endunless
                        </div>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-500">
                            @if($account->bank_name)
                                <span>{{ $account->bank_name }}</span>
                            @endif
                            @if($account->account_number)
                                <span class="font-mono">{{ $account->account_number }}</span>
                            @endif
                            <span class="capitalize">{{ $account->account_type }}</span>
                            @if($account->glAccount)
                                <span>GL: {{ $account->glAccount->code }}</span>
                            @endif
                        </div>
                        @if($account->notes)
                            <p class="mt-1 text-xs text-gray-400 italic">{{ $account->notes }}</p>
                        @endif
                    </div>
                    <div class="flex-shrink-0 text-right">
                        @php $bal = $account->glBalance(); @endphp
                        <p class="text-sm font-semibold {{ $bal >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                            ₦{{ number_format($bal, 2) }}
                        </p>
                        <p class="text-[10px] text-gray-400">GL balance</p>
                    </div>
                </div>

                {{-- Action buttons (view mode) --}}
                <div x-show="!editing" class="mt-3 flex items-center gap-3">
                    @can('update', $account)
                    <button @click="editing = true"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                    @endcan
                    @if(! $account->is_default && $account->is_active)
                    @can('update', $account)
                    <form method="POST" action="{{ route('settings.bank-accounts.default', $account) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 font-medium">Set Default</button>
                    </form>
                    @endcan
                    @endif
                    @can('delete', $account)
                    <form method="POST" action="{{ route('settings.bank-accounts.destroy', $account) }}"
                          class="inline ml-auto"
                          onsubmit="return confirm('Remove {{ addslashes($account->name) }}? This also removes its GL account if unused.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Remove</button>
                    </form>
                    @endcan
                </div>

                {{-- Inline edit form --}}
                @can('update', $account)
                <div x-show="editing" x-cloak>
                    <form method="POST" action="{{ route('settings.bank-accounts.update', $account) }}" class="space-y-3 mt-2">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Label</label>
                                <input type="text" name="name" value="{{ $account->name }}" required maxlength="100"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Bank Name</label>
                                <input type="text" name="bank_name" value="{{ $account->bank_name }}" maxlength="100"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Account Number</label>
                                <input type="text" name="account_number" value="{{ $account->account_number }}" maxlength="50"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Type</label>
                                <select name="account_type"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="current" {{ $account->account_type === 'current' ? 'selected' : '' }}>Current</option>
                                    <option value="savings" {{ $account->account_type === 'savings' ? 'selected' : '' }}>Savings</option>
                                    <option value="other"   {{ $account->account_type === 'other'   ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Notes</label>
                                <input type="text" name="notes" value="{{ $account->notes }}" maxlength="500"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div class="flex items-center gap-3 pt-4">
                                <label class="flex items-center gap-1.5 text-sm">
                                    <input type="checkbox" name="is_active" value="1"
                                           {{ $account->is_active ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-green-600">
                                    Active
                                </label>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="px-4 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                                Save
                            </button>
                            <button type="button" @click="editing = false"
                                    class="px-4 py-1.5 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
                @endcan

            </div>
            @empty
            <div class="px-6 py-10 text-center">
                <p class="text-sm text-gray-400">No bank accounts configured yet.</p>
                @can('create', App\Models\BankAccount::class)
                <button @click="showForm = true"
                        class="text-sm text-green-600 hover:underline mt-1">Add your first bank account →</button>
                @endcan
            </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
