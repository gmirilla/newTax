@extends('storefront.layout')

@section('title', 'Cart — ' . $tenant->name)

@section('content')

<h1 class="text-xl font-bold text-gray-900 mb-6">Your Cart</h1>

@if($lines->isEmpty())
<div class="text-center py-16 text-gray-400">
    <p class="text-sm mb-4">Your cart is empty.</p>
    <a href="{{ route('storefront.index', $tenant->slug) }}" class="text-sm font-medium" style="color: {{ $tenant->accentColor() }};">Continue shopping →</a>
</div>
@else
<div class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-100">
    @foreach($lines as $line)
    @php $image = ($line->type === 'product' ? $line->product : $line->service)->images->first(); @endphp
    <div class="flex items-center gap-4 p-4">
        <div class="w-14 h-14 rounded-lg bg-gray-50 flex-shrink-0 overflow-hidden flex items-center justify-center">
            @if($image)
                <img src="{{ Storage::url($image->image_path) }}" class="w-full h-full object-cover">
            @else
                <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375C2.754 3.75 2.25 4.254 2.25 4.875v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">
                {{ $line->name }}
                @if($line->type === 'service')<span class="ml-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Service</span>@endif
            </p>
            <p class="text-xs text-gray-500">{{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }} × ₦{{ number_format($line->unit_price, 2) }}</p>
        </div>
        <p class="text-sm font-semibold text-gray-900">₦{{ number_format($line->total, 2) }}</p>
        <form method="POST" action="{{ route('storefront.cart.remove', $tenant->slug) }}">
            @csrf
            <input type="hidden" name="type" value="{{ $line->type }}">
            <input type="hidden" name="id" value="{{ $line->type === 'product' ? $line->product->id : $line->service->id }}">
            <button type="submit" class="text-gray-300 hover:text-red-500" title="Remove">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </form>
    </div>
    @endforeach
</div>

@php
    $subtotal = $lines->sum('subtotal');
    $vat      = $lines->sum('vat_amount');
    $total    = $lines->sum('total');
@endphp

<div class="mt-6 bg-white rounded-xl border border-gray-100 shadow-sm p-5 max-w-sm ml-auto space-y-2 text-sm">
    <div class="flex justify-between text-gray-500"><span>Subtotal</span><span>₦{{ number_format($subtotal, 2) }}</span></div>
    <div class="flex justify-between text-gray-500"><span>VAT (7.5%)</span><span>₦{{ number_format($vat, 2) }}</span></div>
    <div class="flex justify-between font-bold text-gray-900 pt-2 border-t border-gray-100"><span>Total</span><span>₦{{ number_format($total, 2) }}</span></div>
</div>

<div class="mt-6 flex justify-end">
    <a href="{{ route('storefront.checkout', $tenant->slug) }}"
       style="background-color: {{ $tenant->accentColor() }}; color: {{ $tenant->accentTextColor() }};"
       class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-semibold">
        Proceed to Checkout
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
    </a>
</div>
@endif

@endsection
