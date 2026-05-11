@extends('layouts.app')

@section('title', 'Stock Valuation Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Stock Valuation</h1>
            <p class="text-sm text-gray-500 mt-1">Current inventory value at weighted average cost</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.reports.stock-valuation.pdf', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-red-300 text-red-700 bg-white hover:bg-red-50">
                PDF
            </a>
            <a href="{{ route('inventory.reports.stock-valuation.excel', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-green-300 text-green-700 bg-white hover:bg-green-50">
                Excel
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Items</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totals['total_items']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Stock Value</p>
            <p class="text-2xl font-bold text-naija-green mt-1">₦{{ number_format($totals['total_stock_value'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Potential Revenue</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">₦{{ number_format($totals['total_potential_revenue'], 2) }}</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-naija-green text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">#</th>
                        <th class="px-4 py-3 text-left font-semibold">Item</th>
                        <th class="px-4 py-3 text-left font-semibold">Category</th>
                        <th class="px-4 py-3 text-left font-semibold">Unit</th>
                        <th class="px-4 py-3 text-right font-semibold">Qty on Hand</th>
                        <th class="px-4 py-3 text-right font-semibold">Avg Cost (₦)</th>
                        <th class="px-4 py-3 text-right font-semibold">Stock Value (₦)</th>
                        <th class="px-4 py-3 text-right font-semibold">Selling Price (₦)</th>
                        <th class="px-4 py-3 text-right font-semibold">Potential Revenue (₦)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $i => $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <a href="{{ route('inventory.items.show', $item) }}" class="hover:underline text-naija-green">{{ $item->name }}</a>
                            @if($item->sku)<span class="text-xs text-gray-400 ml-1">({{ $item->sku }})</span>@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->unit ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float)$item->current_stock, 3) }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float)$item->avg_cost, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-naija-green">{{ number_format($item->stock_value, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float)$item->selling_price, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-blue-600">{{ number_format($item->potential_revenue, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-400">No active inventory items found.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($items->count())
                <tfoot class="bg-green-50 font-semibold">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-right border-t border-gray-300">TOTALS</td>
                        <td class="px-4 py-3 text-right font-mono text-naija-green border-t border-gray-300">₦{{ number_format($totals['total_stock_value'], 2) }}</td>
                        <td class="px-4 py-3 border-t border-gray-300"></td>
                        <td class="px-4 py-3 text-right font-mono text-blue-600 border-t border-gray-300">₦{{ number_format($totals['total_potential_revenue'], 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4 text-right">Generated {{ now()->format('d M Y H:i') }}</p>
</div>
@endsection
