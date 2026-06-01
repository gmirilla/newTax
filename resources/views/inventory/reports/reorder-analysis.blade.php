@extends('layouts.app')
@section('title', 'Reorder Analysis')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reorder Analysis</h1>
            <p class="text-sm text-gray-500 mt-1">Days of stock remaining and suggested reorder quantities based on sales velocity</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.reports.reorder-analysis.pdf', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-red-300 text-red-700 bg-white hover:bg-red-50">PDF</a>
            <a href="{{ route('inventory.reports.reorder-analysis.excel', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-green-300 text-green-700 bg-white hover:bg-green-50">Excel</a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white shadow rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Usage period (for avg daily calculation)</label>
            <select name="days" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
                @foreach([30 => 'Last 30 days', 60 => 'Last 60 days', 90 => 'Last 90 days', 180 => 'Last 180 days'] as $val => $label)
                <option value="{{ $val }}" {{ $filters['days'] == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-1.5 bg-naija-green text-white text-sm rounded hover:bg-green-700">Apply</button>
        <a href="{{ route('inventory.reports.reorder-analysis') }}" class="px-4 py-1.5 text-sm text-gray-600 hover:underline">Reset</a>
    </form>

    {{-- Summary cards --}}
    @php
        $outOfStock  = $items->where('reorder_status', 'Out of Stock')->count();
        $reorderNow  = $items->where('reorder_status', 'Reorder Now')->count();
        $reorderSoon = $items->where('reorder_status', 'Reorder Soon')->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg shadow p-4">
            <p class="text-xs text-red-600 uppercase font-medium">Out of Stock</p>
            <p class="text-xl font-bold text-red-700 mt-1">{{ $outOfStock }}</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg shadow p-4">
            <p class="text-xs text-amber-600 uppercase font-medium">Reorder Now (&le;7 days)</p>
            <p class="text-xl font-bold text-amber-700 mt-1">{{ $reorderNow }}</p>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg shadow p-4">
            <p class="text-xs text-yellow-600 uppercase font-medium">Reorder Soon (&le;30 days)</p>
            <p class="text-xl font-bold text-yellow-700 mt-1">{{ $reorderSoon }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg shadow p-4">
            <p class="text-xs text-green-600 uppercase font-medium">Sufficient / No Movement</p>
            <p class="text-xl font-bold text-green-700 mt-1">{{ $items->count() - $outOfStock - $reorderNow - $reorderSoon }}</p>
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
                        <th class="px-4 py-3 text-right font-semibold">Restock Level</th>
                        <th class="px-4 py-3 text-right font-semibold">Avg Daily Usage</th>
                        <th class="px-4 py-3 text-right font-semibold">Days of Stock</th>
                        <th class="px-4 py-3 text-right font-semibold">Suggested Reorder Qty</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $item)
                    @php
                        $statusColor = match($item->reorder_status) {
                            'Out of Stock'  => ['row' => 'bg-red-50',    'badge' => 'bg-red-100 text-red-800'],
                            'Reorder Now'   => ['row' => 'bg-amber-50',  'badge' => 'bg-amber-100 text-amber-800'],
                            'Reorder Soon'  => ['row' => 'bg-yellow-50', 'badge' => 'bg-yellow-100 text-yellow-800'],
                            'No Movement'   => ['row' => '',             'badge' => 'bg-gray-100 text-gray-600'],
                            default         => ['row' => '',             'badge' => 'bg-green-100 text-green-800'],
                        };
                    @endphp
                    <tr class="{{ $statusColor['row'] }} hover:opacity-90">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <a href="{{ route('inventory.items.show', $item) }}" class="hover:underline text-naija-green">{{ $item->name }}</a>
                            @if($item->sku)<div class="text-xs text-gray-400">{{ $item->sku }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono {{ $item->current_stock <= 0 ? 'text-red-600 font-bold' : 'text-gray-700' }}">
                            {{ number_format($item->current_stock, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-gray-500">{{ number_format($item->restock_level, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-gray-600">{{ number_format($item->avg_daily_usage, 3) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold">
                            @if($item->days_of_stock !== null)
                                <span class="{{ $item->days_of_stock <= 7 ? 'text-red-600' : ($item->days_of_stock <= 30 ? 'text-amber-600' : 'text-green-700') }}">
                                    {{ number_format($item->days_of_stock) }} days
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-gray-700">
                            {{ $item->suggested_reorder_qty > 0 ? number_format($item->suggested_reorder_qty, 2) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColor['badge'] }}">
                                {{ $item->reorder_status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No inventory items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">
        Suggested reorder quantity = 30 days of average demand. Days of stock = current stock ÷ avg daily usage.
    </p>
    <p class="text-xs text-gray-400 mt-1 text-right">Generated {{ now()->format('d M Y H:i') }}</p>
</div>
@endsection
