<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * Quick-create a vendor via AJAX from the expense form.
     * Returns JSON so the JS can add the new option to the select.
     */
    public function quickStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:30',
            'tin'         => 'nullable|string|max:50',
            'rc_number'   => 'nullable|string|max:50',
            'vendor_type' => 'in:goods,services,rent,mixed',
        ]);

        $tenant = $request->user()->tenant;

        $existing = Vendor::where('name', $data['name'])->first();
        if ($existing) {
            return response()->json([
                'id'       => $existing->id,
                'name'     => $existing->name,
                'wht_rate' => $existing->wht_rate,
                'note'     => 'existing',
            ]);
        }

        $vendorType = $data['vendor_type'] ?? 'services';
        $whtRate    = match($vendorType) {
            'rent'  => 10.0,
            default => 5.0,
        };

        $vendor = Vendor::create(array_merge($data, [
            'tenant_id'   => $tenant->id,
            'vendor_type' => $vendorType,
            'wht_rate'    => $whtRate,
            'is_active'   => true,
        ]));

        return response()->json([
            'id'       => $vendor->id,
            'name'     => $vendor->name,
            'wht_rate' => $vendor->wht_rate,
            'note'     => 'created',
        ], 201);
    }
}
