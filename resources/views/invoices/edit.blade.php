@extends('layouts.app')

@section('page-title', 'Edit Invoice #' . $invoice->invoice_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="invoiceForm()">

    <div class="flex items-center justify-between">
        <a href="{{ route('invoices.show', $invoice) }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-base font-semibold mb-5">Edit Invoice #{{ $invoice->invoice_number }}</h2>

        <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Customer *</label>
                    <select name="customer_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ $invoice->customer_id == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Issue Date *</label>
                    <input type="date" name="issue_date" value="{{ $invoice->issue_date?->toDateString() ?? now()->toDateString() }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Due Date *</label>
                    <input type="date" name="due_date" value="{{ $invoice->due_date?->toDateString() ?? now()->addDays(30)->toDateString() }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Currency</label>
                    <select name="currency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="NGN" {{ $invoice->currency === 'NGN' ? 'selected' : '' }}>NGN (₦)</option>
                        <option value="USD" {{ $invoice->currency === 'USD' ? 'selected' : '' }}>USD ($)</option>
                    </select>
                </div>
            </div>

            {{-- Line Items --}}
            <div>
                <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider border-b pb-1 mb-3">Line Items</h3>
                <table class="w-full text-sm mb-3">
                    <thead>
                        <tr class="text-xs text-gray-500">
                            <th class="text-left pb-1 w-1/2">Description</th>
                            <th class="text-right pb-1 w-16">Qty</th>
                            <th class="text-right pb-1 w-32">Unit Price (₦)</th>
                            <th class="text-right pb-1 w-32">Amount</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b border-gray-100">
                                <td class="py-1 pr-2">
                                    <input type="text" :name="`items[${index}][description]`" x-model="item.description"
                                           placeholder="Service / product description"
                                           class="w-full border-gray-300 rounded text-sm px-2 py-1">
                                </td>
                                <td class="py-1 px-1">
                                    <input type="number" :name="`items[${index}][quantity]`" x-model.number="item.quantity"
                                           min="0.01" step="0.01" @input="calcItem(index)"
                                           class="w-16 border-gray-300 rounded text-sm px-2 py-1 text-right">
                                </td>
                                <td class="py-1 px-1">
                                    <input type="number" :name="`items[${index}][unit_price]`" x-model.number="item.unit_price"
                                           min="0" step="0.01" @input="calcItem(index)"
                                           class="w-32 border-gray-300 rounded text-sm px-2 py-1 text-right">
                                </td>
                                <td class="py-1 pl-2 text-right font-medium" x-text="'₦' + fmt(item.amount)"></td>
                                <td class="py-1 text-center">
                                    <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 text-lg leading-none">×</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <button type="button" @click="addItem()" class="text-sm text-green-600 hover:text-green-800">+ Add Line</button>
            </div>

            {{-- Tax Options --}}
            <div class="grid grid-cols-2 gap-4 border-t pt-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="vat_applicable" id="vat_applicable" value="1" x-model="vatApplicable"
                           {{ $invoice->vat_applicable ? 'checked' : '' }}
                           class="rounded border-gray-300 text-green-600">
                    <label for="vat_applicable" class="text-sm text-gray-700">Apply VAT (7.5%)</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="wht_applicable" id="wht_applicable" value="1" x-model="whtApplicable"
                           {{ $invoice->wht_applicable ? 'checked' : '' }}
                           class="rounded border-gray-300 text-yellow-600">
                    <label for="wht_applicable" class="text-sm text-gray-700">WHT Applicable</label>
                </div>
                <div x-show="whtApplicable">
                    <label class="block text-sm font-medium text-gray-700">WHT Rate</label>
                    <select name="wht_rate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="5" {{ $invoice->wht_rate == 5 ? 'selected' : '' }}>5% (Company contracts/services)</option>
                        <option value="10" {{ $invoice->wht_rate == 10 ? 'selected' : '' }}>10% (Individual services/rent)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Discount (₦)</label>
                    <input type="number" name="discount_amount" value="{{ $invoice->discount_amount }}" min="0" step="0.01"
                           x-model.number="discount" @input="calcTotals()"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            {{-- Totals Preview --}}
            <div class="bg-gray-50 rounded-lg p-4 text-sm space-y-1 max-w-xs ml-auto">
                <div class="flex justify-between">
                    <span class="text-gray-500">Subtotal</span>
                    <span x-text="'₦' + fmt(subtotal)">₦0.00</span>
                </div>
                <div class="flex justify-between" x-show="vatApplicable">
                    <span class="text-gray-500">VAT (7.5%)</span>
                    <span x-text="'₦' + fmt(vatAmount)" class="text-blue-600">₦0.00</span>
                </div>
                <div class="flex justify-between border-t pt-1 font-bold">
                    <span>Total</span>
                    <span x-text="'₦' + fmt(total)">₦0.00</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ $invoice->notes }}</textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('invoices.show', $invoice) }}"
                   class="px-4 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">Cancel</a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Update Invoice
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $invoiceItemsData = $invoice->items->map(fn($i) => [
        'description' => $i->description,
        'quantity'    => $i->quantity,
        'unit_price'  => $i->unit_price,
        'amount'      => $i->amount,
    ]);
@endphp
<script>
function invoiceForm() {
    return {
        items: @json($invoiceItemsData),
        vatApplicable: {{ $invoice->vat_applicable ? 'true' : 'false' }},
        whtApplicable: {{ $invoice->wht_applicable ? 'true' : 'false' }},
        discount: {{ $invoice->discount_amount }},
        subtotal: 0, vatAmount: 0, total: 0,
        init() { this.calcTotals(); },
        addItem() { this.items.push({ description: '', quantity: 1, unit_price: 0, amount: 0 }); },
        removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); this.calcTotals(); },
        calcItem(i) {
            this.items[i].amount = Math.round(this.items[i].quantity * this.items[i].unit_price * 100) / 100;
            this.calcTotals();
        },
        calcTotals() {
            this.subtotal = this.items.reduce((s, i) => s + (i.amount || 0), 0) - (this.discount || 0);
            this.vatAmount = this.vatApplicable ? Math.round(this.subtotal * 7.5) / 100 : 0;
            this.total = this.subtotal + this.vatAmount;
        },
        fmt(n) { return Number(n || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    }
}
</script>
@endsection
