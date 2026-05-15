@extends('layouts.app')
@section('page-title', 'Edit Item')

@section('content')
<div class="max-w-3xl">

    <div class="mb-4">
        <a href="{{ route('inventory.items.show', $inventoryItem) }}" class="text-sm text-green-600 hover:underline">← Back to Item</a>
    </div>

    <form method="POST" action="{{ route('inventory.items.update', $inventoryItem) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic Info --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Item Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Item Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $inventoryItem->name) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">SKU / Item Code</label>
                    <input type="text" name="sku" value="{{ old('sku', $inventoryItem->sku) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                    @error('sku')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Item Type <span class="text-red-500">*</span></label>
                    <select name="item_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                        @foreach(App\Models\InventoryItem::ITEM_TYPES as $value => $label)
                            <option value="{{ $value }}" {{ old('item_type', $inventoryItem->item_type ?? 'product') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('item_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                        <option value="">— No Category —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $inventoryItem->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit of Measure <span class="text-red-500">*</span></label>
                    @if($units->isEmpty())
                        <input type="text" name="unit" value="{{ old('unit', $inventoryItem->unit) }}" required maxlength="30"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                        <p class="mt-1 text-xs text-yellow-600">
                            <a href="{{ route('inventory.units.index') }}" class="underline">Manage units of measure →</a>
                        </p>
                    @else
                        @php $currentUnit = old('unit', $inventoryItem->unit); @endphp
                        <select name="unit" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                            {{-- If the item's current unit isn't in the active list, still show it --}}
                            @if($units->pluck('name')->doesntContain($currentUnit))
                                <option value="{{ $currentUnit }}" selected>{{ $currentUnit }} (current)</option>
                            @endif
                            @foreach($units as $u)
                                <option value="{{ $u->name }}" {{ $currentUnit === $u->name ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">
                            <a href="{{ route('inventory.units.index') }}" class="text-green-600 hover:underline">Manage units →</a>
                        </p>
                    @endif
                    @error('unit')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">{{ old('description', $inventoryItem->description) }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2 flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $inventoryItem->is_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-green-600">
                    <label for="is_active" class="text-sm font-medium text-gray-700">Item is active (visible in sales)</label>
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Pricing</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Cost Price (₦) <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-400 mb-1">Reference cost (weighted average is updated on restocks)</p>
                    <input type="number" name="cost_price" value="{{ old('cost_price', $inventoryItem->cost_price) }}"
                           required min="0" step="0.01"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                    @error('cost_price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Selling Price (₦) <span class="text-red-500">*</span></label>
                    <input type="number" name="selling_price" value="{{ old('selling_price', $inventoryItem->selling_price) }}"
                           required min="0" step="0.01"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                    @error('selling_price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="bg-blue-50 rounded-md p-3 md:col-span-2">
                    <p class="text-xs text-blue-700">
                        <strong>Current Weighted Average Cost:</strong>
                        ₦{{ number_format($inventoryItem->avg_cost, 4) }} — updated automatically on every restock received.
                    </p>
                </div>
            </div>
        </div>

        {{-- Stock Settings --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Stock Settings</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="bg-gray-50 rounded-md p-3 text-sm">
                    <p class="text-gray-500 text-xs mb-0.5">Current Stock</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($inventoryItem->current_stock, 3) }} {{ $inventoryItem->unit }}</p>
                    <p class="text-xs text-gray-400 mt-1">Use the stock adjustment tool on the item detail page to change stock levels.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Restock Alert Level <span class="text-red-500">*</span></label>
                    <input type="number" name="restock_level" value="{{ old('restock_level', $inventoryItem->restock_level) }}"
                           required min="0" step="0.001"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                    @error('restock_level')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Save Changes
            </button>
            <a href="{{ route('inventory.items.show', $inventoryItem) }}"
               class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
        </div>

    </form>
</div>
@endsection
