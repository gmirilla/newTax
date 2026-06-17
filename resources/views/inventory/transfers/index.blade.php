@extends('layouts.app')
@section('page-title', 'Stock Transfers')

@section('content')
<div class="max-w-5xl space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Stock Transfers</h1>
            <p class="text-sm text-gray-500 mt-0.5">Move stock between locations.</p>
        </div>
        <a href="{{ route('inventory.transfers.create') }}"
           class="btn-primary text-sm px-4 py-2 rounded-lg flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            New Transfer
        </a>
    </div>

    @include('inventory.partials._location-switcher', ['active' => $active])

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="flex items-center gap-3 flex-wrap">
        <input type="date" name="from" value="{{ request('from') }}"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
        <input type="date" name="to" value="{{ request('to') }}"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
        <button type="submit" class="text-sm px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Filter</button>
        @if(request('from') || request('to'))
            <a href="{{ route('inventory.transfers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        @if($transfers->isEmpty())
        <div class="p-10 text-center">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            <p class="text-sm text-gray-500">No transfers yet.</p>
            <a href="{{ route('inventory.transfers.create') }}" class="mt-2 inline-block text-sm text-green-600 hover:underline">Create the first transfer →</a>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-700">Date</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-700">Item</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-700">From</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-700">To</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-700">Qty</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-700 hidden md:table-cell">By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($transfers as $t)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $t->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-800">{{ $t->item->name }}</span>
                        @if($t->notes)<div class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $t->notes }}</div>@endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $t->location?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $t->transferPair?->location?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-medium text-gray-800">
                        {{ number_format($t->quantity, 2) }}
                        <span class="text-xs text-gray-400">{{ $t->item->unit }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 hidden md:table-cell">{{ $t->creator?->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($transfers->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $transfers->withQueryString()->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
