<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MaintenanceAsset;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Illuminate\Http\Request;

class MaintenanceScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = MaintenanceSchedule::with(['asset', 'assignedTechnician'])
            ->orderBy('next_due_date');

        if ($assetId = $request->input('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        if ($request->boolean('overdue')) {
            $query->where('next_due_date', '<', now()->toDateString());
        }

        $schedules = $query->paginate(20)->withQueryString();
        $assets    = MaintenanceAsset::where('status', '!=', MaintenanceAsset::STATUS_RETIRED)
            ->orderBy('asset_name')->get();

        return view('maintenance.schedules.index', compact('schedules', 'assets'));
    }

    public function create(Request $request)
    {
        $assets      = MaintenanceAsset::where('status', '!=', MaintenanceAsset::STATUS_RETIRED)
            ->orderBy('asset_name')->get();
        $technicians = User::where('is_active', true)->orderBy('name')->get();
        $preAsset    = $request->input('asset_id');

        return view('maintenance.schedules.create', compact('assets', 'technicians', 'preAsset'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', MaintenanceSchedule::class);

        $data = $request->validate([
            'asset_id'                => 'required|exists:maintenance_assets,id',
            'name'                    => 'required|string|max:150',
            'maintenance_type'        => 'required|in:general,lubrication,inspection,calibration,overhaul',
            'frequency_type'          => 'required|in:daily,weekly,monthly,custom_interval',
            'frequency_days'          => 'required_if:frequency_type,custom_interval|integer|min:1|max:3650',
            'next_due_date'           => 'required|date',
            'estimated_hours'         => 'nullable|numeric|min:0.1|max:999',
            'checklist'               => 'nullable|string',
            'assigned_technician_id'  => 'nullable|exists:users,id',
            'is_active'               => 'boolean',
        ]);

        // Parse checklist from textarea (one item per line)
        $checklist = null;
        if (!empty($data['checklist'])) {
            $checklist = array_values(array_filter(
                array_map('trim', explode("\n", $data['checklist']))
            ));
        }

        // Auto-set frequency_days for non-custom types
        if ($data['frequency_type'] !== MaintenanceSchedule::FREQUENCY_CUSTOM) {
            $data['frequency_days'] = MaintenanceSchedule::FREQUENCY_DAYS[$data['frequency_type']];
        }

        $tenant = auth()->user()->tenant;

        $schedule = MaintenanceSchedule::create([
            'tenant_id'               => $tenant->id,
            'asset_id'                => $data['asset_id'],
            'name'                    => $data['name'],
            'maintenance_type'        => $data['maintenance_type'],
            'frequency_type'          => $data['frequency_type'],
            'frequency_days'          => $data['frequency_days'],
            'next_due_date'           => $data['next_due_date'],
            'estimated_hours'         => $data['estimated_hours'] ?? 1,
            'checklist'               => $checklist,
            'assigned_technician_id'  => $data['assigned_technician_id'] ?? null,
            'is_active'               => $data['is_active'] ?? true,
            'created_by'              => auth()->id(),
        ]);

        AuditLog::record('maintenance_schedule_created', $schedule, [], $schedule->toArray());

        return redirect()->route('maintenance.schedules.index')
            ->with('success', "PM schedule '{$schedule->name}' created.");
    }

    public function destroy(MaintenanceSchedule $schedule)
    {
        $this->authorize('delete', $schedule);

        AuditLog::record('maintenance_schedule_deleted', $schedule, $schedule->toArray(), []);
        $schedule->delete();

        return back()->with('success', 'Schedule deleted.');
    }

    public function toggleActive(MaintenanceSchedule $schedule)
    {
        $this->authorize('update', $schedule);

        $schedule->update(['is_active' => !$schedule->is_active]);

        return back()->with('success', $schedule->is_active ? 'Schedule activated.' : 'Schedule paused.');
    }
}
