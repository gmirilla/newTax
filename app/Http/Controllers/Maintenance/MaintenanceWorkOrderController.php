<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\MaintenanceAsset;
use App\Models\MaintenanceWorkOrder;
use App\Models\MaintenanceWorkOrderPart;
use App\Models\User;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;

class MaintenanceWorkOrderController extends Controller
{
    public function __construct(private MaintenanceService $service) {}

    public function index(Request $request)
    {
        $query = MaintenanceWorkOrder::with(['asset', 'assignee'])
            ->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('work_order_number', db_like(), "%{$search}%")
                  ->orWhere('title', db_like(), "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($assetId = $request->input('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        $workOrders = $query->paginate(20)->withQueryString();
        $assets     = MaintenanceAsset::where('status', '!=', MaintenanceAsset::STATUS_RETIRED)
            ->orderBy('asset_name')->get();
        $statuses   = [
            MaintenanceWorkOrder::STATUS_OPEN              => 'Open',
            MaintenanceWorkOrder::STATUS_ASSIGNED          => 'Assigned',
            MaintenanceWorkOrder::STATUS_IN_PROGRESS       => 'In Progress',
            MaintenanceWorkOrder::STATUS_WAITING_FOR_PARTS => 'Waiting for Parts',
            MaintenanceWorkOrder::STATUS_COMPLETED         => 'Completed',
            MaintenanceWorkOrder::STATUS_CLOSED            => 'Closed',
        ];

        return view('maintenance.work-orders.index', compact('workOrders', 'assets', 'statuses'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', MaintenanceWorkOrder::class);

        $assets      = MaintenanceAsset::where('status', '!=', MaintenanceAsset::STATUS_RETIRED)
            ->orderBy('asset_name')->get();
        $technicians = User::where('is_active', true)->orderBy('name')->get();
        $preAsset    = $request->input('asset_id');

        return view('maintenance.work-orders.create', compact('assets', 'technicians', 'preAsset'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', MaintenanceWorkOrder::class);

        $data = $request->validate([
            'source_type'     => 'required|in:preventive,corrective',
            'asset_id'        => 'required|exists:maintenance_assets,id',
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string|max:3000',
            'priority'        => 'required|in:low,medium,high,critical',
            'assigned_to'     => 'nullable|exists:users,id',
            'scheduled_date'  => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:9999',
        ]);

        $tenant = auth()->user()->tenant;

        $wo = $this->service->createWorkOrder($tenant, array_merge($data, [
            'created_by' => auth()->id(),
        ]));

        if ($data['assigned_to'] ?? null) {
            $wo->update(['status' => MaintenanceWorkOrder::STATUS_ASSIGNED]);
        }

        AuditLog::record('work_order_created', $wo, [], $wo->toArray());

        return redirect()->route('maintenance.work-orders.show', $wo)
            ->with('success', "Work order {$wo->work_order_number} created.");
    }

    public function show(MaintenanceWorkOrder $workOrder)
    {
        $this->authorize('view', $workOrder);

        $workOrder->load(['asset', 'assignee', 'breakdown', 'schedule', 'parts.inventoryItem', 'laborLogs.technician', 'cost']);

        $availableItems = InventoryItem::where('is_active', true)
            ->where('current_stock', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'avg_cost', 'current_stock']);

        $technicians = User::where('is_active', true)->orderBy('name')->get();

        return view('maintenance.work-orders.show', compact('workOrder', 'availableItems', 'technicians'));
    }

    // ── Status Transitions ────────────────────────────────────────────────────

    public function start(MaintenanceWorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        if (!$workOrder->canStart()) {
            return back()->with('error', "Work order cannot be started from status: {$workOrder->status}.");
        }

        $this->service->startWorkOrder($workOrder);
        AuditLog::record('work_order_started', $workOrder, ['status' => 'previous'], ['status' => MaintenanceWorkOrder::STATUS_IN_PROGRESS]);

        return back()->with('success', 'Work order marked as In Progress.');
    }

    public function complete(Request $request, MaintenanceWorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        if (!$workOrder->canComplete()) {
            return back()->with('error', "Work order cannot be completed from its current status.");
        }

        $remarks = $request->input('remarks');
        $this->service->completeWorkOrder($workOrder, $remarks);
        AuditLog::record('work_order_completed', $workOrder, [], ['status' => MaintenanceWorkOrder::STATUS_COMPLETED]);

        return back()->with('success', 'Work order marked as Completed.');
    }

    public function close(MaintenanceWorkOrder $workOrder)
    {
        $this->authorize('close', $workOrder);

        if (!$workOrder->canClose()) {
            return back()->with('error', "Work order must be in Completed status before closing.");
        }

        try {
            $this->service->closeWorkOrder($workOrder);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLog::record('work_order_closed', $workOrder, [], ['status' => MaintenanceWorkOrder::STATUS_CLOSED]);

        return back()->with('success', 'Work order closed. Inventory and GL entries posted.');
    }

    // ── Parts ─────────────────────────────────────────────────────────────────

    public function addPart(Request $request, MaintenanceWorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        if ($workOrder->isClosed()) {
            return back()->with('error', 'Cannot modify a closed work order.');
        }

        $data = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity_requested'=> 'required|numeric|min:0.001',
            'notes'             => 'nullable|string|max:500',
        ]);

        $item     = InventoryItem::findOrFail($data['inventory_item_id']);
        $unitCost = (float) $item->avg_cost ?: (float) $item->cost_price;

        MaintenanceWorkOrderPart::create([
            'tenant_id'          => $workOrder->tenant_id,
            'work_order_id'      => $workOrder->id,
            'inventory_item_id'  => $item->id,
            'quantity_requested' => $data['quantity_requested'],
            'unit_cost'          => $unitCost,
            'subtotal'           => round((float) $data['quantity_requested'] * $unitCost, 2),
            'notes'              => $data['notes'] ?? null,
            'created_by'         => auth()->id(),
        ]);

        return back()->with('success', "Part '{$item->name}' added to work order.");
    }

    public function removePart(MaintenanceWorkOrder $workOrder, MaintenanceWorkOrderPart $part)
    {
        $this->authorize('update', $workOrder);

        if ($workOrder->isClosed()) {
            return back()->with('error', 'Cannot modify a closed work order.');
        }

        $part->delete();
        return back()->with('success', 'Part removed from work order.');
    }

    // ── Labor ─────────────────────────────────────────────────────────────────

    public function logLabor(Request $request, MaintenanceWorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        if ($workOrder->isClosed()) {
            return back()->with('error', 'Cannot modify a closed work order.');
        }

        $data = $request->validate([
            'work_date'    => 'required|date|before_or_equal:today',
            'hours_worked' => 'required|numeric|min:0.5|max:24',
            'hourly_rate'  => 'nullable|numeric|min:0',
            'description'  => 'nullable|string|max:500',
        ]);

        $this->service->logLabor($workOrder, array_merge($data, ['user_id' => auth()->id()]));

        return back()->with('success', 'Labor hours logged.');
    }
}
