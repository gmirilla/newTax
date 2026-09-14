<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Vendor::class);

        $vendors = Vendor::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('name')
            ->get();

        return view('vendors.index', compact('vendors'));
    }

    public function edit(Vendor $vendor): View
    {
        $this->authorize('update', $vendor);

        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('update', $vendor);

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'address'           => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:100',
            'state'             => 'nullable|string|max:100',
            'tin'               => 'nullable|string|max:50',
            'rc_number'         => 'nullable|string|max:50',
            'vendor_type'       => 'required|in:goods,services,rent,mixed',
            'wht_rate'          => 'required|numeric|min:0|max:100',
            'wht_exempt'        => 'boolean',
            'wht_exempt_reason' => 'nullable|string|in:' . implode(',', array_keys(Vendor::WHT_EXEMPT_REASONS)),
            'is_active'         => 'boolean',
        ]);

        $whtExempt = (bool) ($validated['wht_exempt'] ?? false);

        $vendor->update([
            ...$validated,
            'wht_rate'          => $whtExempt ? 0.0 : $validated['wht_rate'],
            'wht_exempt'        => $whtExempt,
            'wht_exempt_reason' => $whtExempt ? ($validated['wht_exempt_reason'] ?? null) : null,
            'is_active'         => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorize('delete', $vendor);

        if ($vendor->expenses()->exists() || $vendor->whtRecords()->exists()) {
            return back()->with('error', 'Cannot delete a vendor with existing expenses or WHT records. Deactivate it instead.');
        }

        $vendor->delete();

        return back()->with('success', 'Vendor deleted.');
    }

    /**
     * Quick-create a vendor via AJAX from the expense form.
     * Returns JSON so the JS can add the new option to the select.
     */
    public function quickStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'tin'               => 'nullable|string|max:50',
            'rc_number'         => 'nullable|string|max:50',
            'vendor_type'       => 'in:goods,services,rent,mixed',
            'wht_exempt'        => 'boolean',
            'wht_exempt_reason' => 'nullable|string|in:' . implode(',', array_keys(\App\Models\Vendor::WHT_EXEMPT_REASONS)),
        ]);

        $tenant = $request->user()->tenant;

        $existing = Vendor::where('name', $data['name'])->first();
        if ($existing) {
            return response()->json([
                'id'         => $existing->id,
                'name'       => $existing->name,
                'wht_rate'   => $existing->wht_rate,
                'wht_exempt' => $existing->wht_exempt,
                'note'       => 'existing',
            ]);
        }

        $whtExempt  = !empty($data['wht_exempt']);
        $vendorType = $data['vendor_type'] ?? 'services';
        $whtRate    = $whtExempt ? 0.0 : match($vendorType) {
            'rent'  => 10.0,
            default => 5.0,
        };

        $vendor = Vendor::create(array_merge($data, [
            'tenant_id'         => $tenant->id,
            'vendor_type'       => $vendorType,
            'wht_rate'          => $whtRate,
            'wht_exempt'        => $whtExempt,
            'wht_exempt_reason' => $whtExempt ? ($data['wht_exempt_reason'] ?? null) : null,
            'is_active'         => true,
        ]));

        return response()->json([
            'id'         => $vendor->id,
            'name'       => $vendor->name,
            'wht_rate'   => $vendor->wht_rate,
            'wht_exempt' => $vendor->wht_exempt,
            'note'       => 'created',
        ], 201);
    }
}
