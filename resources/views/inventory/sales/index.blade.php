@extends('layouts.app')
@section('page-title', 'Sales Orders')

@section('content')
<div class="space-y-5">

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Confirmed Orders</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats->confirmed ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-yellow-400">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Draft Orders</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats->drafts ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₦{{ number_format($stats->total_revenue ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg">

        {{-- Header --}}
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Sales Orders</h2>
            <a href="{{ route('inventory.sales.create') }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                + New Sale
            </a>
        </div>

        {{-- Filters --}}
        <div class="px-6 py-3 bg-gray-50 border-b">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Order # or customer…"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 w-52">

                <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All Statuses</option>
                    <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Draft</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
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
                    <a href="{{ route('inventory.sales.index') }}"
                       class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total (₦)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($orders as $order)
                    @php
                        $statusColor = match($order->status) {
                            'confirmed' => 'green',
                            'draft'     => 'yellow',
                            'cancelled' => 'gray',
                            default     => 'gray',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <a href="{{ route('inventory.sales.show', $order) }}" class="text-green-700 hover:underline">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($order->sale_date)->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $order->customer?->name ?? $order->customer_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 capitalize">
                            {{ str_replace('_', ' ', $order->payment_method) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                            {{ number_format($order->total_amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold
                                bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $order->creator?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                            <a href="{{ route('inventory.sales.show', $order) }}"
                               class="text-green-600 hover:text-green-800 font-medium mr-2">View</a>
                            @if($order->status === 'draft')
                                <a href="{{ route('inventory.sales.edit', $order) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                            <p class="text-sm">No sales orders found.</p>
                            <a href="{{ route('inventory.sales.create') }}"
                               class="text-green-600 text-sm hover:underline mt-1 inline-block">
                                Record your first sale →
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
