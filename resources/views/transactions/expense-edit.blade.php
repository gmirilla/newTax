@extends('layouts.app')

@section('page-title', 'Edit Expense')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    <div class="flex items-center justify-between">
        <a href="{{ route('transactions.expenses') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Expenses</a>
        <span class="text-xs font-mono text-gray-400">{{ $expense->reference }}</span>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-5">Edit Expense</h2>

        <form method="POST" action="{{ route('transactions.expenses.update', $expense) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-xs font-medium text-gray-700">Date *</label>
                    <input type="date" name="expense_date"
                           value="{{ old('expense_date', $expense->expense_date?->toDateString()) }}"
                           required class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700">Category *</label>
                    <select name="category" required class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                        @foreach(['rent','utilities','salaries','transport','repairs','supplies','marketing','legal','insurance','other'] as $cat)
                            <option value="{{ $cat }}" {{ old('category', $expense->category) === $cat ? 'selected' : '' }}>
                                {{ ucfirst($cat) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700">Amount (₦) *</label>
                    <input type="number" name="amount" min="0.01" step="0.01"
                           value="{{ old('amount', $expense->amount) }}" required
                           class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700">Account *</label>
                    <select name="account_id" required class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                        <option value="">— Select Account —</option>
                        @foreach($accounts as $acct)
                            <option value="{{ $acct->id }}" {{ old('account_id', $expense->account_id) == $acct->id ? 'selected' : '' }}>
                                {{ $acct->code }} – {{ $acct->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700">Description *</label>
                    <input type="text" name="description" required
                           value="{{ old('description', $expense->description) }}"
                           class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700">Vendor</label>
                    <select name="vendor_id" class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                        <option value="">— No Vendor —</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ old('vendor_id', $expense->vendor_id) == $v->id ? 'selected' : '' }}>
                                {{ $v->name }} — {{ $v->wht_exempt ? 'WHT Exempt' : 'WHT: '.$v->wht_rate.'%' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-0.5">WHT is recalculated on save</p>
                </div>

                <div class="md:col-span-2 flex items-center gap-4">
                    <label class="flex items-center gap-2 text-xs">
                        <input type="checkbox" name="vat_applicable" value="1"
                               {{ old('vat_applicable', $expense->vat_applicable) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-green-600">
                        Input VAT (7.5%)
                    </label>
                </div>

                {{-- Receipt --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700">Receipt</label>
                    @if($expense->receipt_path)
                        <div class="mt-1 flex items-center gap-3 text-sm">
                            <a href="{{ Storage::url($expense->receipt_path) }}" target="_blank"
                               class="text-green-700 hover:underline text-xs">
                                📎 View current receipt
                            </a>
                            <span class="text-gray-400 text-xs">— upload below to replace it</span>
                        </div>
                    @endif
                    <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png"
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    <p class="text-xs text-gray-400 mt-0.5">PDF, JPG or PNG — max 4 MB</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700">Notes</label>
                    <input type="text" name="notes" value="{{ old('notes', $expense->notes) }}"
                           class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                </div>

            </div>

            <div class="flex gap-3 pt-2 border-t">
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                    Save Changes
                </button>
                <a href="{{ route('transactions.expenses') }}"
                   class="px-4 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
