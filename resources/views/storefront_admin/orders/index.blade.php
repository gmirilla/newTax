@extends('layouts.app')

@section('page-title', 'Storefront Orders')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">

    <div class="flex items-center gap-2">
        @foreach(['' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected'] as $value => $label)
        <a href="{{ route('storefront.orders.index', $value ? ['status' => $value] : []) }}"
           class="px-3 py-1.5 text-sm rounded-lg transition-colors {{ request('status', '') === $value ? 'bg-green-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            {{ $label }}
            @if($value === 'pending' && $pendingCount > 0)
                <span class="ml-1 inline-flex items-center justify-center h-4 w-4 rounded-full bg-red-500 text-white text-[10px] font-bold">{{ $pendingCount }}</span>
            @endif
        </a>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Channel</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                @php $statusColor = ['pending' => 'bg-amber-100 text-amber-800', 'accepted' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-800', 'cancelled' => 'bg-gray-100 text-gray-600'][$order->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                <tr class="hover:bg-gray-50/60 transition-colors cursor-pointer" onclick="window.location='{{ route('storefront.orders.show', $order) }}'">
                    <td class="px-4 py-3 text-sm font-medium text-green-700">{{ $order->order_number }}</td>
                    <td class="px-4 py-3 text-sm text-gray-800">{{ $order->customer_name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 capitalize">{{ $order->channel }}</td>
                    <td class="px-4 py-3 text-sm text-gray-800">₦{{ number_format((float) $order->total_amount, 2) }}</td>
                    <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }} capitalize">{{ $order->status }}</span></td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ $order->created_at->format('d M Y, g:i A') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">No storefront orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</div>
@endsection
