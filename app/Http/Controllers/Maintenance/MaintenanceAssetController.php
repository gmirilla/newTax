<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MaintenanceAsset;
use App\Models\MaintenanceAssetCategory;
use App\Models\User;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;

class MaintenanceAssetController extends Controller
{
    public function __construct(private MaintenanceService $service) {}

    public function index(Request $request)
    {
        $query = MaintenanceAsset::with('category')
            ->orderBy('asset_name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('asset_name', db_like(), "%{$search}%")
                  ->orWhere('asset_code', db_like(), "%{$search}%")
                  ->orWhere('serial_number', db_like(), "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $assets     = $query->paginate(20)->withQueryString();
        $categories = MaintenanceAssetCategory::where('is_active', true)->orderBy('name')->get();
        $statuses   = MaintenanceAsset::STATUSES;

        return view('maintenance.assets.index', compact('assets', 'categories', 'statuses'));
    }

    public function create()
    {
        $categories = MaintenanceAssetCategory::where('is_active', true)->orderBy('name')->get();
        $operators  = User::where('is_active', true)->orderBy('name')->get();
        return view('maintenance.assets.create', compact('categories', 'operators'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', MaintenanceAsset::class);

        $data = $request->validate([
            'asset_name'                => 'required|string|max:150',
            'category_id'               => 'nullable|exists:maintenance_asset_categories,id',
            'serial_number'             => 'nullable|string|max:100',
            'manufacturer'              => 'nullable|string|max:100',
            'model'                     => 'nullable|string|max:100',
            'purchase_date'             => 'nullable|date',
            'warranty_expiry'           => 'nullable|date',
            'location'                  => 'nullable|string|max:150',
            'assigned_operator_id'      => 'nullable|exists:users,id',
            'maintenance_interval_days' => 'nullable|integer|min:1',
            'status'                    => 'required|in:active,running,under_maintenance,breakdown,retired',
            'notes'                     => 'nullable|string|max:2000',
        ]);

        $tenant = auth()->user()->tenant;

        $asset = MaintenanceAsset::create(array_merge($data, [
            'tenant_id'  => $tenant->id,
            'asset_code' => $this->service->nextAssetCode($tenant),
            'created_by' => auth()->id(),
        ]));

        AuditLog::record('maintenance_asset_created', $asset, [], $asset->toArray());

        return redirect()->route('maintenance.assets.show', $asset)
            ->with('success', "Asset {$asset->asset_code} created successfully.");
    }

    public function show(MaintenanceAsset $asset)
    {
        $this->authorize('view', $asset);

        $asset->load(['category', 'assignedOperator', 'schedules', 'workOrders.assignee', 'breakdowns']);

        $openWorkOrders    = $asset->workOrders->whereNotIn('status', ['closed', 'completed']);
        $recentWorkOrders  = $asset->workOrders->sortByDesc('created_at')->take(10);
        $totalCost         = $asset->workOrders
            ->join('maintenance_costs')
            ->sum('total_cost');

        $costRecord = \App\Models\MaintenanceCost::withoutGlobalScope('tenant')
            ->where('asset_id', $asset->id)
            ->selectRaw('SUM(total_cost) as total, SUM(labor_cost) as labor, SUM(parts_cost) as parts')
            ->first();

        return view('maintenance.assets.show', compact(
            'asset', 'openWorkOrders', 'recentWorkOrders', 'costRecord'
        ));
    }

    public function edit(MaintenanceAsset $asset)
    {
        $this->authorize('update', $asset);

        $categories = MaintenanceAssetCategory::where('is_active', true)->orderBy('name')->get();
        $operators  = User::where('is_active', true)->orderBy('name')->get();

        return view('maintenance.assets.edit', compact('asset', 'categories', 'operators'));
    }

    public function update(Request $request, MaintenanceAsset $asset)
    {
        $this->authorize('update', $asset);

        $data = $request->validate([
            'asset_name'                => 'required|string|max:150',
            'category_id'               => 'nullable|exists:maintenance_asset_categories,id',
            'serial_number'             => 'nullable|string|max:100',
            'manufacturer'              => 'nullable|string|max:100',
            'model'                     => 'nullable|string|max:100',
            'purchase_date'             => 'nullable|date',
            'warranty_expiry'           => 'nullable|date',
            'location'                  => 'nullable|string|max:150',
            'assigned_operator_id'      => 'nullable|exists:users,id',
            'maintenance_interval_days' => 'nullable|integer|min:1',
            'status'                    => 'required|in:active,running,under_maintenance,breakdown,retired',
            'notes'                     => 'nullable|string|max:2000',
        ]);

        $old = $asset->toArray();
        $asset->update($data);

        AuditLog::record('maintenance_asset_updated', $asset, $old, $asset->fresh()->toArray());

        return redirect()->route('maintenance.assets.show', $asset)
            ->with('success', 'Asset updated successfully.');
    }

    public function destroy(MaintenanceAsset $asset)
    {
        $this->authorize('delete', $asset);

        if ($asset->workOrders()->whereNotIn('status', ['closed'])->exists()) {
            return back()->with('error', 'Cannot delete an asset with open work orders.');
        }

        AuditLog::record('maintenance_asset_deleted', $asset, $asset->toArray(), []);
        $asset->delete();

        return redirect()->route('maintenance.assets.index')
            ->with('success', 'Asset deleted.');
    }
}
