<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StorefrontCategory;
use App\Models\StorefrontProduct;
use App\Models\StorefrontProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StorefrontProductController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;

        $items = InventoryItem::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->with('storefrontProduct.images', 'storefrontProduct.category')
            ->when($request->filled('category'), fn($q) => $q->whereHas(
                'storefrontProduct',
                fn($sq) => $sq->where('storefront_category_id', $request->integer('category'))
            ))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $categories = StorefrontCategory::where('storefront_categories.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->orderBy('name')
            ->get();

        return view('storefront_admin.products.index', compact('items', 'categories'));
    }

    public function publish(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorizeItem($request, $inventoryItem);

        StorefrontProduct::withoutGlobalScope('tenant')->updateOrCreate(
            ['tenant_id' => $inventoryItem->tenant_id, 'inventory_item_id' => $inventoryItem->id],
            ['is_published' => true]
        );

        return back()->with('success', "{$inventoryItem->name} published to your storefront.");
    }

    public function unpublish(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorizeItem($request, $inventoryItem);

        $inventoryItem->storefrontProduct?->update(['is_published' => false]);

        return back()->with('success', "{$inventoryItem->name} removed from your storefront.");
    }

    public function updateDescription(Request $request, StorefrontProduct $storefrontProduct): RedirectResponse
    {
        $this->authorizeProduct($request, $storefrontProduct);

        $validated = $request->validate([
            'web_description'         => 'nullable|string|max:3000',
            'storefront_category_id'  => ['nullable', 'integer', Rule::exists('storefront_categories', 'id')->where('tenant_id', $storefrontProduct->tenant_id)],
            'vat_applicable'          => 'boolean',
        ]);

        $storefrontProduct->update([
            ...$validated,
            'vat_applicable' => $request->boolean('vat_applicable'),
        ]);

        return back()->with('success', 'Product updated.');
    }

    public function uploadImage(Request $request, StorefrontProduct $storefrontProduct): RedirectResponse
    {
        $this->authorizeProduct($request, $storefrontProduct);

        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $currentCount = $storefrontProduct->images()->count();

        if ($currentCount >= StorefrontProduct::MAX_IMAGES) {
            return back()->with('error', 'You\'ve reached the maximum of ' . StorefrontProduct::MAX_IMAGES . ' photos. Remove one to add more.');
        }

        $files    = $request->file('images');
        $slots    = StorefrontProduct::MAX_IMAGES - $currentCount;
        $accepted = array_slice($files, 0, $slots);
        $skipped  = count($files) - count($accepted);

        foreach ($accepted as $file) {
            $path = $file->store("storefront/{$storefrontProduct->tenant_id}/{$storefrontProduct->id}", 'public');

            StorefrontProductImage::withoutGlobalScope('tenant')->create([
                'tenant_id'              => $storefrontProduct->tenant_id,
                'storefront_product_id'  => $storefrontProduct->id,
                'image_path'             => $path,
                'sort_order'             => $currentCount++,
            ]);
        }

        $message = 'Added ' . count($accepted) . ' photo' . (count($accepted) === 1 ? '' : 's') . '.';
        if ($skipped > 0) {
            $message .= " {$skipped} " . ($skipped === 1 ? 'was' : 'were') . ' not added — you\'ve reached the ' . StorefrontProduct::MAX_IMAGES . '-photo limit.';
        }

        return back()->with('success', $message);
    }

    public function deleteImage(Request $request, StorefrontProductImage $image): RedirectResponse
    {
        abort_unless($image->tenant_id === $request->user()->tenant_id, 403);

        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }
        $image->delete();

        return back()->with('success', 'Image removed.');
    }

    private function authorizeItem(Request $request, InventoryItem $inventoryItem): void
    {
        abort_unless($inventoryItem->tenant_id === $request->user()->tenant_id, 403);
    }

    private function authorizeProduct(Request $request, StorefrontProduct $storefrontProduct): void
    {
        abort_unless($storefrontProduct->tenant_id === $request->user()->tenant_id, 403);
    }
}
