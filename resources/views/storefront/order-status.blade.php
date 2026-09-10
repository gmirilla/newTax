@extends('storefront.layout')

@section('title', 'Order ' . $order->order_number . ' — ' . $tenant->name)

@section('content')

@php
    $statusLabel = ['pending' => 'Pending Confirmation', 'accepted' => 'Confirmed', 'rejected' => 'Declined', 'cancelled' => 'Cancelled'][$order->status] ?? ucfirst($order->status);
    $statusColor = ['pending' => 'bg-amber-50 text-amber-700 border-amber-200', 'accepted' => 'bg-green-50 text-green-700 border-green-200', 'rejected' => 'bg-red-50 text-red-700 border-red-200', 'cancelled' => 'bg-gray-50 text-gray-500 border-gray-200'][$order->status] ?? 'bg-gray-50 text-gray-500 border-gray-200';
@endphp

<div class="max-w-lg mx-auto">
    <div class="text-center mb-6">
        <h1 class="text-xl font-bold text-gray-900">Order {{ $order->order_number }}</h1>
        <span class="inline-block mt-2 text-xs font-semibold px-3 py-1 rounded-full border {{ $statusColor }}">{{ $statusLabel }}</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="divide-y divide-gray-100">
            @foreach($order->items as $item)
            <div class="flex justify-between py-2.5 text-sm">
                <span class="text-gray-600">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} × {{ $item->description }}</span>
                <span class="font-medium text-gray-900">₦{{ number_format((float) $item->total, 2) }}</span>
            </div>
            @endforeach
        </div>
        <div class="space-y-1.5 pt-3 mt-3 border-t border-gray-100 text-sm">
            <div class="flex justify-between text-gray-500"><span>Subtotal</span><span>₦{{ number_format((float) $order->subtotal, 2) }}</span></div>
            <div class="flex justify-between text-gray-500"><span>VAT (7.5%)</span><span>₦{{ number_format((float) $order->vat_amount, 2) }}</span></div>
            <div class="flex justify-between font-bold text-gray-900 pt-1.5 mt-1.5 border-t border-gray-100"><span>Total</span><span>₦{{ number_format((float) $order->total_amount, 2) }}</span></div>
        </div>
    </div>

    @if($order->status === 'rejected' && $order->rejection_reason)
    <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
        <strong>Reason:</strong> {{ $order->rejection_reason }}
    </div>
    @endif

    <p class="text-center text-sm text-gray-400 mt-6">
        Questions about this order? Contact {{ $tenant->name }} directly.
    </p>
</div>

@endsection
