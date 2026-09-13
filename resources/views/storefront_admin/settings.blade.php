@extends('layouts.app')

@section('page-title', 'Storefront Settings')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    @if($storefront->is_enabled)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-base font-semibold mb-1">Your Storefront Link</h2>
        <p class="text-sm text-gray-500 mb-3">Share this with your customers.</p>
        <div class="flex items-center gap-2" x-data="{ copied: false }">
            <input type="text" readonly value="{{ route('storefront.index', $tenant->slug) }}" onclick="this.select()"
                   x-ref="link" class="flex-1 rounded-lg border-gray-200 bg-gray-50 shadow-sm text-sm font-mono text-gray-700">
            <button type="button" @click="navigator.clipboard.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 1500)"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors whitespace-nowrap">
                <span x-show="!copied">Copy link</span>
                <span x-show="copied" x-cloak>Copied!</span>
            </button>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div>
            <h2 class="text-base font-semibold">Storefront Settings</h2>
            <p class="text-sm text-gray-500 mt-0.5">Turn your storefront on or off, and set how customers can order.</p>
        </div>

        <form method="POST" action="{{ route('storefront.settings.update') }}" class="space-y-5">
            @csrf

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="hidden" name="is_enabled" value="0">
                <input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $storefront->is_enabled) ? 'checked' : '' }}
                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span>
                    <span class="text-sm font-medium text-gray-700">Storefront enabled</span>
                    <span class="block text-xs text-gray-400 mt-0.5">When off, your storefront link returns a 404 to visitors.</span>
                </span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="hidden" name="vat_applicable" value="0">
                <input type="checkbox" name="vat_applicable" value="1" {{ old('vat_applicable', $storefront->vat_applicable ?? true) ? 'checked' : '' }}
                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span>
                    <span class="text-sm font-medium text-gray-700">Charge VAT on this storefront</span>
                    <span class="block text-xs text-gray-400 mt-0.5">When off, no VAT is charged on anything sold here, regardless of individual product/service settings.</span>
                </span>
            </label>

            <div>
                <label class="block text-sm font-medium text-gray-700">WhatsApp Number</label>
                <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $storefront->whatsapp_number) }}"
                       placeholder="e.g. +234 801 234 5678"
                       class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                <p class="mt-0.5 text-xs text-gray-400">Adds an "Order via WhatsApp" option at checkout. Leave blank to hide it.</p>
                @error('whatsapp_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Shop Description</label>
                <textarea name="description" rows="4" placeholder="Tell customers about your business..."
                          class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">{{ old('description', $storefront->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="mb-4">
            <h2 class="text-base font-semibold">Shop Banner</h2>
            <p class="text-sm text-gray-500 mt-0.5">Appears at the top of your storefront page. Recommended: wide image, max 2 MB.</p>
        </div>
        <div class="flex items-start gap-6">
            <div class="flex-shrink-0">
                @if($storefront->banner_image)
                    <img src="{{ Storage::url($storefront->banner_image) }}" alt="Banner" class="h-20 w-40 object-cover border border-gray-200 rounded-lg bg-gray-50">
                @else
                    <div class="h-20 w-40 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center text-gray-400 text-xs text-center bg-gray-50">No banner</div>
                @endif
            </div>
            <div class="flex-1 space-y-3">
                <form method="POST" action="{{ route('storefront.settings.banner.upload') }}" enctype="multipart/form-data" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <input type="file" name="banner" accept="image/*" required
                               class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                        @error('banner')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">Upload</button>
                </form>
                @if($storefront->banner_image)
                <form method="POST" action="{{ route('storefront.settings.banner.delete') }}">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Remove the banner image?')" class="text-sm text-red-600 hover:text-red-800 underline">Remove banner</button>
                </form>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
