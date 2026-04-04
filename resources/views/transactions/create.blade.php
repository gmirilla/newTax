@extends('layouts.app')

@section('page-title', 'New Journal Entry')

@section('content')
<div x-data="journalForm()" class="space-y-6">
    <form method="POST" action="{{ route('transactions.store') }}">
        @csrf

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold mb-4">Journal Entry Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date *</label>
                    <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}"
                           required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Type *</label>
                    <select name="type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        @foreach(['sale','purchase','expense','income','payment','receipt','journal','tax_payment'] as $type)
                            <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_',' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reference</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" placeholder="Auto-generated if blank"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Description *</label>
                    <input type="text" name="description" value="{{ old('description') }}" required
                           placeholder="What is this transaction for?"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
            </div>
        </div>

        {{-- Journal Lines --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold">Debit / Credit Lines</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Total debits must equal total credits (double-entry rule)</p>
                </div>
                <button type="button" @click="addLine"
                        class="text-sm text-green-600 hover:text-green-800 font-medium">+ Add Line</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b text-xs font-medium text-gray-500 uppercase">
                            <th class="pb-2 text-left w-2/5">Account</th>
                            <th class="pb-2 text-left w-20">Type</th>
                            <th class="pb-2 text-right w-36">Amount (₦)</th>
                            <th class="pb-2 text-left">Description</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b">
                                <td class="py-2 pr-3">
                                    <select :name="`entries[${index}][account_id]`" required x-model="line.account_id"
                                            class="w-full border-gray-300 rounded text-sm focus:ring-green-500 focus:border-green-500">
                                        <option value="">— Select Account —</option>
                                        @foreach($accounts->groupBy('type') as $type => $group)
                                            <optgroup label="{{ ucfirst($type) }}">
                                                @foreach($group as $account)
                                                    <option value="{{ $account->id }}">
                                                        {{ $account->code }} – {{ $account->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 pr-3">
                                    <select :name="`entries[${index}][entry_type]`" x-model="line.entry_type"
                                            class="w-full border-gray-300 rounded text-sm">
                                        <option value="debit">Debit (Dr)</option>
                                        <option value="credit">Credit (Cr)</option>
                                    </select>
                                </td>
                                <td class="py-2 pr-3">
                                    <input type="number" :name="`entries[${index}][amount]`"
                                           x-model="line.amount" @input="recalculate" min="0.01" step="0.01"
                                           class="w-full border-gray-300 rounded text-sm text-right focus:ring-green-500 focus:border-green-500">
                                </td>
                                <td class="py-2 pr-3">
                                    <input type="text" :name="`entries[${index}][description]`"
                                           x-model="line.description" placeholder="Line note"
                                           class="w-full border-gray-300 rounded text-sm focus:ring-green-500 focus:border-green-500">
                                </td>
                                <td class="py-2">
                                    <button type="button" @click="removeLine(index)"
                                            x-show="lines.length > 2"
                                            class="text-red-400 hover:text-red-600 text-xs">✕</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 font-medium text-sm">
                            <td colspan="2" class="pt-2 text-right text-gray-600">Totals:</td>
                            <td class="pt-2 pr-3">
                                <div class="text-right">
                                    <div class="text-green-700">Dr: ₦<span x-text="formatNum(totalDebits)"></span></div>
                                    <div class="text-blue-700">Cr: ₦<span x-text="formatNum(totalCredits)"></span></div>
                                </div>
                            </td>
                            <td colspan="2" class="pt-2">
                                <span x-show="!isBalanced" class="text-red-600 text-xs font-bold">
                                    ⚠️ Not balanced! Difference: ₦<span x-text="formatNum(Math.abs(totalDebits - totalCredits))"></span>
                                </span>
                                <span x-show="isBalanced" class="text-green-600 text-xs font-bold">
                                    ✅ Balanced
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('transactions.index') }}"
                   class="px-4 py-2 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" :disabled="!isBalanced"
                        :class="isBalanced ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300 cursor-not-allowed'"
                        class="px-6 py-2 text-white text-sm font-medium rounded-md">
                    Post Journal Entry
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function journalForm() {
    return {
        lines: [
            { account_id: '', entry_type: 'debit',  amount: 0, description: '' },
            { account_id: '', entry_type: 'credit', amount: 0, description: '' },
        ],
        get totalDebits()  { return this.lines.filter(l => l.entry_type === 'debit').reduce((s, l) => s + (+l.amount), 0); },
        get totalCredits() { return this.lines.filter(l => l.entry_type === 'credit').reduce((s, l) => s + (+l.amount), 0); },
        get isBalanced()   { return Math.abs(this.totalDebits - this.totalCredits) < 0.01 && this.totalDebits > 0; },

        addLine()  { this.lines.push({ account_id: '', entry_type: 'debit', amount: 0, description: '' }); },
        removeLine(i) { if (this.lines.length > 2) this.lines.splice(i, 1); },
        recalculate() {},
        formatNum(v) { return Number(v).toLocaleString('en-NG', { minimumFractionDigits: 2 }); },
    };
}
</script>
@endpush
@endsection
