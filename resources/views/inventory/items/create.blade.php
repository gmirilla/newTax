@extends('layouts.app')
@section('page-title', 'New Inventory Item')

@section('content')
<div class="max-w-3xl">

    <div class="mb-4">
        <a href="{{ route('inventory.items.index') }}" class="text-sm text-green-600 hover:underline">← Back to Items</a>
    </div>

    <form method="POST" action="{{ route('inventory.items.store') }}" class="space-y-6">
        @csrf

        {{-- Basic Info --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Item Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Item Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                           placeholder="e.g. Bag of Cement 50kg">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">SKU / Item Code</label>
                    <input type="text" name="sku" value="{{ old('sku') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                           placeholder="e.g. CEM-50KG (optional)">
                    @error('sku')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                        <option value="">— No Category —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit of Measure <span class="text-red-500">*</span></label>
                    <input type="text" name="unit" value="{{ old('unit', 'piece') }}" required
                           list="unit-options"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                           placeholder="piece, kg, carton, litre…">
                    <datalist id="unit-options">
                        <option value="piece">
                        <option value="pair">
                        <option value="kg">
                        <option value="g">
                        <option value="litre">
                        <option value="ml">
                        <option value="carton">
                        <option value="bag">
                        <option value="box">
                        <option value="roll">
                        <option value="metre">
                        <option value="set">
                    </datalist>
                    @error('unit')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                              placeholder="Optional product description">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Pricing</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Cost Price (₦) <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-400 mb-1">What you pay to acquire the item</p>
                    <input type="number" name="cost_price" value="{{ old('cost_price', '0.00') }}" required
                           min="0" step="0.01"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                    @error('cost_price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Selling Price (₦) <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-400 mb-1">What you charge customers</p>
                    <input type="number" name="selling_price" value="{{ old('selling_price', '0.00') }}" required
                           min="0" step="0.01"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                    @error('selling_price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Stock Settings --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Stock Settings</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Opening Stock Quantity</label>
                    <p class="text-xs text-gray-400 mb-1">Current quantity on hand (leave 0 if none yet)</p>
                    <input type="number" name="opening_stock" value="{{ old('opening_stock', '0') }}"
                           min="0" step="0.001"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                    @error('opening_stock')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Restock Alert Level <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-400 mb-1">Get alerted when stock falls to this quantity</p>
                    <input type="number" name="restock_level" value="{{ old('restock_level', '5') }}" required
                           min="0" step="0.001"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 text-sm">
                    @error('restock_level')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Create Item
            </button>
            <a href="{{ route('inventory.items.index') }}"
               class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
        </div>

    </form>
</div>
@endsection
