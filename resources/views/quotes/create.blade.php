@extends('layouts.app')

@section('page-title', 'New Quote / Proforma Invoice')

@section('content')
<div x-data="quoteForm()" class="space-y-6" @keydown.escape="showNewCustomer = false">

    <form method="POST" action="{{ route('quotes.store') }}" @submit="calculateTotals">
        @csrf

        {{-- Header details --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Quote Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Customer *</label>
                    <div class="flex gap-2 mt-1">
                        <select name="customer_id" x-ref="customerSelect" required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                            <option value="">— Select Customer —</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" @click="showNewCustomer = true"
                                class="shrink-0 px-3 py-1.5 text-xs bg-green-50 border border-green-300 text-green-700 rounded-md hover:bg-green-100 whitespace-nowrap">
                            + New
                        </button>
                    </div>
                </div>

                {{-- New Customer Modal --}}
                <template x-teleport="body">
                    <div x-show="showNewCustomer" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                         @click.self="showNewCustomer = false">
                        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-base font-semibold">Add New Customer</h3>
                                <button type="button" @click="showNewCustomer = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-700">Customer Name *</label>
                                    <input type="text" x-model="newCustomer.name" placeholder="e.g. Dangote Industries Ltd"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Email</label>
                                    <input type="email" x-model="newCustomer.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Phone</label>
                                    <input type="text" x-model="newCustomer.phone" placeholder="+234..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                </div>
                                <div class="col-span-2 flex items-center gap-2">
                                    <input type="checkbox" x-model="newCustomer.is_company" class="rounded border-gray-300 text-green-600">
                                    <label class="text-xs text-gray-600">This is a company</label>
                                </div>
                            </div>
                            <p x-show="newCustomerError" x-text="newCustomerError" class="text-xs text-red-600"></p>
                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showNewCustomer = false"
                                        class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                                <button type="button" @click="saveNewCustomer()" :disabled="savingCustomer"
                                        class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
                                    <span x-text="savingCustomer ? 'Saving…' : 'Save Customer'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Quote Date *</label>
                    <input type="date" name="quote_date" value="{{ old('quote_date', now()->toDateString()) }}"
                           required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Valid Until *</label>
                    <input type="date" name="expiry_date" value="{{ old('expiry_date', now()->addDays(30)->toDateString()) }}"
                           required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Reference / PO Number</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" placeholder="Customer reference"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="flex items-center gap-6 mt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="vat_applicable" value="1" checked x-model="vatApplicable"
                               class="rounded border-gray-300 text-green-600">
                        <span class="text-sm font-medium text-gray-700">Apply VAT (7.5%)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="wht_applicable" value="1" x-model="whtApplicable"
                               class="rounded border-gray-300 text-green-600">
                        <span class="text-sm font-medium text-gray-700">WHT Deductible</span>
                    </label>
                </div>

                <div x-show="whtApplicable">
                    <label class="block text-sm font-medium text-gray-700">WHT Rate (%)</label>
                    <select name="wht_rate" x-model="whtRate"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="5">5% (Services/Contracts)</option>
                        <option value="10">10% (Rent/Dividends)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Line Items</h2>
                <button type="button" @click="addItem"
                        class="text-sm text-green-600 hover:text-green-800 font-medium">+ Add Item</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b text-xs font-medium text-gray-500 uppercase">
                            <th class="pb-2 text-left w-2/5">Description</th>
                            <th class="pb-2 text-right w-16">Qty</th>
                            <th class="pb-2 text-right w-32">Unit Price (₦)</th>
                            <th class="pb-2 text-right w-28">Subtotal (₦)</th>
                            <th class="pb-2 text-right w-24">VAT (₦)</th>
                            <th class="pb-2 text-right w-28">Total (₦)</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b">
                                <td class="py-2 pr-3">
                                    <input type="text" :name="`items[${index}][description]`"
                                           x-model="item.description" required placeholder="Service/product description"
                                           class="w-full border-gray-300 rounded text-sm focus:ring-green-500 focus:border-green-500">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" :name="`items[${index}][quantity]`"
                                           x-model="item.quantity" @input="calculateItem(index)" min="0.01" step="0.01"
                                           class="w-full border-gray-300 rounded text-sm text-right focus:ring-green-500 focus:border-green-500">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" :name="`items[${index}][unit_price]`"
                                           x-model="item.unitPrice" @input="calculateItem(index)" min="0" step="0.01"
                                           class="w-full border-gray-300 rounded text-sm text-right focus:ring-green-500 focus:border-green-500">
                                </td>
                                <td class="py-2 pr-2 text-right text-sm" x-text="fmt(item.subtotal)"></td>
                                <td class="py-2 pr-2 text-right text-sm text-blue-600" x-text="fmt(item.vatAmount)"></td>
                                <td class="py-2 pr-2 text-right text-sm font-medium" x-text="fmt(item.total)"></td>
                                <td class="py-2">
                                    <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 text-xs">✕</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totals & Notes --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Notes & Terms</h3>
                <textarea name="notes" rows="3" placeholder="Additional notes for the customer..."
                          class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500">{{ old('notes') }}</textarea>
                <textarea name="terms" rows="2" placeholder="Terms..."
                          class="mt-2 w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500">{{ old('terms', 'This quote is valid until the expiry date shown. Prices are subject to change after expiry.') }}</textarea>
                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-700">Discount (₦)</label>
                    <input type="number" name="discount_amount" x-model="discount" @input="calculateTotals"
                           min="0" step="0.01" value="{{ old('discount_amount', 0) }}"
                           class="mt-1 block w-full rounded border-gray-300 text-sm focus:ring-green-500 focus:border-green-500">
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Quote Summary</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd x-text="'₦' + fmtN(totals.subtotal)"></dd>
                    </div>
                    <div class="flex justify-between text-blue-700" x-show="vatApplicable">
                        <dt>VAT (7.5%)</dt>
                        <dd x-text="'₦' + fmtN(totals.vatAmount)"></dd>
                    </div>
                    <div class="flex justify-between text-orange-700" x-show="discount > 0">
                        <dt>Discount</dt>
                        <dd x-text="'- ₦' + fmtN(discount)"></dd>
                    </div>
                    <div class="flex justify-between text-red-700" x-show="whtApplicable">
                        <dt x-text="'WHT (' + whtRate + '%) — deducted by customer'"></dt>
                        <dd x-text="'- ₦' + fmtN(totals.whtAmount)"></dd>
                    </div>
                    <div class="flex justify-between border-t pt-2 font-bold text-lg">
                        <dt>Total</dt>
                        <dd class="text-green-700" x-text="'₦' + fmtN(totals.grandTotal)"></dd>
                    </div>
                </dl>

                <div class="mt-6 flex gap-3">
                    <button type="submit" name="status" value="draft"
                            class="flex-1 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                        Save as Draft
                    </button>
                    <button type="submit" name="status" value="sent"
                            class="flex-1 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                        Save & Mark Sent
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function quoteForm() {
    return {
        vatApplicable: true,
        whtApplicable: false,
        whtRate: 5,
        discount: 0,
        items: [{ description: '', quantity: 1, unitPrice: 0, subtotal: 0, vatAmount: 0, total: 0 }],
        totals: { subtotal: 0, vatAmount: 0, whtAmount: 0, grandTotal: 0 },
        showNewCustomer: false,
        savingCustomer: false,
        newCustomerError: '',
        newCustomer: { name: '', email: '', phone: '', is_company: true },

        async saveNewCustomer() {
            if (!this.newCustomer.name.trim()) { this.newCustomerError = 'Customer name is required.'; return; }
            this.savingCustomer = true; this.newCustomerError = '';
            try {
                const res = await fetch('{{ route('customers.quick-store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.newCustomer),
                });
                const data = await res.json();
                if (!res.ok) { this.newCustomerError = data.message || 'Failed to save.'; return; }
                const select = this.$refs.customerSelect;
                if (!select.querySelector(`option[value="${data.id}"]`)) select.add(new Option(data.name, data.id, true, true));
                select.value = data.id;
                this.showNewCustomer = false;
                this.newCustomer = { name: '', email: '', phone: '', is_company: true };
            } catch (e) { this.newCustomerError = 'Network error.'; } finally { this.savingCustomer = false; }
        },

        addItem() { this.items.push({ description: '', quantity: 1, unitPrice: 0, subtotal: 0, vatAmount: 0, total: 0 }); },
        removeItem(i) { if (this.items.length > 1) { this.items.splice(i, 1); this.calculateTotals(); } },

        calculateItem(i) {
            const item = this.items[i];
            item.subtotal  = Math.round(item.quantity * item.unitPrice * 100) / 100;
            item.vatAmount = this.vatApplicable ? Math.round(item.subtotal * 7.5) / 100 : 0;
            item.total     = item.subtotal + item.vatAmount;
            this.calculateTotals();
        },

        calculateTotals() {
            this.totals.subtotal   = this.items.reduce((s, i) => s + i.subtotal, 0);
            this.totals.vatAmount  = this.vatApplicable ? this.items.reduce((s, i) => s + i.vatAmount, 0) : 0;
            this.totals.whtAmount  = this.whtApplicable ? Math.round(this.totals.subtotal * this.whtRate) / 100 : 0;
            this.totals.grandTotal = this.totals.subtotal + this.totals.vatAmount - this.totals.whtAmount - (parseFloat(this.discount) || 0);
        },

        fmt(v)  { return '₦' + this.fmtN(v); },
        fmtN(v) { return Number(v || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 }); },
    };
}
</script>
@endpush
@endsection
