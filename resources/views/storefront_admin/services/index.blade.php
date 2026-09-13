@extends('layouts.app')

@section('page-title', 'Storefront Services')

@section('content')
<div x-data="{ showForm: false }" class="max-w-5xl mx-auto space-y-4">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center justify-between gap-4">
        <p class="text-sm text-gray-500">Sell services — consultations, delivery, installation — alongside your physical products.</p>
        <button @click="showForm = !showForm"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors whitespace-nowrap">
            + New Service
        </button>
    </div>

    <div x-show="showForm" x-cloak class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Add Service</h2>
        <form method="POST" action="{{ route('storefront.services.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="150"
                           class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                           placeholder="e.g. Home Cleaning">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Price (₦) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ old('price') }}" required min="0" step="0.01"
                           class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="storefront_category_id" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        <option value="">Uncategorized</option>
                        @foreach($categories ?? [] as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="2" maxlength="3000"
                          class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">{{ old('description') }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="hidden" name="vat_applicable" value="0">
                <input type="checkbox" name="vat_applicable" value="1" {{ old('vat_applicable', true) ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                Charge VAT on this service
            </label>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    Create Service
                </button>
                <button type="button" @click="showForm = false"
                        class="px-4 py-2 border border-gray-200 text-sm rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">VAT</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($services as $service)
                <tr class="hover:bg-gray-50/60 transition-colors">
                    <td class="px-4 py-3 text-sm text-gray-800">{{ $service->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">₦{{ number_format((float) $service->price, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ $service->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if($service->vat_applicable)
                            <span class="text-gray-500">7.5%</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Exempt</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($service->is_published)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Published</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Not published</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                        @if($service->is_published)
                            <a href="{{ route('storefront.service', ['tenant' => auth()->user()->tenant->slug, 'storefrontService' => $service->id]) }}" target="_blank"
                               class="text-sm text-gray-500 hover:text-gray-700">View</a>
                            <form method="POST" action="{{ route('storefront.services.unpublish', $service) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">Unpublish</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('storefront.services.publish', $service) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-green-600 hover:text-green-800 font-medium">Publish</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('storefront.services.destroy', $service) }}" class="inline"
                              onsubmit="return confirm('Delete {{ addslashes($service->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-gray-400 hover:text-red-600">Delete</button>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="px-4 pb-4 bg-gray-50">
                        <div class="grid sm:grid-cols-2 gap-4 pt-3">
                            <form method="POST" action="{{ route('storefront.services.update', $service) }}" class="space-y-2">
                                @csrf @method('PUT')
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                                    <input type="text" name="name" value="{{ $service->name }}" required maxlength="150"
                                           class="w-full rounded-lg border-gray-200 text-sm focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Price (₦)</label>
                                        <input type="number" name="price" value="{{ $service->price }}" required min="0" step="0.01"
                                               class="w-full rounded-lg border-gray-200 text-sm focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                                        <select name="storefront_category_id" class="w-full rounded-lg border-gray-200 text-sm focus:ring-green-500 focus:border-green-500">
                                            <option value="">Uncategorized</option>
                                            @foreach($categories ?? [] as $category)
                                                <option value="{{ $category->id }}" @selected($service->storefront_category_id === $category->id)>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                    <textarea name="description" rows="2" class="w-full rounded-lg border-gray-200 text-sm focus:ring-green-500 focus:border-green-500">{{ $service->description }}</textarea>
                                </div>
                                <label class="flex items-center gap-2 text-xs text-gray-600">
                                    <input type="hidden" name="vat_applicable" value="0">
                                    <input type="checkbox" name="vat_applicable" value="1" {{ old('vat_applicable', $service->vat_applicable) ? 'checked' : '' }}
                                           class="h-3.5 w-3.5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                                    Charge VAT on this service
                                </label>
                                <button type="submit" class="text-xs font-medium text-green-700 hover:text-green-900">Save changes</button>
                            </form>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Photos ({{ $service->images->count() }}/{{ \App\Models\StorefrontService::MAX_IMAGES }})</label>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    @foreach($service->images as $image)
                                    <div class="relative">
                                        <img src="{{ Storage::url($image->image_path) }}" class="h-14 w-14 object-cover rounded-lg border border-gray-200">
                                        <form method="POST" action="{{ route('storefront.services.images.delete', $image) }}" class="absolute -top-1.5 -right-1.5">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="h-4 w-4 bg-red-500 text-white rounded-full text-[10px] leading-4">×</button>
                                        </form>
                                    </div>
                                    @endforeach
                                </div>
                                @if($service->images->count() >= \App\Models\StorefrontService::MAX_IMAGES)
                                <p class="text-xs text-gray-400">Maximum of {{ \App\Models\StorefrontService::MAX_IMAGES }} photos reached — remove one to add more.</p>
                                @else
                                <form method="POST" action="{{ route('storefront.services.images.upload', $service) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                    @csrf
                                    <input type="file" name="images[]" accept="image/*" multiple required class="text-xs">
                                    <button type="submit" class="text-xs font-medium text-green-700 hover:text-green-900 whitespace-nowrap">Add photo(s)</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">No services yet — add your first one above.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $services->links() }}
</div>
@endsection
