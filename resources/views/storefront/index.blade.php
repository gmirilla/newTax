@extends('storefront.layout')

@section('content')

@if($tenant->storefront?->banner_image)
<div class="rounded-2xl overflow-hidden mb-10 shadow-sm">
    <img src="{{ Storage::url($tenant->storefront->banner_image) }}" alt="{{ $tenant->name }}" class="w-full h-48 sm:h-64 object-cover">
</div>
@endif

<div class="mb-9">
    <h1 class="font-display text-3xl sm:text-4xl font-semibold text-gray-900 tracking-tight">{{ $tenant->name }}</h1>
    @if($tenant->storefront?->description)
    <p class="mt-3 text-gray-500 max-w-2xl leading-relaxed">{{ $tenant->storefront->description }}</p>
    @endif

    @php $deliveryNotice = $tenant->storefront?->deliveryNoticeLines() ?? []; @endphp
    @if(!empty($deliveryNotice))
    <div class="mt-4 bg-amber-50 border border-amber-100 rounded-2xl px-4 py-3 max-w-2xl">
        @foreach($deliveryNotice as $line)
        <p class="text-sm text-amber-800 {{ !$loop->last ? 'mb-1' : '' }}">{{ $line }}</p>
        @endforeach
    </div>
    @endif
</div>

<form method="GET" action="{{ route('storefront.index', $tenant->slug) }}" class="mb-7">
    @if($categoryId)
    <input type="hidden" name="category" value="{{ $categoryId }}">
    @endif
    <div class="relative max-w-md">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search this shop..."
               class="w-full rounded-lg border-gray-200 shadow-sm text-sm pl-9 py-2.5 focus:ring-gray-900 focus:border-gray-900">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
    </div>
</form>

@if($categories->isNotEmpty())
<div class="flex flex-wrap gap-2 mb-8">
    <a href="{{ route('storefront.index', array_filter(['tenant' => $tenant->slug, 'q' => $search])) }}"
       class="px-3.5 py-1.5 rounded-full text-sm font-medium border transition-colors {{ !$categoryId ? 'text-white border-transparent' : 'text-gray-600 bg-gray-50 border-gray-100 hover:border-gray-200' }}"
       @if(!$categoryId) style="background-color: {{ $tenant->accentColor() }};" @endif>
        All
    </a>
    @foreach($categories as $category)
    <a href="{{ route('storefront.index', array_filter(['tenant' => $tenant->slug, 'category' => $category->id, 'q' => $search])) }}"
       class="px-3.5 py-1.5 rounded-full text-sm font-medium border transition-colors {{ $categoryId === $category->id ? 'text-white border-transparent' : 'text-gray-600 bg-gray-50 border-gray-100 hover:border-gray-200' }}"
       @if($categoryId === $category->id) style="background-color: {{ $tenant->accentColor() }};" @endif>
        {{ $category->name }}
    </a>
    @endforeach
</div>
@endif

@if($products->isEmpty() && $services->isEmpty())
<div class="text-center py-16 text-gray-400">
    @if($search)
    <p class="text-sm">No results for "{{ $search }}" — try a different search.</p>
    @else
    <p class="text-sm">No products available right now — check back soon.</p>
    @endif
</div>
@else
    @if($products->isNotEmpty())
    <div class="mb-12">
        <h2 class="font-display text-xl font-semibold text-gray-900 mb-5">Products</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($products as $product)
            <a href="{{ route('storefront.product', ['tenant' => $tenant->slug, 'storefrontProduct' => $product->id]) }}"
               class="group bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
                <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
                    @if($product->images->first())
                        <img src="{{ Storage::url($product->images->first()->image_path) }}" alt="{{ $product->item->name }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    @else
                        <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375C2.754 3.75 2.25 4.254 2.25 4.875v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                    @endif
                </div>
                <div class="p-4">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $product->item->name }}</p>
                    <p class="text-sm font-semibold text-gray-900 mt-1.5">
                        ₦{{ number_format((float) $product->item->selling_price, 2) }}
                        @if($storeVatApplicable && $product->vat_applicable)
                            <span class="text-xs font-normal text-gray-400">+ VAT</span>
                        @endif
                    </p>
                </div>
            </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $products->links() }}</div>
    </div>
    @endif

    @if($services->isNotEmpty())
    <div>
        <h2 class="font-display text-xl font-semibold text-gray-900 mb-5">Services</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($services as $service)
            <a href="{{ route('storefront.service', ['tenant' => $tenant->slug, 'storefrontService' => $service->id]) }}"
               class="group bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
                <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
                    @if($service->images->first())
                        <img src="{{ Storage::url($service->images->first()->image_path) }}" alt="{{ $service->name }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    @else
                        <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
                    @endif
                </div>
                <div class="p-4">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $service->name }}</p>
                    <p class="text-sm font-semibold text-gray-900 mt-1.5">
                        ₦{{ number_format((float) $service->price, 2) }}
                        @if($storeVatApplicable && $service->vat_applicable)
                            <span class="text-xs font-normal text-gray-400">+ VAT</span>
                        @endif
                    </p>
                </div>
            </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $services->links() }}</div>
    </div>
    @endif
@endif

@endsection
