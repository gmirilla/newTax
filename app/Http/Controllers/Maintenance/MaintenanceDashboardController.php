<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceAsset;
use App\Models\MaintenanceBreakdown;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceWorkOrder;
use Illuminate\Http\Request;

class MaintenanceDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $openWorkOrders = MaintenanceWorkOrder::whereNotIn('status', [
            MaintenanceWorkOrder::STATUS_CLOSED,
            MaintenanceWorkOrder::STATUS_COMPLETED,
        ])->count();

        $overdueCount = MaintenanceWorkOrder::whereIn('status', [
            MaintenanceWorkOrder::STATUS_OPEN,
            MaintenanceWorkOrder::STATUS_ASSIGNED,
        ])
        ->whereNotNull('scheduled_date')
        ->where('scheduled_date', '<', now()->toDateString())
        ->count();

        $breakdownAssets = MaintenanceAsset::where('status', MaintenanceAsset::STATUS_BREAKDOWN)
            ->count();

        $monthStart = now()->startOfMonth()->toDateString();
        $monthCost  = MaintenanceCost::whereHas('workOrder', function ($q) use ($monthStart) {
            $q->where('closed_at', '>=', $monthStart);
        })->sum('total_cost');

        $recentWorkOrders = MaintenanceWorkOrder::with(['asset', 'assignee'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $openBreakdowns = MaintenanceBreakdown::with('asset')
            ->where('status', MaintenanceBreakdown::STATUS_OPEN)
            ->orderByDesc('downtime_start')
            ->limit(5)
            ->get();

        // Monthly cost trend (last 6 months) — DB-agnostic: group in PHP
        $cutoff = now()->subMonths(6)->startOfMonth();
        $costTrend = MaintenanceCost::withoutGlobalScope('tenant')
            ->where('maintenance_costs.tenant_id', $tenant->id)
            ->join('maintenance_work_orders', 'maintenance_work_orders.id', '=', 'maintenance_costs.work_order_id')
            ->whereNotNull('maintenance_work_orders.closed_at')
            ->where('maintenance_work_orders.closed_at', '>=', $cutoff)
            ->select('maintenance_costs.total_cost', 'maintenance_work_orders.closed_at')
            ->get()
            ->groupBy(fn($r) => \Carbon\Carbon::parse($r->closed_at)->format('Y-m'))
            ->map(fn($group, $key) => (object) [
                'month'       => \Carbon\Carbon::parse($group->first()->closed_at)->format('M Y'),
                'total'       => $group->sum('total_cost'),
                'month_start' => $key,
            ])
            ->sortKeys()
            ->values();

        // Top 5 assets by cost
        $topAssets = MaintenanceCost::withoutGlobalScope('tenant')
            ->where('maintenance_costs.tenant_id', $tenant->id)
            ->join('maintenance_assets', 'maintenance_assets.id', '=', 'maintenance_costs.asset_id')
            ->selectRaw('maintenance_assets.asset_name, maintenance_assets.asset_code, SUM(maintenance_costs.total_cost) as total_cost, COUNT(*) as wo_count')
            ->groupBy('maintenance_assets.id', 'maintenance_assets.asset_name', 'maintenance_assets.asset_code')
            ->orderByDesc('total_cost')
            ->limit(5)
            ->get();

        return view('maintenance.dashboard', compact(
            'openWorkOrders', 'overdueCount', 'breakdownAssets', 'monthCost',
            'recentWorkOrders', 'openBreakdowns', 'costTrend', 'topAssets'
        ));
    }
}
