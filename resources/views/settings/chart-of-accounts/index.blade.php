@extends('layouts.app')
@section('page-title', 'Chart of Accounts')

@php
    $typeGroups = [
        'asset'     => 'Assets (1xxx)',
        'liability' => 'Liabilities (2xxx)',
        'equity'    => 'Equity (3xxx)',
        'revenue'   => 'Revenue (4xxx)',
        'expense'   => 'Expenses (5xxx)',
    ];
@endphp

@section('content')
<div x-data="{ showForm: false }" class="max-w-4xl space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Chart of Accounts</h1>
            <p class="text-xs text-gray-500 mt-0.5">System accounts (marked 🔒) can be renamed and deactivated but never recoded or deleted — the rest of the app relies on their exact code and type.</p>
        </div>
        @can('create', App\Models\Account::class)
        <button @click="showForm = !showForm"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 whitespace-nowrap">
            + Add Account
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
    @can('create', App\Models\Account::class)
    <div x-show="showForm" x-cloak class="bg-white rounded-lg shadow p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Add Account</h2>
        <form method="POST" action="{{ route('settings.chart-of-accounts.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="150"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="e.g. Equipment Lease Expense">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Type <span class="text-red-500">*</span></label>
                    <select name="type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        @foreach($typeGroups as $value => $label)
                            <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>{{ ucfirst($value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required maxlength="20"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="e.g. 1600">
                    <p class="mt-0.5 text-xs text-gray-400">Must start with 1 (Asset), 2 (Liability), 3 (Equity), 4 (Revenue), or 5 (Expense) to match the type above.</p>
                    @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sub-type</label>
                    <select name="sub_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        <option value="">None</option>
                        @foreach(\App\Http\Controllers\AccountController::SUB_TYPES as $subType)
                            <option value="{{ $subType }}" {{ old('sub_type') === $subType ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $subType)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="2" maxlength="500"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">{{ old('description') }}</textarea>
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

    {{-- Account groups by type --}}
    @foreach($typeGroups as $type => $label)
    @continue($accounts->get($type, collect())->isEmpty())
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-base font-semibold text-gray-900">{{ $label }}</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($accounts->get($type, collect()) as $account)
            <div x-data="{ editing: false }" class="px-6 py-4">

                {{-- View row --}}
                <div x-show="!editing" class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-xs text-gray-400">{{ $account->code }}</span>
                            <p class="text-sm font-semibold text-gray-900">{{ $account->name }}</p>
                            @if($account->is_system)
                                <span title="System account — code and type are locked" class="text-xs">🔒</span>
                            @endif
                            @unless($account->is_active)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-gray-100 text-gray-500">Inactive</span>
                            @endunless
                        </div>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-500">
                            @if($account->sub_type)
                                <span>{{ ucwords(str_replace('_', ' ', $account->sub_type)) }}</span>
                            @endif
                            <span>{{ $account->journal_entries_count }} {{ Str::plural('entry', $account->journal_entries_count) }}</span>
                        </div>
                        @if($account->description)
                            <p class="mt-1 text-xs text-gray-400 italic">{{ $account->description }}</p>
                        @endif
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-sm font-semibold {{ (float) $account->current_balance >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                            ₦{{ number_format((float) $account->current_balance, 2) }}
                        </p>
                    </div>
                </div>

                <div x-show="!editing" class="mt-3 flex items-center gap-3">
                    @can('update', $account)
                    <button @click="editing = true" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                    @endcan
                    @can('delete', $account)
                    <form method="POST" action="{{ route('settings.chart-of-accounts.destroy', $account) }}"
                          class="inline ml-auto"
                          onsubmit="return confirm('Delete {{ addslashes($account->name) }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                    </form>
                    @endcan
                </div>

                {{-- Inline edit form --}}
                @can('update', $account)
                <div x-show="editing" x-cloak>
                    <form method="POST" action="{{ route('settings.chart-of-accounts.update', $account) }}" class="space-y-3 mt-2">
                        @csrf @method('PATCH')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Name</label>
                                <input type="text" name="name" value="{{ $account->name }}" required maxlength="150"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            </div>
                            @if($account->is_system)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Code / Type</label>
                                    <p class="mt-1 text-sm text-gray-500">{{ $account->code }} · {{ ucfirst($account->type) }} (locked)</p>
                                </div>
                            @else
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Type</label>
                                    <select name="type" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                        @foreach($typeGroups as $value => $groupLabel)
                                            <option value="{{ $value }}" {{ $account->type === $value ? 'selected' : '' }}>{{ ucfirst($value) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Code</label>
                                    <input type="text" name="code" value="{{ $account->code }}" required maxlength="20"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                </div>
                            @endif
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Sub-type</label>
                                <select name="sub_type"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="">None</option>
                                    @foreach(\App\Http\Controllers\AccountController::SUB_TYPES as $subType)
                                        <option value="{{ $subType }}" {{ $account->sub_type === $subType ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $subType)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Description</label>
                                <input type="text" name="description" value="{{ $account->description }}" maxlength="500"
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
                            <button type="submit" class="px-4 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Save</button>
                            <button type="button" @click="editing = false" class="px-4 py-1.5 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                        </div>
                    </form>
                </div>
                @endcan

            </div>
            @endforeach
        </div>
    </div>
    @endforeach

</div>
@endsection
