@extends('layouts.app')
@section('title', 'Fast-Moving Inventory')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Fast-Moving Inventory</h1>
            <p class="text-sm text-gray-500 mt-1">Top-selling items ranked by units sold in the selected period</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.reports.fast-moving.pdf', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-red-300 text-red-700 bg-white hover:bg-red-50">PDF</a>
            <a href="{{ route('inventory.reports.fast-moving.excel', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-green-300 text-green-700 bg-white hover:bg-green-50">Excel</a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white shadow rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Period</label>
            <select name="days" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
                @foreach([30 => 'Last 30 days', 60 => 'Last 60 days', 90 => 'Last 90 days', 180 => 'Last 180 days', 365 => 'Last 12 months'] as $val => $label)
                <option value="{{ $val }}" {{ $filters['days'] == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-1.5 bg-naija-green text-white text-sm rounded hover:bg-green-700">Apply</button>
        <a href="{{ route('inventory.reports.fast-moving') }}" class="px-4 py-1.5 text-sm text-gray-600 hover:underline">Reset</a>
    </form>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Items with Sales</p>
            <p class="text-xl font-bold text-gray-800 mt-1">{{ $items->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Total Units Sold</p>
            <p class="text-xl font-bold text-naija-green mt-1">{{ number_format($items->sum('units_sold'), 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Total Revenue</p>
            <p class="text-xl font-bold text-blue-600 mt-1">₦{{ number_format($items->sum('revenue'), 2) }}</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-naija-green text-white">
                    <tr>
                        <th class="px-4 py-3 text-center font-semibold w-12">#</th>
                        <th class="px-4 py-3 text-left font-semibold">Item</th>
                        <th class="px-4 py-3 text-left font-semibold">Category</th>
                        <th class="px-4 py-3 text-right font-semibold">Units Sold</th>
                        <th class="px-4 py-3 text-right font-semibold">Transactions</th>
                        <th class="px-4 py-3 text-right font-semibold">Revenue (₦)</th>
                        <th class="px-4 py-3 text-right font-semibold">Avg Daily Usage</th>
                        <th class="px-4 py-3 text-right font-semibold">Current Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $i => $item)
                    @php $top3 = $i < 3; @endphp
                    <tr class="{{ $top3 ? 'bg-amber-50' : 'hover:bg-gray-50' }}">
                        <td class="px-4 py-3 text-center font-bold {{ $top3 ? 'text-amber-600' : 'text-gray-400' }}">
                            {{ $i + 1 }}
                            @if($i === 0) 🥇 @elseif($i === 1) 🥈 @elseif($i === 2) 🥉 @endif
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <a href="{{ route('inventory.items.show', $item) }}" class="hover:underline text-naija-green">{{ $item->name }}</a>
                            @if($item->sku)<div class="text-xs text-gray-400">{{ $item->sku }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-gray-900">{{ number_format($item->units_sold, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-gray-600">{{ number_format($item->transaction_count) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-naija-green font-semibold">{{ number_format($item->revenue, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-gray-500">{{ number_format($item->avg_daily_usage, 3) }}</td>
                        <td class="px-4 py-3 text-right font-mono {{ $item->current_stock <= $item->restock_level ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                            {{ number_format($item->current_stock, 2) }}
                            @if($item->current_stock <= $item->restock_level)
                            <span class="ml-1 text-xs text-red-500">⚠</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No sales recorded in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4 text-right">Generated {{ now()->format('d M Y H:i') }}</p>
</div>
@endsection
