@extends('layouts.app')
@section('page-title', 'Restock Requests')

@section('content')
<div class="space-y-5">

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-yellow-400">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Approval</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats->pending ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Approved / In Transit</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats->approved ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Received (All Time)</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats->received ?? 0) }}</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg">

        {{-- Header --}}
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Restock Requests</h2>
            @can('create', App\Models\RestockRequest::class)
            <a href="{{ route('inventory.restock.create') }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                + New Request
            </a>
            @endcan
        </div>

        {{-- Filters --}}
        <div class="px-6 py-3 bg-gray-50 border-b">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Item name or SKU…"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 w-48">

                <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All Statuses</option>
                    <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
                    <option value="approved"  {{ request('status') === 'approved'  ? 'selected' : '' }}>Approved</option>
                    <option value="received"  {{ request('status') === 'received'  ? 'selected' : '' }}>Received</option>
                    <option value="rejected"  {{ request('status') === 'rejected'  ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>

                <input type="date" name="from" value="{{ request('from') }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                <input type="date" name="to" value="{{ request('to') }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">

                <button type="submit"
                        class="px-4 py-1.5 bg-gray-700 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                    Filter
                </button>
                @if(request()->hasAny(['search','status','from','to']))
                    <a href="{{ route('inventory.restock.index') }}"
                       class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Request #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost (₦)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total (₦)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested By</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($requests as $rr)
                    @php
                        $statusColor = match($rr->status) {
                            'pending'   => 'yellow',
                            'approved'  => 'blue',
                            'received'  => 'green',
                            'rejected'  => 'red',
                            'cancelled' => 'gray',
                            default     => 'gray',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium">
                            <a href="{{ route('inventory.restock.show', $rr) }}"
                               class="text-green-700 hover:underline">
                                {{ $rr->request_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $rr->item->name ?? '—' }}</p>
                            @if($rr->item?->sku)
                                <p class="text-xs text-gray-400">{{ $rr->item->sku }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">
                            {{ number_format($rr->quantity_requested, 3) }}
                            <span class="text-xs text-gray-400">{{ $rr->item?->unit }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">
                            {{ number_format($rr->unit_cost, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                            {{ number_format($rr->totalCost(), 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $rr->supplier_name ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $rr->requester?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold
                                bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                                {{ ucfirst($rr->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('inventory.restock.show', $rr) }}"
                               class="text-green-600 hover:text-green-800 font-medium">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-400">
                            <p class="text-sm">No restock requests found.</p>
                            @can('create', App\Models\RestockRequest::class)
                                <a href="{{ route('inventory.restock.create') }}"
                                   class="text-green-600 text-sm hover:underline mt-1 inline-block">
                                    Create your first request →
                                </a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $requests->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
