<?php

namespace App\Http\Controllers;

use App\Models\StorefrontCategory;
use App\Models\StorefrontService;
use App\Models\StorefrontServiceImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StorefrontServiceController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;

        $services = StorefrontService::where('storefront_services.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->with('images', 'category')
            ->orderBy('name')
            ->paginate(25);

        $categories = StorefrontCategory::where('storefront_categories.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->orderBy('name')
            ->get();

        return view('storefront_admin.services.index', compact('services', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        $validated = $this->validateService($request, $tenant->id);

        StorefrontService::withoutGlobalScope('tenant')->create([
            ...$validated,
            'tenant_id'      => $tenant->id,
            'vat_applicable' => $request->boolean('vat_applicable'),
        ]);

        return back()->with('success', 'Service created.');
    }

    public function update(Request $request, StorefrontService $storefrontService): RedirectResponse
    {
        $this->authorizeService($request, $storefrontService);

        $validated = $this->validateService($request, $storefrontService->tenant_id);

        $storefrontService->update([
            ...$validated,
            'vat_applicable' => $request->boolean('vat_applicable'),
        ]);

        return back()->with('success', 'Service updated.');
    }

    public function publish(Request $request, StorefrontService $storefrontService): RedirectResponse
    {
        $this->authorizeService($request, $storefrontService);

        $storefrontService->update(['is_published' => true]);

        return back()->with('success', "{$storefrontService->name} published to your storefront.");
    }

    public function unpublish(Request $request, StorefrontService $storefrontService): RedirectResponse
    {
        $this->authorizeService($request, $storefrontService);

        $storefrontService->update(['is_published' => false]);

        return back()->with('success', "{$storefrontService->name} removed from your storefront.");
    }

    public function destroy(Request $request, StorefrontService $storefrontService): RedirectResponse
    {
        $this->authorizeService($request, $storefrontService);

        $storefrontService->delete();

        return back()->with('success', 'Service deleted.');
    }

    public function uploadImage(Request $request, StorefrontService $storefrontService): RedirectResponse
    {
        $this->authorizeService($request, $storefrontService);

        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $currentCount = $storefrontService->images()->count();

        if ($currentCount >= StorefrontService::MAX_IMAGES) {
            return back()->with('error', 'You\'ve reached the maximum of ' . StorefrontService::MAX_IMAGES . ' photos. Remove one to add more.');
        }

        $files    = $request->file('images');
        $slots    = StorefrontService::MAX_IMAGES - $currentCount;
        $accepted = array_slice($files, 0, $slots);
        $skipped  = count($files) - count($accepted);

        foreach ($accepted as $file) {
            $path = $file->store("storefront/{$storefrontService->tenant_id}/services/{$storefrontService->id}", 'public');

            StorefrontServiceImage::withoutGlobalScope('tenant')->create([
                'tenant_id'              => $storefrontService->tenant_id,
                'storefront_service_id'  => $storefrontService->id,
                'image_path'             => $path,
                'sort_order'             => $currentCount++,
            ]);
        }

        $message = 'Added ' . count($accepted) . ' photo' . (count($accepted) === 1 ? '' : 's') . '.';
        if ($skipped > 0) {
            $message .= " {$skipped} " . ($skipped === 1 ? 'was' : 'were') . ' not added — you\'ve reached the ' . StorefrontService::MAX_IMAGES . '-photo limit.';
        }

        return back()->with('success', $message);
    }

    public function deleteImage(Request $request, StorefrontServiceImage $image): RedirectResponse
    {
        abort_unless($image->tenant_id === $request->user()->tenant_id, 403);

        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }
        $image->delete();

        return back()->with('success', 'Image removed.');
    }

    private function validateService(Request $request, int $tenantId): array
    {
        return $request->validate([
            'name'                    => ['required', 'string', 'max:150'],
            'description'             => ['nullable', 'string', 'max:3000'],
            'price'                   => ['required', 'numeric', 'min:0'],
            'storefront_category_id'  => ['nullable', 'integer', Rule::exists('storefront_categories', 'id')->where('tenant_id', $tenantId)],
            'vat_applicable'          => ['boolean'],
        ]);
    }

    private function authorizeService(Request $request, StorefrontService $storefrontService): void
    {
        abort_unless($storefrontService->tenant_id === $request->user()->tenant_id, 403);
    }
}
