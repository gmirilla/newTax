@extends('storefront.layout')

@section('content')

@if($tenant->storefront?->banner_image)
<div class="rounded-xl overflow-hidden mb-8">
    <img src="{{ Storage::url($tenant->storefront->banner_image) }}" alt="{{ $tenant->name }}" class="w-full h-48 sm:h-64 object-cover">
</div>
@endif

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">{{ $tenant->name }}</h1>
    @if($tenant->storefront?->description)
    <p class="mt-2 text-gray-600 max-w-2xl">{{ $tenant->storefront->description }}</p>
    @endif
</div>

@if($products->isEmpty())
<div class="text-center py-16 text-gray-400">
    <p class="text-sm">No products available right now — check back soon.</p>
</div>
@else
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
    @foreach($products as $product)
    <a href="{{ route('storefront.product', ['tenant' => $tenant->slug, 'storefrontProduct' => $product->id]) }}"
       class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
        <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
            @if($product->images->first())
                <img src="{{ Storage::url($product->images->first()->image_path) }}" alt="{{ $product->item->name }}" class="w-full h-full object-cover">
            @else
                <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375C2.754 3.75 2.25 4.254 2.25 4.875v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            @endif
        </div>
        <div class="p-3">
            <p class="text-sm font-medium text-gray-900 truncate">{{ $product->item->name }}</p>
            <p class="text-sm font-bold text-gray-900 mt-1">
                ₦{{ number_format((float) $product->item->selling_price, 2) }}
                <span class="text-xs font-normal text-gray-400">+ VAT</span>
            </p>
        </div>
    </a>
    @endforeach
</div>
@endif

@endsection
