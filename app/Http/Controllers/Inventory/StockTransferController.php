<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\StockMovement;
use App\Traits\ResolvesLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    use ResolvesLocation;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $tenant = auth()->user()->tenant;

        $locationIds = InventoryLocation::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->pluck('id');

        // Transfers are pairs; show the transfer_out side as the primary record
        $transfers = StockMovement::whereIn('location_id', $locationIds)
            ->withoutGlobalScope('tenant')
            ->where('type', 'transfer_out')
            ->with(['item', 'location', 'transferPair.location', 'creator'])
            ->when($request->filled('from'), fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'),   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $active = $this->activeLocation();

        return view('inventory.transfers.index', compact('transfers', 'active'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $tenant = auth()->user()->tenant;

        $locations = InventoryLocation::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $items = InventoryItem::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'current_stock']);

        $active = $this->activeLocation();

        return view('inventory.transfers.create', compact('locations', 'items', 'active'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $tenant    = auth()->user()->tenant;
        $locationIds = InventoryLocation::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $validated = $request->validate([
            'from_location_id' => ['required', 'integer', 'in:' . implode(',', $locationIds)],
            'to_location_id'   => ['required', 'integer', 'in:' . implode(',', $locationIds), 'different:from_location_id'],
            'item_id'          => ['required', 'integer', 'exists:inventory_items,id'],
            'quantity'         => ['required', 'numeric', 'min:0.001'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $outMovement = null;

        DB::transaction(function () use ($validated, $tenant, &$outMovement) {
            $item     = InventoryItem::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->findOrFail($validated['item_id']);

            $fromId   = (int) $validated['from_location_id'];
            $qty      = (float) $validated['quantity'];

            $fromStock = $item->stockAtLocation($fromId);

            if ($fromStock < $qty) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock at source location: {$fromStock} {$item->unit} available.",
                ]);
            }

            // Insert transfer_out first (without pair link), then update with pair
            $out = StockMovement::create([
                'tenant_id'       => $tenant->id,
                'item_id'         => $item->id,
                'location_id'     => $fromId,
                'type'            => 'transfer_out',
                'quantity'        => $qty,
                'unit_cost'       => $item->avg_cost,
                'running_balance' => round($item->current_stock - $qty, 3),
                'notes'           => $validated['notes'] ?? null,
                'created_by'      => auth()->id(),
            ]);

            $in = StockMovement::create([
                'tenant_id'        => $tenant->id,
                'item_id'          => $item->id,
                'location_id'      => (int) $validated['to_location_id'],
                'type'             => 'transfer_in',
                'quantity'         => $qty,
                'unit_cost'        => $item->avg_cost,
                'running_balance'  => round($item->current_stock, 3),
                'transfer_pair_id' => $out->id,
                'notes'            => $validated['notes'] ?? null,
                'created_by'       => auth()->id(),
            ]);

            $out->update(['transfer_pair_id' => $in->id]);

            $outMovement = $out;
        });

        $fromName = InventoryLocation::withoutGlobalScope('tenant')->find($validated['from_location_id'])?->name;
        $toName   = InventoryLocation::withoutGlobalScope('tenant')->find($validated['to_location_id'])?->name;

        AuditLog::record('inventory.transfer', $outMovement, [], [
            'item_id'  => $validated['item_id'],
            'quantity' => $validated['quantity'],
            'from'     => $fromName,
            'to'       => $toName,
        ], 'inventory');

        return redirect()->route('inventory.transfers.index')
            ->with('success', "Stock transferred from {$fromName} to {$toName}.");
    }
}
