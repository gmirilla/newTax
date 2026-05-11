@extends('layouts.app')

@section('title', 'Sales by Item')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sales by Item</h1>
            <p class="text-sm text-gray-500 mt-1">Revenue, COGS and margin per product (confirmed orders only)</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.reports.sales-by-item.pdf', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-red-300 text-red-700 bg-white hover:bg-red-50">
                PDF
            </a>
            <a href="{{ route('inventory.reports.sales-by-item.excel', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-green-300 text-green-700 bg-white hover:bg-green-50">
                Excel
            </a>
        </div>
    </div>

    {{-- Date filter --}}
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
        <button type="submit" class="px-4 py-1.5 bg-naija-green text-white text-sm rounded hover:bg-green-700">Apply</button>
        <a href="{{ route('inventory.reports.sales-by-item') }}" class="px-4 py-1.5 text-sm text-gray-600 hover:underline">Reset</a>
    </form>

    {{-- Summary Cards --}}
    @if($rows->count())
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Revenue</p>
            <p class="text-xl font-bold text-naija-green mt-1">₦{{ number_format($totals['revenue'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">COGS</p>
            <p class="text-xl font-bold text-red-600 mt-1">₦{{ number_format($totals['cogs'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Gross Profit</p>
            <p class="text-xl font-bold {{ $totals['gross_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                ₦{{ number_format($totals['gross_profit'], 2) }}
            </p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Avg Margin</p>
            <p class="text-xl font-bold text-blue-600 mt-1">{{ number_format($totals['margin_pct'], 1) }}%</p>
        </div>
    </div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-naija-green text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Item</th>
                        <th class="px-4 py-3 text-right font-semibold">Units Sold</th>
                        <th class="px-4 py-3 text-right font-semibold">Revenue (₦)</th>
                        <th class="px-4 py-3 text-right font-semibold">COGS (₦)</th>
                        <th class="px-4 py-3 text-right font-semibold">Gross Profit (₦)</th>
                        <th class="px-4 py-3 text-right font-semibold">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $row->item?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float)$row->units_sold, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float)$row->revenue, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-red-600">{{ number_format((float)$row->cogs, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono {{ $row->gross_profit >= 0 ? 'text-green-700' : 'text-red-600' }} font-semibold">
                            {{ number_format($row->gross_profit, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            <span class="inline-flex px-2 py-0.5 text-xs rounded-full {{ $row->margin_pct >= 20 ? 'bg-green-100 text-green-800' : ($row->margin_pct >= 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ number_format($row->margin_pct, 1) }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">No confirmed sales in this period.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($rows->count())
                <tfoot class="bg-green-50 font-semibold">
                    <tr>
                        <td class="px-4 py-3 border-t border-gray-300">TOTALS</td>
                        <td class="px-4 py-3 text-right font-mono border-t border-gray-300">{{ number_format($totals['units_sold'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono border-t border-gray-300">₦{{ number_format($totals['revenue'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-red-600 border-t border-gray-300">₦{{ number_format($totals['cogs'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono border-t border-gray-300 {{ $totals['gross_profit'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                            ₦{{ number_format($totals['gross_profit'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono border-t border-gray-300">{{ number_format($totals['margin_pct'], 1) }}%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4 text-right">Generated {{ now()->format('d M Y H:i') }}</p>
</div>
@endsection
