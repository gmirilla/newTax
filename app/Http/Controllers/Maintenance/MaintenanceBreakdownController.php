<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MaintenanceAsset;
use App\Models\MaintenanceBreakdown;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;

class MaintenanceBreakdownController extends Controller
{
    public function __construct(private MaintenanceService $service) {}

    public function index(Request $request)
    {
        $query = MaintenanceBreakdown::with(['asset', 'reporter', 'workOrder'])
            ->orderByDesc('downtime_start');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($assetId = $request->input('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        $breakdowns = $query->paginate(20)->withQueryString();
        $assets     = MaintenanceAsset::orderBy('asset_name')->get();
        $statuses   = [
            MaintenanceBreakdown::STATUS_OPEN        => 'Open',
            MaintenanceBreakdown::STATUS_IN_PROGRESS => 'In Progress',
            MaintenanceBreakdown::STATUS_RESOLVED    => 'Resolved',
            MaintenanceBreakdown::STATUS_CLOSED      => 'Closed',
        ];

        return view('maintenance.breakdowns.index', compact('breakdowns', 'assets', 'statuses'));
    }

    public function create(Request $request)
    {
        $assets   = MaintenanceAsset::where('status', '!=', MaintenanceAsset::STATUS_RETIRED)
            ->orderBy('asset_name')->get();
        $preAsset = $request->input('asset_id');

        return view('maintenance.breakdowns.create', compact('assets', 'preAsset'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', MaintenanceBreakdown::class);

        $data = $request->validate([
            'asset_id'          => 'required|exists:maintenance_assets,id',
            'issue_description' => 'required|string|max:2000',
            'severity'          => 'required|in:low,medium,high,critical',
            'downtime_start'    => 'required|date',
            'create_work_order' => 'boolean',
        ]);

        $data['reported_by']   = auth()->id();
        $createWO              = $request->boolean('create_work_order', true);

        $tenant    = auth()->user()->tenant;
        $breakdown = $this->service->reportBreakdown($tenant, $data, $createWO);

        AuditLog::record('breakdown_reported', $breakdown, [], $breakdown->toArray());

        return redirect()->route('maintenance.breakdowns.show', $breakdown)
            ->with('success', "Breakdown {$breakdown->breakdown_number} reported.");
    }

    public function show(MaintenanceBreakdown $breakdown)
    {
        $this->authorize('view', $breakdown);

        $breakdown->load(['asset', 'reporter', 'workOrder.assignee']);

        return view('maintenance.breakdowns.show', compact('breakdown'));
    }

    public function resolve(Request $request, MaintenanceBreakdown $breakdown)
    {
        $this->authorize('update', $breakdown);

        if (!$breakdown->isResolvable()) {
            return back()->with('error', "Breakdown is already resolved or closed.");
        }

        $data = $request->validate([
            'root_cause'         => 'required|string|max:2000',
            'corrective_action'  => 'required|string|max:2000',
            'downtime_end'       => 'required|date|after:' . $breakdown->downtime_start->toDateTimeString(),
        ]);

        $downtimeEnd   = new \Carbon\Carbon($data['downtime_end']);
        $downtimeHours = round($breakdown->downtime_start->diffInMinutes($downtimeEnd) / 60, 2);

        $breakdown->update([
            'root_cause'        => $data['root_cause'],
            'corrective_action' => $data['corrective_action'],
            'downtime_end'      => $downtimeEnd,
            'downtime_hours'    => $downtimeHours,
            'status'            => MaintenanceBreakdown::STATUS_RESOLVED,
        ]);

        // Restore asset status if no other open breakdowns
        $otherOpen = MaintenanceBreakdown::withoutGlobalScope('tenant')
            ->where('asset_id', $breakdown->asset_id)
            ->where('id', '!=', $breakdown->id)
            ->where('status', MaintenanceBreakdown::STATUS_OPEN)
            ->exists();

        if (!$otherOpen) {
            MaintenanceAsset::withoutGlobalScope('tenant')
                ->where('id', $breakdown->asset_id)
                ->where('status', MaintenanceAsset::STATUS_BREAKDOWN)
                ->update(['status' => MaintenanceAsset::STATUS_ACTIVE]);
        }

        AuditLog::record('breakdown_resolved', $breakdown, [], $breakdown->fresh()->toArray());

        return back()->with('success', "Breakdown resolved. Downtime: {$downtimeHours} hrs.");
    }

    public function close(MaintenanceBreakdown $breakdown)
    {
        $this->authorize('update', $breakdown);

        $breakdown->update(['status' => MaintenanceBreakdown::STATUS_CLOSED]);

        return back()->with('success', 'Breakdown record closed.');
    }
}
