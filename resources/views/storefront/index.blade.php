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

@if($categories->isNotEmpty())
<div class="flex flex-wrap gap-2 mb-6">
    <a href="{{ route('storefront.index', $tenant->slug) }}"
       class="px-3 py-1.5 rounded-full text-sm font-medium border {{ !$categoryId ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:border-gray-300' }}"
       @if(!$categoryId) style="background-color: {{ $tenant->accentColor() }};" @endif>
        All
    </a>
    @foreach($categories as $category)
    <a href="{{ route('storefront.index', ['tenant' => $tenant->slug, 'category' => $category->id]) }}"
       class="px-3 py-1.5 rounded-full text-sm font-medium border {{ $categoryId === $category->id ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:border-gray-300' }}"
       @if($categoryId === $category->id) style="background-color: {{ $tenant->accentColor() }};" @endif>
        {{ $category->name }}
    </a>
    @endforeach
</div>
@endif

@if($products->isEmpty() && $services->isEmpty())
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
                @if($storeVatApplicable && $product->vat_applicable)
                    <span class="text-xs font-normal text-gray-400">+ VAT</span>
                @endif
            </p>
        </div>
    </a>
    @endforeach
    @foreach($services as $service)
    <a href="{{ route('storefront.service', ['tenant' => $tenant->slug, 'storefrontService' => $service->id]) }}"
       class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
        <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden relative">
            <span class="absolute top-2 left-2 text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-gray-900/80 text-white">Service</span>
            @if($service->images->first())
                <img src="{{ Storage::url($service->images->first()->image_path) }}" alt="{{ $service->name }}" class="w-full h-full object-cover">
            @else
                <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
            @endif
        </div>
        <div class="p-3">
            <p class="text-sm font-medium text-gray-900 truncate">{{ $service->name }}</p>
            <p class="text-sm font-bold text-gray-900 mt-1">
                ₦{{ number_format((float) $service->price, 2) }}
                @if($storeVatApplicable && $service->vat_applicable)
                    <span class="text-xs font-normal text-gray-400">+ VAT</span>
                @endif
            </p>
        </div>
    </a>
    @endforeach
</div>
@endif

@endsection
