@extends('layouts.app')

@section('page-title', 'Order ' . $order->order_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    @php $statusColor = ['pending' => 'bg-amber-100 text-amber-800', 'accepted' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-800', 'cancelled' => 'bg-gray-100 text-gray-600'][$order->status] ?? 'bg-gray-100 text-gray-600'; @endphp

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">{{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500">Submitted {{ $order->created_at->format('d M Y, g:i A') }} via {{ ucfirst($order->channel) }}</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColor }} capitalize">{{ $order->status }}</span>
    </div>

    @if($order->canBeActioned())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-3">
        <form method="POST" action="{{ route('storefront.orders.accept', $order) }}">
            @csrf
            <button type="submit" class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                Accept Order
            </button>
        </form>
        <button type="button" onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                class="px-5 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors">
            Decline
        </button>
    </div>
    <form id="reject-form" method="POST" action="{{ route('storefront.orders.reject', $order) }}" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
        @csrf
        <label class="block text-sm font-medium text-gray-700">Reason (optional, shown to the customer)</label>
        <textarea name="rejection_reason" rows="2" class="w-full rounded-lg border-gray-200 text-sm focus:ring-red-500 focus:border-red-500"></textarea>
        <button type="submit" class="px-5 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">Confirm Decline</button>
    </form>
    @elseif($order->salesOrder)
    <div class="bg-green-50 border border-green-200 rounded-2xl p-4 text-sm text-green-800">
        Accepted — converted to draft sales order
        <a href="{{ route('inventory.sales.show', $order->salesOrder) }}" class="font-semibold underline">{{ $order->salesOrder->order_number }}</a>.
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Items</h2>
        <div class="divide-y divide-gray-100">
            @foreach($order->items as $item)
            <div class="flex justify-between py-2.5 text-sm">
                <span class="text-gray-600">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} × {{ $item->description }}</span>
                <span class="font-medium text-gray-900">₦{{ number_format((float) $item->total, 2) }}</span>
            </div>
            @endforeach
        </div>
        <div class="space-y-1 pt-3 mt-3 border-t border-gray-100 text-sm">
            <div class="flex justify-between text-gray-500"><span>Subtotal</span><span>₦{{ number_format((float) $order->subtotal, 2) }}</span></div>
            @if((float) $order->vat_amount > 0)
            <div class="flex justify-between text-gray-500"><span>VAT</span><span>₦{{ number_format((float) $order->vat_amount, 2) }}</span></div>
            @endif
            <div class="flex justify-between font-bold text-gray-900"><span>Total</span><span>₦{{ number_format((float) $order->total_amount, 2) }}</span></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-sm space-y-1.5">
        <h2 class="text-sm font-semibold text-gray-700 mb-2">Customer</h2>
        <p class="text-gray-600"><strong class="text-gray-800">{{ $order->customer_name }}</strong></p>
        <p class="text-gray-600">{{ $order->customer_phone }} · {{ $order->customer_email }}</p>
        @if($order->delivery_address)<p class="text-gray-600">{{ $order->delivery_address }}</p>@endif
        @if($order->notes)<p class="text-gray-500 mt-2 italic">"{{ $order->notes }}"</p>@endif
    </div>

</div>
@endsection
