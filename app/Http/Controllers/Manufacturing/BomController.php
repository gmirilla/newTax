<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\BomLine;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BomController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Bom::class);

        $tenant = auth()->user()->tenant;

        $boms = Bom::where('boms.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->with(['finishedItem', 'lines.rawMaterial'])
            ->orderBy('name')
            ->get();

        $finishedItems = InventoryItem::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->whereIn('item_type', ['finished_good', 'semi_finished'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit']);

        $rawMaterials = InventoryItem::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('item_type', 'raw_material')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'avg_cost']);

        return view('manufacturing.boms.index', compact('boms', 'finishedItems', 'rawMaterials'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Bom::class);

        $validated = $request->validate([
            'finished_item_id'      => ['required', 'integer', 'exists:inventory_items,id'],
            'name'                  => ['required', 'string', 'max:100'],
            'version'               => ['nullable', 'string', 'max:20'],
            'yield_qty'             => ['required', 'numeric', 'min:0.001'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'lines'                 => ['required', 'array', 'min:1'],
            'lines.*.raw_material_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'lines.*.quantity_required'    => ['required', 'numeric', 'min:0.001'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        DB::transaction(function () use ($validated, $tenantId) {
            $bom = Bom::create([
                'tenant_id'        => $tenantId,
                'finished_item_id' => $validated['finished_item_id'],
                'name'             => $validated['name'],
                'version'          => $validated['version'] ?? '1.0',
                'yield_qty'        => $validated['yield_qty'],
                'notes'            => $validated['notes'] ?? null,
                'is_active'        => true,
                'created_by'       => auth()->id(),
            ]);

            foreach ($validated['lines'] as $line) {
                BomLine::create([
                    'bom_id'               => $bom->id,
                    'raw_material_item_id' => $line['raw_material_item_id'],
                    'quantity_required'    => $line['quantity_required'],
                ]);
            }
        });

        return redirect()->route('manufacturing.boms.index')
            ->with('success', "Bill of Materials \"{$validated['name']}\" created.");
    }

    public function update(Request $request, Bom $bom): RedirectResponse
    {
        $this->authorize('update', $bom);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'version'   => ['nullable', 'string', 'max:20'],
            'yield_qty' => ['required', 'numeric', 'min:0.001'],
            'is_active' => ['boolean'],
            'notes'     => ['nullable', 'string', 'max:1000'],
            'lines'     => ['required', 'array', 'min:1'],
            'lines.*.raw_material_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'lines.*.quantity_required'    => ['required', 'numeric', 'min:0.001'],
        ]);

        DB::transaction(function () use ($validated, $bom) {
            $bom->update([
                'name'      => $validated['name'],
                'version'   => $validated['version'] ?? $bom->version,
                'yield_qty' => $validated['yield_qty'],
                'is_active' => $validated['is_active'] ?? $bom->is_active,
                'notes'     => $validated['notes'] ?? null,
            ]);

            $bom->lines()->delete();

            foreach ($validated['lines'] as $line) {
                BomLine::create([
                    'bom_id'               => $bom->id,
                    'raw_material_item_id' => $line['raw_material_item_id'],
                    'quantity_required'    => $line['quantity_required'],
                ]);
            }
        });

        return redirect()->route('manufacturing.boms.index')
            ->with('success', "Bill of Materials updated.");
    }

    public function destroy(Bom $bom): RedirectResponse
    {
        $this->authorize('delete', $bom);

        $bom->lines()->delete();
        $bom->delete();

        return redirect()->route('manufacturing.boms.index')
            ->with('success', "Bill of Materials deleted.");
    }
}
