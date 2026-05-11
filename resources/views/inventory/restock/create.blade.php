@extends('layouts.app')
@section('page-title', 'New Restock Request')

@section('content')
@php
    $itemsJson = $items->map(fn($i) => [
        'id'            => $i->id,
        'name'          => $i->name,
        'sku'           => $i->sku,
        'unit'          => $i->unit,
        'cost_price'    => (float) $i->cost_price,
        'current_stock' => (float) $i->current_stock,
        'restock_level' => (float) $i->restock_level,
    ])->values()->toJson();
@endphp

<div x-data="restockForm({{ $itemsJson }}, {{ $preselectedItemId ?: 0 }})">

    <form method="POST" action="{{ route('inventory.restock.store') }}">
        @csrf

        <div class="max-w-2xl mx-auto space-y-5">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900">New Restock Request</h1>
                <a href="{{ route('inventory.restock.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700">← Back to Requests</a>
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

            {{-- Item Selection --}}
            <div class="bg-white rounded-lg shadow p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Item to Restock</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item <span class="text-red-500">*</span></label>
                    <select name="item_id" x-model="selectedItemId" @change="onItemChange()"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                        <option value="">— Select item —</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}"
                                {{ old('item_id', $preselectedItemId) == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}{{ $item->sku ? ' [' . $item->sku . ']' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Item info box --}}
                <template x-if="selectedItemId">
                    <div class="bg-blue-50 border border-blue-100 rounded-md p-3 text-sm space-y-1">
                        <div class="grid grid-cols-3 gap-3 text-gray-700">
                            <div>
                                <p class="text-xs text-gray-500">Current Stock</p>
                                <p class="font-semibold"
                                   :class="getStock() <= getRestockLevel() ? 'text-red-600' : 'text-gray-900'">
                                    <span x-text="getStock().toFixed(3)"></span>
                                    <span x-text="getUnit()"></span>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Restock Level</p>
                                <p class="font-semibold text-gray-900">
                                    <span x-text="getRestockLevel().toFixed(3)"></span>
                                    <span x-text="getUnit()"></span>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Suggested Cost</p>
                                <p class="font-semibold text-gray-900">
                                    ₦<span x-text="getCostPrice().toFixed(2)"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Request Details --}}
            <div class="bg-white rounded-lg shadow p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Request Details</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Quantity Requested <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" name="quantity_requested"
                                   value="{{ old('quantity_requested') }}"
                                   x-model.number="quantity"
                                   @input="calcTotal()"
                                   min="0.001" step="0.001"
                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm pr-10" required>
                            <span class="absolute right-3 top-1.5 text-xs text-gray-400"
                                  x-text="getUnit()"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Estimated Unit Cost (₦) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="unit_cost"
                               value="{{ old('unit_cost') }}"
                               x-model.number="unitCost"
                               @input="calcTotal()"
                               min="0" step="0.01"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                    </div>
                </div>

                {{-- Estimated total --}}
                <div x-show="quantity > 0 && unitCost > 0"
                     class="text-right text-sm text-gray-700">
                    Estimated Total: <span class="font-semibold text-gray-900">₦<span x-text="(quantity * unitCost).toFixed(2)"></span></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier Name</label>
                    <input type="text" name="supplier_name"
                           value="{{ old('supplier_name') }}"
                           placeholder="e.g. ABC Distributors Ltd"
                           class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes / Justification</label>
                    <textarea name="notes" rows="3"
                              class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                              placeholder="Why is this restock needed? Any special requirements?">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('inventory.restock.index') }}"
                   class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Submit Request
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function restockForm(catalog, preselectedItemId) {
    return {
        catalog,
        selectedItemId: preselectedItemId || '',
        quantity: 0,
        unitCost: 0,

        init() {
            if (preselectedItemId) {
                this.selectedItemId = preselectedItemId;
                this.onItemChange();
            }
        },

        onItemChange() {
            const item = this.catalog.find(i => i.id == this.selectedItemId);
            if (item) {
                this.unitCost = item.cost_price;
                // Suggest quantity to reach restock level
                const shortfall = item.restock_level - item.current_stock;
                this.quantity = shortfall > 0 ? Math.ceil(shortfall) : 1;
            }
        },

        getStock()        { return this.catalog.find(i => i.id == this.selectedItemId)?.current_stock ?? 0; },
        getRestockLevel() { return this.catalog.find(i => i.id == this.selectedItemId)?.restock_level ?? 0; },
        getCostPrice()    { return this.catalog.find(i => i.id == this.selectedItemId)?.cost_price ?? 0; },
        getUnit()         { return this.catalog.find(i => i.id == this.selectedItemId)?.unit ?? ''; },
        calcTotal()       { /* reactive via x-text */ },
    };
}
</script>
@endpush
@endsection
