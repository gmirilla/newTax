<?php

namespace App\Http\Controllers;

use App\Models\Storefront;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StorefrontSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $tenant     = $request->user()->tenant;
        $storefront = $tenant->storefront ?? new Storefront(['tenant_id' => $tenant->id]);

        return view('storefront_admin.settings', compact('tenant', 'storefront'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'is_enabled'       => 'boolean',
            'vat_applicable'   => 'boolean',
            'whatsapp_number'  => 'nullable|string|max:30',
            'description'      => 'nullable|string|max:2000',
            'pickup_enabled'   => 'boolean',
            'delivery_enabled' => 'boolean',
            'pickup_address'   => 'required_if:pickup_enabled,1|nullable|string|max:1000',
            'delivery_states'   => 'nullable|array',
            'delivery_states.*' => [Rule::in(config('nigeria_states'))],
        ]);

        if (!$request->boolean('pickup_enabled') && !$request->boolean('delivery_enabled')) {
            return back()->withInput()->withErrors([
                'delivery_enabled' => 'Enable at least one delivery option (pickup or delivery).',
            ]);
        }

        Storefront::withoutGlobalScope('tenant')->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'is_enabled'       => $request->boolean('is_enabled'),
                'vat_applicable'   => $request->boolean('vat_applicable'),
                'whatsapp_number'  => $validated['whatsapp_number'] ?? null,
                'description'      => $validated['description'] ?? null,
                'pickup_enabled'   => $request->boolean('pickup_enabled'),
                'delivery_enabled' => $request->boolean('delivery_enabled'),
                'pickup_address'   => $request->boolean('pickup_enabled') ? ($validated['pickup_address'] ?? null) : null,
                'delivery_states'  => $validated['delivery_states'] ?? [],
            ]
        );

        return back()->with('success', 'Storefront settings updated.');
    }

    public function uploadBanner(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $storefront = Storefront::withoutGlobalScope('tenant')->firstOrCreate(['tenant_id' => $tenant->id]);

        if ($storefront->banner_image && Storage::disk('public')->exists($storefront->banner_image)) {
            Storage::disk('public')->delete($storefront->banner_image);
        }

        $path = $request->file('banner')->store("storefront/{$tenant->id}/banner", 'public');
        $storefront->update(['banner_image' => $path]);

        return back()->with('success', 'Banner updated.');
    }

    public function deleteBanner(Request $request): RedirectResponse
    {
        $tenant     = $request->user()->tenant;
        $storefront = Storefront::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->first();

        if ($storefront?->banner_image && Storage::disk('public')->exists($storefront->banner_image)) {
            Storage::disk('public')->delete($storefront->banner_image);
        }

        $storefront?->update(['banner_image' => null]);

        return back()->with('success', 'Banner removed.');
    }
}
