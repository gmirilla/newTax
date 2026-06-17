@extends('layouts.app')
@section('page-title', 'New Stock Transfer')

@section('content')
<div class="max-w-2xl">

    <div class="mb-5">
        <a href="{{ route('inventory.transfers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Transfer History
        </a>
        <h1 class="text-lg font-semibold text-gray-900 mt-1">New Stock Transfer</h1>
        <p class="text-sm text-gray-500">Move stock between locations without affecting total inventory.</p>
    </div>

    @if($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6"
         x-data="{
             fromId: '{{ old('from_location_id', $active->id) }}',
             toId: '{{ old('to_location_id') }}',
             itemId: '{{ old('item_id') }}',
             qty: '',
             items: @js($items->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'sku' => $i->sku, 'unit' => $i->unit, 'current_stock' => (float)$i->current_stock])),
             get selectedItem() { return this.items.find(i => i.id == this.itemId) || null; },
             get sameLocation() { return this.fromId && this.toId && this.fromId === this.toId; }
         }">

        <form method="POST" action="{{ route('inventory.transfers.store') }}" class="space-y-5">
            @csrf

            {{-- From --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Location <span class="text-red-500">*</span></label>
                <select name="from_location_id" x-model="fromId" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">Select source location</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('from_location_id', $active->id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- To --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Location <span class="text-red-500">*</span></label>
                <select name="to_location_id" x-model="toId" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        :class="sameLocation ? 'border-red-400 bg-red-50' : ''">
                    <option value="">Select destination location</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('to_location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
                <p x-show="sameLocation" class="mt-1 text-xs text-red-600">Source and destination must be different.</p>
            </div>

            {{-- Item --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Item <span class="text-red-500">*</span></label>
                <select name="item_id" x-model="itemId" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">Select item to transfer</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" {{ old('item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->name }}{{ $item->sku ? ' ('.$item->sku.')' : '' }}
                        </option>
                    @endforeach
                </select>
                <p x-show="selectedItem" x-cloak class="mt-1 text-xs text-gray-500">
                    Total stock: <span class="font-medium" x-text="selectedItem?.current_stock + ' ' + selectedItem?.unit"></span>
                </p>
            </div>

            {{-- Quantity --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-2">
                    <input type="number" name="quantity" x-model="qty" required min="0.001" step="0.001"
                           value="{{ old('quantity') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <span class="text-sm text-gray-500 whitespace-nowrap" x-text="selectedItem?.unit || 'units'"></span>
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" maxlength="500"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
                          placeholder="Reason for transfer, reference number, etc.">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit"
                        :disabled="sameLocation"
                        class="btn-primary text-sm px-5 py-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
                    Transfer Stock
                </button>
                <a href="{{ route('inventory.transfers.index') }}"
                   class="text-sm px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
