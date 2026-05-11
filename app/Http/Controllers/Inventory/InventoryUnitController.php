<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryUnitController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;

        $units = InventoryUnit::where('inventory_units.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->withCount(['items' => fn($q) => $q->withoutGlobalScope('tenant')->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return view('inventory.units.index', compact('units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', InventoryUnit::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        $exists = InventoryUnit::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereRaw('lower(name) = ?', [strtolower($validated['name'])])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'A unit with that name already exists.'])->withInput();
        }

        InventoryUnit::create([
            'tenant_id' => $tenantId,
            'name'      => $validated['name'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Unit created.');
    }

    public function update(Request $request, InventoryUnit $unit): RedirectResponse
    {
        $this->authorize('update', $unit);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $tenantId = auth()->user()->tenant_id;
        $exists = InventoryUnit::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $unit->id)
            ->whereRaw('lower(name) = ?', [strtolower($validated['name'])])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Another unit with that name already exists.'])->withInput();
        }

        $unit->update($validated);

        return back()->with('success', 'Unit updated.');
    }

    public function destroy(InventoryUnit $unit): RedirectResponse
    {
        $this->authorize('delete', $unit);

        if ($unit->items()->withoutGlobalScope('tenant')->exists()) {
            return back()->with('error', 'Cannot delete a unit that is in use by inventory items.');
        }

        $unit->delete();

        return back()->with('success', 'Unit deleted.');
    }
}
