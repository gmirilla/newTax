@extends('layouts.app')

@section('title', 'Stock Movements')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Stock Movements</h1>
            <p class="text-sm text-gray-500 mt-1">Full movement ledger with filters</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.reports.movements.pdf', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-red-300 text-red-700 bg-white hover:bg-red-50">
                PDF
            </a>
            <a href="{{ route('inventory.reports.movements.excel', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-green-300 text-green-700 bg-white hover:bg-green-50">
                Excel
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white shadow rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
            <input type="date" name="from" value="{{ $filters['from'] }}"
                   class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
            <input type="date" name="to" value="{{ $filters['to'] }}"
                   class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Item</label>
            <select name="item_id" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
                <option value="">All Items</option>
                @foreach($items as $item)
                <option value="{{ $item->id }}" @selected($filters['item_id'] == $item->id)>
                    {{ $item->name }}{{ $item->sku ? " ({$item->sku})" : '' }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
            <select name="type" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
                <option value="">All Types</option>
                @foreach(['restock','sale','adjustment_in','adjustment_out','opening','production_in','production_out'] as $t)
                <option value="{{ $t }}" @selected($filters['type'] === $t)>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-1.5 bg-naija-green text-white text-sm rounded hover:bg-green-700">Filter</button>
        <a href="{{ route('inventory.reports.movements') }}" class="px-4 py-1.5 text-sm text-gray-600 hover:underline">Clear</a>
    </form>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 text-sm text-gray-500">
            {{ $movements->count() }} movement(s) &bull; {{ $filters['from'] }} → {{ $filters['to'] }}
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-naija-green text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Date & Time</th>
                        <th class="px-4 py-3 text-left font-semibold">Item</th>
                        <th class="px-4 py-3 text-center font-semibold">Type</th>
                        <th class="px-4 py-3 text-right font-semibold">Qty In</th>
                        <th class="px-4 py-3 text-right font-semibold">Qty Out</th>
                        <th class="px-4 py-3 text-right font-semibold">Balance</th>
                        <th class="px-4 py-3 text-left font-semibold">Notes / Reference</th>
                        <th class="px-4 py-3 text-left font-semibold">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($movements as $m)
                    @php
                        $isIn = in_array($m->type, ['restock', 'adjustment_in', 'opening', 'production_in']);
                        $typeBadge = match($m->type) {
                            'restock'        => 'bg-green-100 text-green-800',
                            'sale'           => 'bg-blue-100 text-blue-800',
                            'adjustment_in'  => 'bg-teal-100 text-teal-800',
                            'adjustment_out' => 'bg-orange-100 text-orange-800',
                            'opening'        => 'bg-purple-100 text-purple-800',
                            'production_in'  => 'bg-green-100 text-green-800',
                            'production_out' => 'bg-red-100 text-red-700',
                            default          => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap text-xs">{{ $m->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $m->item?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $typeBadge }}">
                                {{ ucfirst(str_replace('_', ' ', $m->type)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-green-700">
                            {{ $isIn ? number_format((float)$m->quantity, 3) : '' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-red-600">
                            {{ !$isIn ? number_format((float)$m->quantity, 3) : '' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-semibold">{{ number_format((float)$m->running_balance, 3) }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $m->notes ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $m->creator?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">No movements found for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4 text-right">Generated {{ now()->format('d M Y H:i') }}</p>
</div>
@endsection
