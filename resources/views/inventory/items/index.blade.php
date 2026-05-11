@extends('layouts.app')
@section('page-title', 'Inventory Items')

@section('content')
<div class="space-y-5">

    {{-- Low stock / out-of-stock alert banner --}}
    @include('inventory.partials.alert-banner')

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Items</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Stock Value</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₦{{ number_format($stats['stock_value'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Low Stock</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['low_stock']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Out of Stock</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['out_stock']) }}</p>
        </div>
    </div>

    {{-- Table card --}}
    <div class="bg-white shadow rounded-lg">

        {{-- Header --}}
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Item Catalog</h2>
            <div class="flex gap-2">
                @can('create', App\Models\InventoryItem::class)
                <a href="{{ route('inventory.categories.index') }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                    🗂️ Categories
                </a>
                <a href="{{ route('inventory.items.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    + New Item
                </a>
                @endcan
            </div>
        </div>

        {{-- Filters --}}
        <div class="px-6 py-3 bg-gray-50 border-b">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search name, SKU…"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 w-52">

                <select name="category" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <select name="stock_status" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All Stock Levels</option>
                    <option value="ok"  {{ request('stock_status') === 'ok'  ? 'selected' : '' }}>In Stock</option>
                    <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out" {{ request('stock_status') === 'out' ? 'selected' : '' }}>Out of Stock</option>
                </select>

                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="checkbox" name="inactive" value="1" {{ request('inactive') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-green-600">
                    Show inactive
                </label>

                <button type="submit"
                        class="px-4 py-1.5 bg-gray-700 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'category', 'stock_status', 'inactive']))
                    <a href="{{ route('inventory.items.index') }}"
                       class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">In Stock</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Restock Level</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Cost (₦)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Selling Price (₦)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stock Value (₦)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($items as $item)
                    @php
                        $stockNum = (float) $item->current_stock;
                        $levelNum = (float) $item->restock_level;
                        if ($stockNum <= 0) {
                            $stockColor = 'red';   $stockLabel = 'Out of Stock';
                        } elseif ($levelNum > 0 && $stockNum <= $levelNum) {
                            $stockColor = 'yellow'; $stockLabel = 'Low Stock';
                        } else {
                            $stockColor = 'green';  $stockLabel = 'In Stock';
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 text-sm">{{ $item->name }}</div>
                            @if($item->sku)
                                <div class="text-xs text-gray-400">SKU: {{ $item->sku }}</div>
                            @endif
                            <div class="text-xs text-gray-400">{{ $item->unit }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $item->category?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                bg-{{ $stockColor }}-100 text-{{ $stockColor }}-800">
                                {{ number_format($stockNum, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-600">
                            {{ number_format($levelNum, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">
                            {{ number_format($item->avg_cost, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                            {{ number_format($item->selling_price, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">
                            {{ number_format($stockNum * (float)$item->avg_cost, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold
                                {{ $item->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                                {{ $item->is_active ? $stockLabel : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                            <a href="{{ route('inventory.items.show', $item) }}"
                               class="text-green-600 hover:text-green-800 font-medium mr-3">View</a>
                            @can('update', $item)
                            <a href="{{ route('inventory.items.edit', $item) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium mr-3">Edit</a>
                            @endcan
                            @can('delete', $item)
                            <form method="POST" action="{{ route('inventory.items.destroy', $item) }}"
                                  class="inline"
                                  onsubmit="return confirm('Remove {{ addslashes($item->name) }} from catalog?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Remove</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-400">
                            <p class="text-sm">No items found.</p>
                            @can('create', App\Models\InventoryItem::class)
                                <a href="{{ route('inventory.items.create') }}" class="text-green-600 text-sm hover:underline mt-1 inline-block">
                                    Add your first item →
                                </a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($items->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $items->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
