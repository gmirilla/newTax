<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Bom;
use App\Models\InventoryItem;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderLine;
use App\Models\StockMovement;
use App\Services\BookkeepingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductionOrderController extends Controller
{
    public function __construct(private readonly BookkeepingService $bookkeeping) {}

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProductionOrder::class);

        $tenant = auth()->user()->tenant;

        $orders = ProductionOrder::where('production_orders.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->with(['finishedItem', 'bom', 'creator'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $stats = ProductionOrder::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->selectRaw("
                SUM(CASE WHEN status = 'draft'         THEN 1 ELSE 0 END) AS drafts,
                SUM(CASE WHEN status = 'in_production' THEN 1 ELSE 0 END) AS in_production,
                SUM(CASE WHEN status = 'completed'     THEN 1 ELSE 0 END) AS completed
            ")
            ->first();

        return view('manufacturing.production.index', compact('orders', 'stats'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $this->authorize('create', ProductionOrder::class);

        $tenant = auth()->user()->tenant;

        $boms = Bom::where('boms.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->with(['finishedItem', 'lines.rawMaterial'])
            ->orderBy('name')
            ->get();

        return view('manufacturing.production.create', compact('boms'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ProductionOrder::class);

        $validated = $request->validate([
            'bom_id'           => ['required', 'integer', 'exists:boms,id'],
            'quantity_planned'  => ['required', 'numeric', 'min:0.001'],
            'additional_cost'   => ['nullable', 'numeric', 'min:0'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        $bom = Bom::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->with('lines.rawMaterial')
            ->findOrFail($validated['bom_id']);

        $order = DB::transaction(function () use ($validated, $tenantId, $bom) {
            $qtyPlanned = (float) $validated['quantity_planned'];
            $factor     = $qtyPlanned / (float) $bom->yield_qty;

            $order = ProductionOrder::create([
                'tenant_id'        => $tenantId,
                'order_number'     => $this->generateOrderNumber($tenantId),
                'bom_id'           => $bom->id,
                'finished_item_id' => $bom->finished_item_id,
                'quantity_planned' => $qtyPlanned,
                'additional_cost'  => $validated['additional_cost'] ?? 0,
                'status'           => ProductionOrder::STATUS_DRAFT,
                'notes'            => $validated['notes'] ?? null,
                'created_by'       => auth()->id(),
            ]);

            foreach ($bom->lines as $line) {
                ProductionOrderLine::create([
                    'production_order_id'  => $order->id,
                    'raw_material_item_id' => $line->raw_material_item_id,
                    'quantity_required'    => round((float) $line->quantity_required * $factor, 3),
                    'unit_cost_at_production' => (float) $line->rawMaterial->avg_cost,
                ]);
            }

            return $order;
        });

        return redirect()->route('manufacturing.production.show', $order)
            ->with('success', "Production order {$order->order_number} created.");
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(ProductionOrder $productionOrder): View
    {
        $this->authorize('view', $productionOrder);

        $productionOrder->load(['finishedItem', 'bom', 'creator', 'lines.rawMaterial']);

        return view('manufacturing.production.show', compact('productionOrder'));
    }

    // ── Start Production ──────────────────────────────────────────────────────

    public function start(ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('start', $productionOrder);

        $productionOrder->update([
            'status'     => ProductionOrder::STATUS_IN_PRODUCTION,
            'started_at' => now(),
        ]);

        $productionOrder->loadMissing('creator', 'finishedItem');
        AuditLog::record(
            'production_order.started',
            $productionOrder,
            ['status' => 'draft'],
            [
                'reference'      => $productionOrder->order_number,
                'initiator_id'   => $productionOrder->created_by,
                'initiator_name' => $productionOrder->creator?->name ?? 'Unknown',
                'item'           => $productionOrder->finishedItem?->name,
                'qty_planned'    => $productionOrder->quantity_planned,
            ],
            'manufacturing,approval'
        );

        return back()->with('success', "Production order {$productionOrder->order_number} started.");
    }

    // ── Complete ──────────────────────────────────────────────────────────────

    public function complete(Request $request, ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('complete', $productionOrder);

        $productionOrder->load('lines');

        $lineIds = $productionOrder->lines->pluck('id')->toArray();

        $validated = $request->validate([
            'quantity_produced'            => ['required', 'numeric', 'min:0.001'],
            'additional_cost'              => ['nullable', 'numeric', 'min:0'],
            'lines'                        => ['required', 'array'],
            'lines.*.quantity_consumed'    => ['required', 'numeric', 'min:0'],
        ]);

        // Ensure every line in the order has a submitted value
        foreach ($lineIds as $lineId) {
            if (! isset($validated['lines'][$lineId])) {
                return back()->withErrors(['lines' => 'Material quantities are incomplete. Please reload and try again.'])->withInput();
            }
        }

        $tenant   = auth()->user()->tenant;
        $tenantId = $tenant->id;

        DB::transaction(function () use ($validated, $productionOrder, $tenant, $tenantId) {
            $qtyProduced    = (float) $validated['quantity_produced'];
            $additionalCost = (float) ($validated['additional_cost'] ?? $productionOrder->additional_cost);

            $productionOrder->load('lines', 'finishedItem');

            // ── 1. Lock raw material rows and validate stock sufficiency ───────
            $rawItemIds = $productionOrder->lines->pluck('raw_material_item_id')->unique();

            $lockedItems = InventoryItem::withoutGlobalScope('tenant')
                ->whereIn('id', $rawItemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $shortfalls = [];
            foreach ($productionOrder->lines as $line) {
                $rawItem     = $lockedItems->get($line->raw_material_item_id);
                $qtyConsumed = round((float) $validated['lines'][$line->id]['quantity_consumed'], 3);
                $qtyInStock  = (float) $rawItem->current_stock;

                if ($qtyConsumed > $qtyInStock + 0.0001) {
                    $shortfalls[] = "{$rawItem->name}: need {$qtyConsumed}, have {$qtyInStock}";
                }
            }

            if (! empty($shortfalls)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quantity_produced' => 'Insufficient raw material stock: ' . implode('; ', $shortfalls) . '.',
                ]);
            }

            // ── 2. Consume raw materials ──────────────────────────────────────
            $totalRawCost = 0;

            foreach ($productionOrder->lines as $line) {
                $rawItem     = $lockedItems->get($line->raw_material_item_id);
                $qtyConsumed = round((float) $validated['lines'][$line->id]['quantity_consumed'], 3);
                $unitCost    = (float) $rawItem->avg_cost;
                $totalRawCost += round($qtyConsumed * $unitCost, 2);
                $newBalance  = round((float) $rawItem->current_stock - $qtyConsumed, 3);

                $rawItem->update(['current_stock' => $newBalance]);

                StockMovement::create([
                    'tenant_id'       => $tenantId,
                    'item_id'         => $rawItem->id,
                    'type'            => 'production_out',
                    'quantity'        => $qtyConsumed,
                    'unit_cost'       => $unitCost,
                    'running_balance' => $newBalance,
                    'reference_type'  => ProductionOrder::class,
                    'reference_id'    => $productionOrder->id,
                    'notes'           => "Production: {$productionOrder->order_number}",
                    'created_by'      => auth()->id(),
                ]);

                $line->update([
                    'quantity_consumed'       => $qtyConsumed,
                    'unit_cost_at_production' => $unitCost,
                ]);
            }

            // ── 2. Add finished goods to stock ────────────────────────────────
            $finishedItem    = $productionOrder->finishedItem;
            $totalCost       = $totalRawCost + $additionalCost;
            $newAvgCost      = $qtyProduced > 0 ? round($totalCost / $qtyProduced, 4) : 0;
            $newStock        = round((float) $finishedItem->current_stock + $qtyProduced, 3);

            // Weighted average cost update
            $prevQty         = (float) $finishedItem->current_stock;
            $prevValue       = $prevQty * (float) $finishedItem->avg_cost;
            $newTotalQty     = $prevQty + $qtyProduced;
            $blendedAvgCost  = $newTotalQty > 0
                ? round(($prevValue + $totalCost) / $newTotalQty, 4)
                : $newAvgCost;

            $finishedItem->update([
                'current_stock' => $newStock,
                'avg_cost'      => $blendedAvgCost,
            ]);

            StockMovement::create([
                'tenant_id'       => $tenantId,
                'item_id'         => $finishedItem->id,
                'type'            => 'production_in',
                'quantity'        => $qtyProduced,
                'unit_cost'       => $newAvgCost,
                'running_balance' => $newStock,
                'reference_type'  => ProductionOrder::class,
                'reference_id'    => $productionOrder->id,
                'notes'           => "Production complete: {$productionOrder->order_number}",
                'created_by'      => auth()->id(),
            ]);

            // ── 3. GL: Dr 1202 Finished Goods / Cr 1201 Raw Materials ─────────
            $accounts = Account::where('tenant_id', $tenantId)
                ->withoutGlobalScope('tenant')
                ->whereIn('code', ['1201', '1202'])
                ->pluck('id', 'code');

            if ($accounts->has('1201') && $accounts->has('1202')) {
                $this->bookkeeping->postJournalEntry(
                    $tenant,
                    [
                        'reference'        => $productionOrder->order_number,
                        'transaction_date' => now()->toDateString(),
                        'type'             => 'journal',
                        'description'      => "Production: {$productionOrder->order_number} — {$finishedItem->name}",
                    ],
                    [
                        [
                            'account_id'  => $accounts['1202'],
                            'entry_type'  => 'debit',
                            'amount'      => round($totalCost, 2),
                            'description' => "Finished goods: {$finishedItem->name} × {$qtyProduced}",
                        ],
                        [
                            'account_id'  => $accounts['1201'],
                            'entry_type'  => 'credit',
                            'amount'      => round($totalCost, 2),
                            'description' => "Raw materials consumed: {$productionOrder->order_number}",
                        ],
                    ]
                );
            }

            // ── 4. Finalise order ─────────────────────────────────────────────
            $productionOrder->update([
                'status'            => ProductionOrder::STATUS_COMPLETED,
                'quantity_produced' => $qtyProduced,
                'additional_cost'   => $additionalCost,
                'completed_at'      => now(),
            ]);
        });

        $productionOrder->loadMissing('creator', 'finishedItem');
        AuditLog::record(
            'production_order.completed',
            $productionOrder,
            ['status' => 'in_production'],
            [
                'reference'      => $productionOrder->order_number,
                'initiator_id'   => $productionOrder->created_by,
                'initiator_name' => $productionOrder->creator?->name ?? 'Unknown',
                'item'           => $productionOrder->finishedItem?->name,
                'qty_produced'   => $productionOrder->quantity_produced,
                'additional_cost'=> $productionOrder->additional_cost,
            ],
            'manufacturing,approval'
        );

        return redirect()->route('manufacturing.production.show', $productionOrder)
            ->with('success', "Production order {$productionOrder->order_number} completed. Finished goods added to stock.");
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function cancel(ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('cancel', $productionOrder);

        $oldStatus = $productionOrder->status;
        $productionOrder->loadMissing('creator', 'finishedItem');
        $productionOrder->update(['status' => ProductionOrder::STATUS_CANCELLED]);

        AuditLog::record(
            'production_order.cancelled',
            $productionOrder,
            ['status' => $oldStatus],
            [
                'reference'      => $productionOrder->order_number,
                'initiator_id'   => $productionOrder->created_by,
                'initiator_name' => $productionOrder->creator?->name ?? 'Unknown',
                'item'           => $productionOrder->finishedItem?->name,
                'qty_planned'    => $productionOrder->quantity_planned,
            ],
            'manufacturing,approval'
        );

        return redirect()->route('manufacturing.production.index')
            ->with('success', "Production order {$productionOrder->order_number} cancelled.");
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function generateOrderNumber(int $tenantId): string
    {
        $prefix = 'PROD-' . now()->format('Ym') . '-';

        $last = ProductionOrder::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->where('order_number', 'like', $prefix . '%')
            ->orderBy('order_number', 'desc')
            ->lockForUpdate()
            ->first();

        $next = $last ? ((int) substr($last->order_number, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
