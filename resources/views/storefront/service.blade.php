@extends('storefront.layout')

@section('title', $service->name . ' — ' . $tenant->name)

@section('content')

<a href="{{ route('storefront.index', $tenant->slug) }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 mb-6">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
    Back to shop
</a>

<div class="grid md:grid-cols-2 gap-10">
    <div>
        @if($service->images->isNotEmpty())
        <div x-data="{ active: 0 }">
            <div class="aspect-square bg-gray-50 rounded-xl overflow-hidden border border-gray-100">
                @foreach($service->images as $i => $image)
                <img x-show="active === {{ $i }}" src="{{ Storage::url($image->image_path) }}" alt="{{ $service->name }}" class="w-full h-full object-cover">
                @endforeach
            </div>
            @if($service->images->count() > 1)
            <div class="flex gap-2 mt-3">
                @foreach($service->images as $i => $image)
                <button @click="active = {{ $i }}" class="w-16 h-16 rounded-lg overflow-hidden border-2" :class="active === {{ $i }} ? 'border-gray-900' : 'border-transparent'">
                    <img src="{{ Storage::url($image->image_path) }}" class="w-full h-full object-cover">
                </button>
                @endforeach
            </div>
            @endif
        </div>
        @else
        <div class="aspect-square bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center">
            <svg class="w-16 h-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
        </div>
        @endif
    </div>

    <div>
        @php
            $unitPrice = (float) $service->price;
            $unitVat   = round($unitPrice * \App\Models\Invoice::VAT_RATE / 100, 2);
        @endphp
        <span class="inline-block text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-gray-900/80 text-white mb-2">Service</span>
        <h1 class="text-xl font-bold text-gray-900">{{ $service->name }}</h1>
        <p class="text-2xl font-bold text-gray-900 mt-2">
            ₦{{ number_format($unitPrice, 2) }}
            <span class="text-sm font-normal text-gray-400">+ VAT</span>
        </p>
        <p class="text-xs text-gray-500 mt-0.5">₦{{ number_format($unitPrice + $unitVat, 2) }} incl. VAT (7.5%)</p>

        @if($service->description)
        <p class="mt-4 text-sm text-gray-600 leading-relaxed">{{ $service->description }}</p>
        @endif

        <form method="POST" action="{{ route('storefront.cart.add', $tenant->slug) }}" class="mt-6 flex items-end gap-3">
            @csrf
            <input type="hidden" name="type" value="service">
            <input type="hidden" name="id" value="{{ $service->id }}">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Quantity</label>
                <input type="number" name="quantity" value="1" min="0.01" step="any"
                       class="w-20 rounded-md border-gray-300 shadow-sm text-sm focus:ring-gray-900 focus:border-gray-900">
            </div>
            <button type="submit" style="background-color: {{ $tenant->accentColor() }}; color: {{ $tenant->accentTextColor() }};"
                    class="px-6 py-2.5 rounded-lg text-sm font-semibold">
                Add to Cart
            </button>
        </form>
    </div>
</div>

@endsection
