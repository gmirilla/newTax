<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Quick-create a customer via AJAX from the invoice form.
     * Returns JSON so the JS can add the new option to the select.
     */
    public function quickStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:30',
            'tin'        => 'nullable|string|max:50',
            'rc_number'  => 'nullable|string|max:50',
            'address'    => 'nullable|string|max:500',
            'is_company' => 'boolean',
        ]);

        $tenant = $request->user()->tenant;

        // Prevent duplicates within the same tenant
        $existing = Customer::where('name', $data['name'])->first();
        if ($existing) {
            return response()->json([
                'id'   => $existing->id,
                'name' => $existing->name,
                'note' => 'existing',
            ]);
        }

        if (!$tenant->withinLimit('customers')) {
            $limit = $tenant->plan?->limit('customers') ?? 20;
            return response()->json([
                'message' => "You have reached your plan limit of {$limit} customers. Upgrade your plan to add more.",
            ], 422);
        }

        $customer = Customer::create(array_merge($data, [
            'tenant_id'  => $tenant->id,
            'is_active'  => true,
            'is_company' => $data['is_company'] ?? true,
        ]));

        $tenant->invalidateLimitCache('customers');

        return response()->json([
            'id'   => $customer->id,
            'name' => $customer->name,
            'note' => 'created',
        ], 201);
    }
}
