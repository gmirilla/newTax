@extends('storefront.layout')

@section('title', $product->item->name . ' — ' . $tenant->name)

@section('content')

<a href="{{ route('storefront.index', $tenant->slug) }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 mb-6">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
    Back to shop
</a>

<div class="grid md:grid-cols-2 gap-12">
    <div>
        @if($product->images->isNotEmpty())
        <div x-data="{ active: 0 }">
            <div class="aspect-square bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
                @foreach($product->images as $i => $image)
                <img x-show="active === {{ $i }}" x-transition.opacity src="{{ Storage::url($image->image_path) }}" alt="{{ $product->item->name }}" class="w-full h-full object-cover">
                @endforeach
            </div>
            @if($product->images->count() > 1)
            <div class="flex gap-2 mt-3">
                @foreach($product->images as $i => $image)
                <button @click="active = {{ $i }}" class="w-16 h-16 rounded-xl overflow-hidden ring-2 transition-all" :class="active === {{ $i }} ? 'ring-gray-900' : 'ring-transparent opacity-70 hover:opacity-100'">
                    <img src="{{ Storage::url($image->image_path) }}" class="w-full h-full object-cover">
                </button>
                @endforeach
            </div>
            @endif
        </div>
        @else
        <div class="aspect-square bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-center">
            <svg class="w-16 h-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375C2.754 3.75 2.25 4.254 2.25 4.875v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
        </div>
        @endif
    </div>

    <div>
        @php
            $unitPrice     = (float) $product->item->selling_price;
            $vatApplicable = ($tenant->storefront?->vat_applicable ?? true) && $product->vat_applicable;
            $unitVat       = $vatApplicable ? round($unitPrice * \App\Models\Invoice::VAT_RATE / 100, 2) : 0;
        @endphp
        <h1 class="font-display text-2xl sm:text-3xl font-semibold text-gray-900 tracking-tight">{{ $product->item->name }}</h1>
        <p class="text-2xl font-semibold text-gray-900 mt-3">
            ₦{{ number_format($unitPrice, 2) }}
            @if($vatApplicable)
                <span class="text-sm font-normal text-gray-400">+ VAT</span>
            @endif
        </p>
        @if($vatApplicable)
        <p class="text-xs text-gray-400 mt-1">₦{{ number_format($unitPrice + $unitVat, 2) }} incl. VAT (7.5%)</p>
        @endif

        @if($product->description())
        <p class="mt-5 text-sm text-gray-500 leading-relaxed">{{ $product->description() }}</p>
        @endif

        <form method="POST" action="{{ route('storefront.cart.add', $tenant->slug) }}" class="mt-7 flex items-end gap-3">
            @csrf
            <input type="hidden" name="type" value="product">
            <input type="hidden" name="id" value="{{ $product->id }}">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Quantity</label>
                <input type="number" name="quantity" value="1" min="0.01" step="any"
                       class="w-20 rounded-lg border-gray-200 shadow-sm text-sm focus:ring-gray-900 focus:border-gray-900">
            </div>
            <button type="submit" style="background-color: {{ $tenant->accentColor() }}; color: {{ $tenant->accentTextColor() }};"
                    class="px-7 py-2.5 rounded-lg text-sm font-semibold shadow-sm hover:shadow-md hover:-translate-y-px transition-all">
                Add to Cart
            </button>
        </form>
    </div>
</div>

@endsection
