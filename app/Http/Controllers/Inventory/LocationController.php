<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryLocation;
use App\Traits\ResolvesLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    use ResolvesLocation;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $tenant    = auth()->user()->tenant;
        $locations = InventoryLocation::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $active = $this->activeLocation();

        return view('inventory.locations.index', compact('locations', 'active'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', InventoryLocation::class);

        $tenant    = auth()->user()->tenant;
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['nullable', 'string', 'max:20',
                               Rule::unique('inventory_locations')->where('tenant_id', $tenant->id)],
            'address'       => ['nullable', 'string', 'max:500'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'contact_name'  => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'is_default'    => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $tenant) {
            if (!empty($validated['is_default'])) {
                InventoryLocation::where('tenant_id', $tenant->id)
                    ->withoutGlobalScope('tenant')
                    ->update(['is_default' => false]);
            }

            InventoryLocation::create([
                'tenant_id'     => $tenant->id,
                'name'          => $validated['name'],
                'code'          => $validated['code'] ?? null,
                'address'       => $validated['address'] ?? null,
                'city'          => $validated['city'] ?? null,
                'state'         => $validated['state'] ?? null,
                'contact_name'  => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'is_default'    => !empty($validated['is_default']),
                'is_active'     => true,
            ]);
        });

        return redirect()->route('inventory.locations.index')
            ->with('success', "Location \"{$validated['name']}\" added.");
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, InventoryLocation $location): RedirectResponse
    {
        $this->authorize('update', $location);

        $tenant    = auth()->user()->tenant;
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['nullable', 'string', 'max:20',
                               Rule::unique('inventory_locations')->where('tenant_id', $tenant->id)->ignore($location->id)],
            'address'       => ['nullable', 'string', 'max:500'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'contact_name'  => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'is_default'    => ['nullable', 'boolean'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $location, $tenant) {
            if (!empty($validated['is_default'])) {
                InventoryLocation::where('tenant_id', $tenant->id)
                    ->withoutGlobalScope('tenant')
                    ->where('id', '!=', $location->id)
                    ->update(['is_default' => false]);
            }

            $location->update([
                'name'          => $validated['name'],
                'code'          => $validated['code'] ?? null,
                'address'       => $validated['address'] ?? null,
                'city'          => $validated['city'] ?? null,
                'state'         => $validated['state'] ?? null,
                'contact_name'  => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'is_default'    => !empty($validated['is_default']),
                'is_active'     => !empty($validated['is_active']),
            ]);
        });

        return redirect()->route('inventory.locations.index')
            ->with('success', "Location \"{$location->name}\" updated.");
    }

    // ── Deactivate ────────────────────────────────────────────────────────────

    public function destroy(InventoryLocation $location): RedirectResponse
    {
        $this->authorize('delete', $location);

        if ($location->is_default) {
            return back()->with('error', 'Cannot deactivate the default location. Set another location as default first.');
        }

        $location->update(['is_active' => false]);

        // If the deactivated location was the active session location, clear it
        if (session('inventory_location_id') == $location->id) {
            session()->forget('inventory_location_id');
        }

        return redirect()->route('inventory.locations.index')
            ->with('success', "Location \"{$location->name}\" deactivated.");
    }

    // ── Session switcher ──────────────────────────────────────────────────────

    public function switchLocation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer'],
        ]);

        $tenant   = auth()->user()->tenant;
        $location = InventoryLocation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('id', $validated['location_id'])
            ->where('is_active', true)
            ->firstOrFail();

        session(['inventory_location_id' => $location->id]);

        return redirect()->back()->with('success', "Switched to {$location->name}.");
    }
}
