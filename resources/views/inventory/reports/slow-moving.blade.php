@extends('layouts.app')
@section('title', 'Slow-Moving Inventory')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Slow-Moving Inventory</h1>
            <p class="text-sm text-gray-500 mt-1">Items with low or zero sales velocity in the selected period</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.reports.slow-moving.pdf', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-red-300 text-red-700 bg-white hover:bg-red-50">PDF</a>
            <a href="{{ route('inventory.reports.slow-moving.excel', request()->query()) }}"
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
        <a href="{{ route('inventory.reports.slow-moving') }}" class="px-4 py-1.5 text-sm text-gray-600 hover:underline">Reset</a>
    </form>

    {{-- Summary cards --}}
    @php
        $deadStock  = $items->where('velocity_label', 'Dead Stock')->count();
        $slowCount  = $items->where('velocity_label', 'Slow')->count();
        $totalValue = $items->sum(fn($i) => $i->current_stock * $i->avg_cost);
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Total Items</p>
            <p class="text-xl font-bold text-gray-800 mt-1">{{ $items->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Dead Stock</p>
            <p class="text-xl font-bold text-red-600 mt-1">{{ $deadStock }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Slow Moving</p>
            <p class="text-xl font-bold text-amber-600 mt-1">{{ $slowCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Stock Value at Risk</p>
            <p class="text-xl font-bold text-gray-700 mt-1">₦{{ number_format($totalValue, 2) }}</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-naija-green text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Item</th>
                        <th class="px-4 py-3 text-left font-semibold">Category</th>
                        <th class="px-4 py-3 text-right font-semibold">Current Stock</th>
                        <th class="px-4 py-3 text-right font-semibold">Units Sold</th>
                        <th class="px-4 py-3 text-right font-semibold">Avg Daily Usage</th>
                        <th class="px-4 py-3 text-center font-semibold">Days Since Last Sale</th>
                        <th class="px-4 py-3 text-center font-semibold">Velocity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $item)
                    @php
                        $velColor = match($item->velocity_label) {
                            'Dead Stock' => 'bg-red-100 text-red-800',
                            'Slow'       => 'bg-amber-100 text-amber-800',
                            'Moderate'   => 'bg-blue-100 text-blue-800',
                            default      => 'bg-green-100 text-green-800',
                        };
                        $rowBg = $item->velocity_label === 'Dead Stock' ? 'bg-red-50' : 'hover:bg-gray-50';
                    @endphp
                    <tr class="{{ $rowBg }}">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <a href="{{ route('inventory.items.show', $item) }}" class="hover:underline text-naija-green">{{ $item->name }}</a>
                            @if($item->sku)<div class="text-xs text-gray-400">{{ $item->sku }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono {{ $item->current_stock <= 0 ? 'text-red-600 font-bold' : 'text-gray-700' }}">
                            {{ number_format($item->current_stock, 2) }} {{ $item->unit }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-gray-700">{{ number_format($item->units_sold, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-gray-500">{{ number_format($item->avg_daily_usage, 3) }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">
                            {{ $item->days_since_last_sale !== null ? $item->days_since_last_sale . ' days' : 'Never sold' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $velColor }}">
                                {{ $item->velocity_label }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No inventory items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4 text-right">Generated {{ now()->format('d M Y H:i') }}</p>
</div>
@endsection
