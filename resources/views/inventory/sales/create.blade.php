@extends('layouts.app')
@section('page-title', $order ? 'Edit Sales Order' : 'New Sales Order')

@push('styles')
<style>
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button { opacity: 1; }
</style>
@endpush

@section('content')
@php
    $itemsJson = $inventoryItems->map(fn($i) => [
        'id'            => $i->id,
        'name'          => $i->name,
        'sku'           => $i->sku,
        'unit'          => $i->unit,
        'selling_price' => (float) $i->selling_price,
        'avg_cost'      => (float) $i->avg_cost,
        'current_stock' => (float) $i->current_stock,
    ])->values()->toJson();

    $existingLines = $order
        ? $order->items->map(fn($l) => [
            'item_id'        => $l->item_id,
            'description'    => $l->description,
            'quantity'       => (float) $l->quantity,
            'unit_price'     => (float) $l->unit_price,
            'vat_applicable' => (bool) $l->vat_applicable,
        ])->values()->toJson()
        : '[]';
@endphp

<div x-data="salesForm({{ $itemsJson }}, {{ $existingLines }}, {{ $preselectedItemId ?: 0 }})">

    <form method="POST"
          action="{{ $order ? route('inventory.sales.update', $order) : route('inventory.sales.store') }}"
          @submit.prevent="submitForm($event)">
        @csrf
        @if($order) @method('PUT') @endif

        <div class="space-y-5">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900">
                    {{ $order ? 'Edit ' . $order->order_number : 'New Sales Order' }}
                </h1>
                <a href="{{ $order ? route('inventory.sales.show', $order) : route('inventory.sales.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- Left / Main --}}
                <div class="lg:col-span-2 space-y-5">

                    {{-- Order Details --}}
                    <div class="bg-white rounded-lg shadow p-5 space-y-4">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Order Details</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sale Date</label>
                                <input type="date" name="sale_date"
                                       value="{{ old('sale_date', $order?->sale_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                                       required
                                       class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                <select name="payment_method" x-model="paymentMethod"
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                    <option value="cash"          {{ old('payment_method', $order?->payment_method) === 'cash'          ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ old('payment_method', $order?->payment_method) === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="pos"           {{ old('payment_method', $order?->payment_method) === 'pos'           ? 'selected' : '' }}>POS</option>
                                    <option value="cheque"        {{ old('payment_method', $order?->payment_method) === 'cheque'        ? 'selected' : '' }}>Cheque</option>
                                    <option value="online"        {{ old('payment_method', $order?->payment_method) === 'online'        ? 'selected' : '' }}>Online</option>
                                </select>
                            </div>
                            <div x-show="paymentMethod !== 'cash'" x-cloak>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Reference</label>
                                <input type="text" name="payment_reference"
                                       value="{{ old('payment_reference', $order?->payment_reference) }}"
                                       placeholder="Transfer ref / cheque no."
                                       class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </div>
                        </div>
                    </div>

                    {{-- Line Items --}}
                    <div class="bg-white rounded-lg shadow p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Items Sold</h2>
                            <button type="button" @click="addLine()"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                                + Add Line
                            </button>
                        </div>

                        <template x-if="lines.length === 0">
                            <p class="text-sm text-gray-400 text-center py-4">No items yet. Click "Add Line" to begin.</p>
                        </template>

                        <div class="space-y-3">
                            <template x-for="(line, idx) in lines" :key="idx">
                                <div class="border border-gray-200 rounded-md p-3 space-y-3">

                                    {{-- Item selector + stock badge --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Item</label>
                                            <select :name="'items['+idx+'][item_id]'"
                                                    x-model="line.item_id"
                                                    @change="onItemChange(idx)"
                                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                                <option value="">— Select item —</option>
                                                <template x-for="item in catalog" :key="item.id">
                                                    <option :value="item.id" x-text="item.name + (item.sku ? ' ['+item.sku+']' : '')"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                            <input type="text" :name="'items['+idx+'][description]'"
                                                   x-model="line.description"
                                                   placeholder="Item description / notes"
                                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                        </div>
                                    </div>

                                    {{-- Stock info --}}
                                    <template x-if="line.item_id">
                                        <div class="text-xs text-gray-500 flex items-center gap-3">
                                            <span>
                                                In stock:
                                                <span :class="getStockClass(idx)"
                                                      x-text="getStock(line.item_id).toFixed(2) + ' ' + getUnit(line.item_id)">
                                                </span>
                                            </span>
                                            <span>Unit: <span x-text="getUnit(line.item_id)"></span></span>
                                            <span>Sell price: ₦<span x-text="getSellPrice(line.item_id).toFixed(2)"></span></span>
                                        </div>
                                    </template>

                                    {{-- Qty / Price / VAT --}}
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 items-end">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Quantity</label>
                                            <input type="number" :name="'items['+idx+'][quantity]'"
                                                   x-model.number="line.quantity"
                                                   @input="calcLine(idx)"
                                                   min="0.001" step="0.001"
                                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                                                   :class="line.quantity > getStock(line.item_id) ? 'border-red-400' : ''"
                                                   required>
                                            <p x-show="line.item_id && line.quantity > getStock(line.item_id)"
                                               class="text-xs text-red-600 mt-0.5">Exceeds available stock</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Unit Price (₦)</label>
                                            <input type="number" :name="'items['+idx+'][unit_price]'"
                                                   x-model.number="line.unit_price"
                                                   @input="calcLine(idx)"
                                                   min="0" step="0.01"
                                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                        </div>
                                        <div class="flex items-center gap-2 pt-4">
                                            <input type="checkbox" :name="'items['+idx+'][vat_applicable]'"
                                                   x-model="line.vat_applicable"
                                                   @change="calcLine(idx)"
                                                   value="1"
                                                   class="rounded border-gray-300 text-green-600">
                                            <label class="text-xs text-gray-600">VAT 7.5%</label>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500 mb-1">Line Total</p>
                                            <p class="font-semibold text-sm text-gray-900">₦<span x-text="line.total.toFixed(2)"></span></p>
                                            <button type="button" @click="removeLine(idx)"
                                                    class="text-xs text-red-500 hover:text-red-700 mt-1">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="bg-white rounded-lg shadow p-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                        <textarea name="notes" rows="2"
                                  class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                                  placeholder="Internal notes, delivery instructions…">{{ old('notes', $order?->notes) }}</textarea>
                    </div>
                </div>

                {{-- Right / Summary --}}
                <div class="space-y-5">

                    {{-- Customer --}}
                    <div class="bg-white rounded-lg shadow p-5 space-y-4">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Customer</h2>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Existing Customer</label>
                            <select name="customer_id"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">— Walk-in / No customer —</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        {{ old('customer_id', $order?->customer_id) == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Walk-in Name <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="customer_name"
                                   value="{{ old('customer_name', $order?->customer_name) }}"
                                   placeholder="e.g. John Doe"
                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </div>
                    </div>

                    {{-- Order Summary --}}
                    <div class="bg-white rounded-lg shadow p-5 space-y-3">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Order Summary</h2>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium">₦<span x-text="subtotal.toFixed(2)"></span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">VAT (7.5%)</span>
                                <span class="font-medium">₦<span x-text="vatTotal.toFixed(2)"></span></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <label class="text-gray-600">Discount</label>
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500">₦</span>
                                    <input type="number" name="discount_amount"
                                           x-model.number="discount"
                                           @input="calcTotals()"
                                           min="0" step="0.01" value="{{ old('discount_amount', $order?->discount_amount ?? 0) }}"
                                           class="w-28 rounded-md border-gray-300 text-sm shadow-sm text-right">
                                </div>
                            </div>
                            <div class="border-t pt-2 flex justify-between font-semibold text-base">
                                <span>Total</span>
                                <span>₦<span x-text="grandTotal.toFixed(2)"></span></span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="bg-white rounded-lg shadow p-5 space-y-3">
                        <button type="submit" name="action" value="draft"
                                class="w-full px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                            Save as Draft
                        </button>
                        <button type="submit" name="action" value="confirm"
                                class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                            Confirm & Generate Invoice
                        </button>
                        <p class="text-xs text-gray-400 text-center">
                            Confirming will reduce stock and post accounting entries.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden confirm flag --}}
        <input type="hidden" name="confirm" x-ref="confirmFlag" value="">
    </form>
</div>

@push('scripts')
<script>
function salesForm(catalog, existingLines, preselectedItemId) {
    const VAT_RATE = 0.075;

    return {
        catalog,
        paymentMethod: '{{ old('payment_method', $order?->payment_method ?? 'cash') }}',
        lines: [],
        discount: {{ old('discount_amount', $order?->discount_amount ?? 0) }},
        subtotal: 0,
        vatTotal: 0,
        grandTotal: 0,

        init() {
            if (existingLines.length > 0) {
                this.lines = existingLines.map(l => ({
                    item_id:        l.item_id,
                    description:    l.description,
                    quantity:       l.quantity,
                    unit_price:     l.unit_price,
                    vat_applicable: l.vat_applicable,
                    subtotal:       0,
                    vat:            0,
                    total:          0,
                }));
                this.lines.forEach((_, i) => this.calcLine(i));
            } else if (preselectedItemId > 0) {
                this.addLine(preselectedItemId);
            }
            this.calcTotals();
        },

        addLine(itemId = '') {
            this.lines.push({
                item_id: itemId || '',
                description: '',
                quantity: 1,
                unit_price: itemId ? this.getSellPrice(itemId) : 0,
                vat_applicable: false,
                subtotal: 0,
                vat: 0,
                total: 0,
            });
            const idx = this.lines.length - 1;
            if (itemId) {
                const item = this.catalog.find(i => i.id == itemId);
                if (item) this.lines[idx].description = item.name;
            }
            this.calcLine(idx);
        },

        removeLine(idx) {
            this.lines.splice(idx, 1);
            this.calcTotals();
        },

        onItemChange(idx) {
            const line = this.lines[idx];
            const item = this.catalog.find(i => i.id == line.item_id);
            if (item) {
                line.description = item.name;
                line.unit_price  = item.selling_price;
            }
            this.calcLine(idx);
        },

        calcLine(idx) {
            const line = this.lines[idx];
            const qty   = parseFloat(line.quantity)   || 0;
            const price = parseFloat(line.unit_price) || 0;
            line.subtotal = qty * price;
            line.vat      = line.vat_applicable ? line.subtotal * VAT_RATE : 0;
            line.total    = line.subtotal + line.vat;
            this.calcTotals();
        },

        calcTotals() {
            this.subtotal  = this.lines.reduce((s, l) => s + (l.subtotal || 0), 0);
            this.vatTotal  = this.lines.reduce((s, l) => s + (l.vat || 0), 0);
            this.grandTotal = this.subtotal + this.vatTotal - (parseFloat(this.discount) || 0);
        },

        getStock(itemId) {
            const item = this.catalog.find(i => i.id == itemId);
            return item ? item.current_stock : 0;
        },

        getSellPrice(itemId) {
            const item = this.catalog.find(i => i.id == itemId);
            return item ? item.selling_price : 0;
        },

        getUnit(itemId) {
            const item = this.catalog.find(i => i.id == itemId);
            return item ? item.unit : '';
        },

        getStockClass(idx) {
            const line = this.lines[idx];
            const stock = this.getStock(line.item_id);
            if (stock <= 0)              return 'text-red-600 font-semibold';
            if (line.quantity > stock)   return 'text-red-600 font-semibold';
            return 'text-green-700 font-medium';
        },

        submitForm(e) {
            const btn = e.submitter;
            if (btn && btn.value === 'confirm') {
                this.$refs.confirmFlag.value = '1';
            } else {
                this.$refs.confirmFlag.value = '';
            }
            e.target.submit();
        },
    };
}
</script>
@endpush
@endsection
