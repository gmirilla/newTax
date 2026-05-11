@extends('layouts.app')

@section('title', 'Low Stock Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Low Stock Report</h1>
            <p class="text-sm text-gray-500 mt-1">Items at or below their restock level</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.reports.low-stock.pdf') }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-red-300 text-red-700 bg-white hover:bg-red-50">
                PDF
            </a>
            <a href="{{ route('inventory.reports.low-stock.excel') }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-green-300 text-green-700 bg-white hover:bg-green-50">
                Excel
            </a>
            <a href="{{ route('inventory.restock.create') }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-naija-green hover:bg-green-700">
                + New Restock Request
            </a>
        </div>
    </div>

    @if($items->isEmpty())
    <div class="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
        <p class="text-green-700 font-semibold text-lg">All items are sufficiently stocked!</p>
        <p class="text-green-600 text-sm mt-1">No items are at or below their restock level.</p>
    </div>
    @else
    <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
        <strong>{{ $items->count() }} item(s)</strong> need attention.
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-naija-green text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Item</th>
                        <th class="px-4 py-3 text-left font-semibold">SKU</th>
                        <th class="px-4 py-3 text-right font-semibold">Current Stock</th>
                        <th class="px-4 py-3 text-right font-semibold">Restock Level</th>
                        <th class="px-4 py-3 text-right font-semibold">Shortfall</th>
                        <th class="px-4 py-3 text-left font-semibold">Last Restocked</th>
                        <th class="px-4 py-3 text-center font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $item)
                    <tr class="hover:bg-red-50">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <a href="{{ route('inventory.items.show', $item) }}" class="hover:underline text-naija-green">{{ $item->name }}</a>
                            <div class="text-xs text-gray-400">{{ $item->category?->name ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->sku ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono
                            {{ (float)$item->current_stock <= 0 ? 'text-red-600 font-bold' : 'text-amber-600 font-semibold' }}">
                            {{ number_format((float)$item->current_stock, 3) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-gray-600">{{ number_format((float)$item->restock_level, 3) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-red-600">{{ number_format($item->shortfall, 3) }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->last_restocked?->format('d M Y') ?? 'Never' }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('inventory.restock.create', ['item_id' => $item->id]) }}"
                               class="text-xs px-2 py-1 rounded border border-naija-green text-naija-green hover:bg-green-50">
                                Request Restock
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <p class="text-xs text-gray-400 mt-4 text-right">Generated {{ now()->format('d M Y H:i') }}</p>
</div>
@endsection
