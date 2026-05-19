@extends('superadmin.layout')

@section('page-title', 'New Invoice — ' . $tenant->name)

@section('content')
<div class="space-y-6 max-w-2xl">

    <div>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('superadmin.companies.show', $tenant) }}" class="hover:underline">{{ $tenant->name }}</a>
            <span>/</span>
            <a href="{{ route('superadmin.enterprises.invoices.index', $tenant) }}" class="hover:underline">Invoices</a>
            <span>/</span>
            <span>New</span>
        </div>
        <h1 class="text-xl font-bold text-gray-900">New Platform Invoice</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Agreement: <strong>{{ $agreement->plan?->name }}</strong> · ₦{{ number_format($agreement->negotiated_price, 0) }}/{{ $agreement->billing_cycle }}
        </p>
    </div>

    <form method="POST" action="{{ route('superadmin.enterprises.invoices.store', $tenant) }}">
        @csrf
        <input type="hidden" name="agreement_id" value="{{ $agreement->id }}">

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Period Start *</label>
                    <input type="date" name="period_start"
                           value="{{ old('period_start') }}" required
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('period_start')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Period End *</label>
                    <input type="date" name="period_end"
                           value="{{ old('period_end') }}" required
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('period_end')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Amount (₦) *</label>
                    <input type="number" name="amount" min="0" step="0.01"
                           value="{{ old('amount', $agreement->negotiated_price) }}" required
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Due Date *</label>
                    <input type="date" name="due_date"
                           value="{{ old('due_date', today()->addDays($agreement->payment_terms_days)->format('Y-m-d')) }}" required
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('due_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                <textarea name="notes" rows="3"
                          placeholder="Itemised charges, adjustments, or any notes to include on the invoice..."
                          class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="flex gap-3 mt-4">
            <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                Create Invoice
            </button>
            <a href="{{ route('superadmin.enterprises.invoices.index', $tenant) }}"
               class="px-6 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
