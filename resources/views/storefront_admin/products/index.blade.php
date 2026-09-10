@extends('layouts.app')

@section('page-title', 'Storefront Products')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">

    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Publish inventory items to your storefront so customers can order them. Unpublishing keeps your images and description saved for later.</p>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($items as $item)
                @php $product = $item->storefrontProduct; @endphp
                <tr>
                    <td class="px-4 py-3 text-sm text-gray-800">{{ $item->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">₦{{ number_format((float) $item->selling_price, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ rtrim(rtrim(number_format((float) $item->current_stock, 2), '0'), '.') }}</td>
                    <td class="px-4 py-3">
                        @if($product?->is_published)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Published</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Not published</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                        @if($product?->is_published)
                            <a href="{{ route('storefront.product', ['tenant' => auth()->user()->tenant->slug, 'storefrontProduct' => $product->id]) }}" target="_blank"
                               class="text-sm text-gray-500 hover:text-gray-700">View</a>
                            <form method="POST" action="{{ route('storefront.products.unpublish', $item) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">Unpublish</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('storefront.products.publish', $item) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-green-600 hover:text-green-800 font-medium">Publish</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @if($product)
                <tr>
                    <td colspan="5" class="px-4 pb-4 bg-gray-50">
                        <div class="grid sm:grid-cols-2 gap-4 pt-3">
                            <form method="POST" action="{{ route('storefront.products.update', $product) }}">
                                @csrf @method('PATCH')
                                <label class="block text-xs font-medium text-gray-600 mb-1">Shop description (optional — falls back to the item description)</label>
                                <textarea name="web_description" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:ring-green-500 focus:border-green-500">{{ old('web_description', $product->web_description) }}</textarea>
                                <button type="submit" class="mt-2 text-xs font-medium text-green-700 hover:text-green-900">Save description</button>
                            </form>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Photos</label>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    @foreach($product->images as $image)
                                    <div class="relative">
                                        <img src="{{ Storage::url($image->image_path) }}" class="h-14 w-14 object-cover rounded border border-gray-200">
                                        <form method="POST" action="{{ route('storefront.products.images.delete', $image) }}" class="absolute -top-1.5 -right-1.5">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="h-4 w-4 bg-red-500 text-white rounded-full text-[10px] leading-4">×</button>
                                        </form>
                                    </div>
                                    @endforeach
                                </div>
                                <form method="POST" action="{{ route('storefront.products.images.upload', $product) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                    @csrf
                                    <input type="file" name="image" accept="image/*" required class="text-xs">
                                    <button type="submit" class="text-xs font-medium text-green-700 hover:text-green-900 whitespace-nowrap">Add photo</button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $items->links() }}
</div>
@endsection
