@extends('layouts.app')

@section('page-title', 'Edit Quote ' . $quote->quote_number)

@section('content')

@php
    $quoteItemsData = $quote->items->map(fn($i) => [
        'description' => $i->description,
        'quantity'    => $i->quantity,
        'unit_price'  => $i->unit_price,
        'subtotal'    => $i->subtotal,
        'vatAmount'   => $i->vat_amount,
        'total'       => $i->total,
    ]);
@endphp

<div x-data="quoteEditForm()" class="space-y-6">

    <form method="POST" action="{{ route('quotes.update', $quote) }}" @submit="calculateTotals">
        @csrf @method('PUT')

        {{-- Header details --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Quote Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Customer *</label>
                    <select name="customer_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ $quote->customer_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Quote Date *</label>
                    <input type="date" name="quote_date" value="{{ $quote->quote_date?->toDateString() ?? now()->toDateString() }}"
                           required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Valid Until *</label>
                    <input type="date" name="expiry_date" value="{{ $quote->expiry_date?->toDateString() ?? now()->addDays(30)->toDateString() }}"
                           required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Reference</label>
                    <input type="text" name="reference" value="{{ $quote->reference }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="flex items-center gap-6 mt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="vat_applicable" value="1" x-model="vatApplicable"
                               {{ $quote->vat_applicable ? 'checked' : '' }}
                               class="rounded border-gray-300 text-green-600">
                        <span class="text-sm font-medium text-gray-700">Apply VAT (7.5%)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="wht_applicable" value="1" x-model="whtApplicable"
                               {{ $quote->wht_applicable ? 'checked' : '' }}
                               class="rounded border-gray-300 text-green-600">
                        <span class="text-sm font-medium text-gray-700">WHT Deductible</span>
                    </label>
                </div>

                <div x-show="whtApplicable">
                    <label class="block text-sm font-medium text-gray-700">WHT Rate (%)</label>
                    <select name="wht_rate" x-model="whtRate"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="5" {{ $quote->wht_rate == 5 ? 'selected' : '' }}>5% (Services/Contracts)</option>
                        <option value="10" {{ $quote->wht_rate == 10 ? 'selected' : '' }}>10% (Rent/Dividends)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Line Items</h2>
                <button type="button" @click="addItem" class="text-sm text-green-600 hover:text-green-800 font-medium">+ Add Item</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b text-xs font-medium text-gray-500 uppercase">
                            <th class="pb-2 text-left w-2/5">Description</th>
                            <th class="pb-2 text-right w-16">Qty</th>
                            <th class="pb-2 text-right w-32">Unit Price (₦)</th>
                            <th class="pb-2 text-right w-28">Subtotal</th>
                            <th class="pb-2 text-right w-24">VAT</th>
                            <th class="pb-2 text-right w-28">Total</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b">
                                <td class="py-2 pr-3">
                                    <input type="text" :name="`items[${index}][description]`" x-model="item.description"
                                           required class="w-full border-gray-300 rounded text-sm focus:ring-green-500 focus:border-green-500">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity"
                                           @input="calculateItem(index)" min="0.01" step="0.01"
                                           class="w-full border-gray-300 rounded text-sm text-right focus:ring-green-500 focus:border-green-500">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" :name="`items[${index}][unit_price]`" x-model="item.unit_price"
                                           @input="calculateItem(index)" min="0" step="0.01"
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
                <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500">{{ $quote->notes }}</textarea>
                <textarea name="terms" rows="2" class="mt-2 w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500">{{ $quote->terms }}</textarea>
                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-700">Discount (₦)</label>
                    <input type="number" name="discount_amount" x-model="discount" @input="calculateTotals"
                           min="0" step="0.01" value="{{ $quote->discount_amount }}"
                           class="mt-1 block w-full rounded border-gray-300 text-sm focus:ring-green-500 focus:border-green-500">
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Summary</h3>
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
                        <dt x-text="'WHT (' + whtRate + '%)'"></dt>
                        <dd x-text="'- ₦' + fmtN(totals.whtAmount)"></dd>
                    </div>
                    <div class="flex justify-between border-t pt-2 font-bold text-lg">
                        <dt>Total</dt>
                        <dd class="text-green-700" x-text="'₦' + fmtN(totals.grandTotal)"></dd>
                    </div>
                </dl>
                <div class="mt-6 flex gap-3">
                    <a href="{{ route('quotes.show', $quote) }}"
                       class="flex-1 py-2 text-center border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="flex-1 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function quoteEditForm() {
    return {
        vatApplicable: {{ $quote->vat_applicable ? 'true' : 'false' }},
        whtApplicable: {{ $quote->wht_applicable ? 'true' : 'false' }},
        whtRate: {{ $quote->wht_rate ?: 5 }},
        discount: {{ $quote->discount_amount }},
        items: @json($quoteItemsData),
        totals: { subtotal: 0, vatAmount: 0, whtAmount: 0, grandTotal: 0 },

        init() { this.calculateTotals(); },

        addItem() { this.items.push({ description: '', quantity: 1, unit_price: 0, subtotal: 0, vatAmount: 0, total: 0 }); },
        removeItem(i) { if (this.items.length > 1) { this.items.splice(i, 1); this.calculateTotals(); } },

        calculateItem(i) {
            const item = this.items[i];
            item.subtotal  = Math.round(item.quantity * item.unit_price * 100) / 100;
            item.vatAmount = this.vatApplicable ? Math.round(item.subtotal * 7.5) / 100 : 0;
            item.total     = item.subtotal + item.vatAmount;
            this.calculateTotals();
        },

        calculateTotals() {
            this.totals.subtotal   = this.items.reduce((s, i) => s + (i.subtotal || 0), 0);
            this.totals.vatAmount  = this.vatApplicable ? this.items.reduce((s, i) => s + (i.vatAmount || 0), 0) : 0;
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
