@extends('layouts.app')
@section('page-title', 'Production Orders')

@section('content')
<div class="max-w-5xl space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Production Orders</h1>
            <p class="text-sm text-gray-500 mt-0.5">Track and manage the conversion of raw materials into finished goods.</p>
        </div>
        @can('create', App\Models\ProductionOrder::class)
        <a href="{{ route('manufacturing.production.create') }}"
           class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + New Production Order
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-gray-700">{{ $stats->drafts ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Draft</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats->in_production ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">In Production</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats->completed ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Completed</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('manufacturing.production.index') }}" class="flex gap-2 items-center">
        <select name="status"
                class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-green-500 focus:border-green-500"
                onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="draft"         {{ request('status') === 'draft'         ? 'selected' : '' }}>Draft</option>
            <option value="in_production" {{ request('status') === 'in_production' ? 'selected' : '' }}>In Production</option>
            <option value="completed"     {{ request('status') === 'completed'     ? 'selected' : '' }}>Completed</option>
            <option value="cancelled"     {{ request('status') === 'cancelled'     ? 'selected' : '' }}>Cancelled</option>
        </select>
    </form>

    {{-- Orders list --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Finished Item</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Planned Qty</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Produced Qty</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900">
                        {{ $order->order_number }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                        {{ $order->finishedItem->name }}
                        @if($order->finishedItem->sku)
                            <span class="text-xs text-gray-400">({{ $order->finishedItem->sku }})</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-700">
                        {{ number_format($order->quantity_planned, 3) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-700">
                        {{ $order->quantity_produced ? number_format($order->quantity_produced, 3) : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $badge = match($order->status) {
                                'draft'         => 'bg-gray-100 text-gray-600',
                                'in_production' => 'bg-yellow-100 text-yellow-700',
                                'completed'     => 'bg-green-100 text-green-700',
                                'cancelled'     => 'bg-red-100 text-red-600',
                                default         => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $badge }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('manufacturing.production.show', $order) }}"
                           class="text-sm text-green-600 hover:underline font-medium">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                        No production orders yet.
                        @can('create', App\Models\ProductionOrder::class)
                        <a href="{{ route('manufacturing.production.create') }}" class="ml-1 text-green-600 hover:underline">Create one →</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->withQueryString()->links() }}

</div>
@endsection
