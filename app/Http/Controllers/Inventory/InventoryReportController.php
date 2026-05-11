<?php

namespace App\Http\Controllers\Inventory;

use App\Exports\Inventory\LowStockExport;
use App\Exports\Inventory\RestockHistoryExport;
use App\Exports\Inventory\SalesByItemExport;
use App\Exports\Inventory\SalesByPeriodExport;
use App\Exports\Inventory\StockMovementsExport;
use App\Exports\Inventory\StockValuationExport;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\RestockRequest;
use App\Models\SaleOrderItem;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReportController extends Controller
{
    // ── Stock Valuation ───────────────────────────────────────────────────────

    public function stockValuation(Request $request): View
    {
        [$tenant, $items, $totals] = $this->stockValuationData($request);
        return view('inventory.reports.stock-valuation', compact('items', 'totals', 'tenant'));
    }

    public function stockValuationPdf(Request $request)
    {
        [$tenant, $items, $totals] = $this->stockValuationData($request);
        $pdf = Pdf::loadView('inventory.reports.pdf.stock-valuation', compact('items', 'totals', 'tenant'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('Stock_Valuation_' . now()->format('Y-m-d') . '.pdf');
    }

    public function stockValuationExcel(Request $request)
    {
        [$tenant, $items, $totals] = $this->stockValuationData($request);
        return Excel::download(
            new StockValuationExport($items, $totals, $tenant),
            'Stock_Valuation_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function stockValuationData(Request $request): array
    {
        $tenant = $request->user()->tenant;

        $items = InventoryItem::where('inventory_items.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                $item->stock_value       = round((float) $item->current_stock * (float) $item->avg_cost, 2);
                $item->potential_revenue = round((float) $item->current_stock * (float) $item->selling_price, 2);
                return $item;
            });

        $totals = [
            'total_stock_value'       => $items->sum('stock_value'),
            'total_potential_revenue' => $items->sum('potential_revenue'),
            'total_items'             => $items->count(),
        ];

        return [$tenant, $items, $totals];
    }

    // ── Low Stock ─────────────────────────────────────────────────────────────

    public function lowStock(Request $request): View
    {
        [$tenant, $items] = $this->lowStockData($request);
        return view('inventory.reports.low-stock', compact('items', 'tenant'));
    }

    public function lowStockPdf(Request $request)
    {
        [$tenant, $items] = $this->lowStockData($request);
        $pdf = Pdf::loadView('inventory.reports.pdf.low-stock', compact('items', 'tenant'))
            ->setPaper('a4', 'portrait');
        return $pdf->download('Low_Stock_' . now()->format('Y-m-d') . '.pdf');
    }

    public function lowStockExcel(Request $request)
    {
        [$tenant, $items] = $this->lowStockData($request);
        return Excel::download(
            new LowStockExport($items, $tenant),
            'Low_Stock_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function lowStockData(Request $request): array
    {
        $tenant = $request->user()->tenant;

        $items = InventoryItem::where('inventory_items.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->with('category')
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'restock_level')
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                $item->shortfall = max(0, (float) $item->restock_level - (float) $item->current_stock);

                $lastRestock = StockMovement::where('item_id', $item->id)
                    ->where('type', 'restock')
                    ->latest('created_at')
                    ->first();
                $item->last_restocked = $lastRestock?->created_at;

                return $item;
            });

        return [$tenant, $items];
    }

    // ── Stock Movements ───────────────────────────────────────────────────────

    public function movements(Request $request): View
    {
        [$tenant, $movements, $filters] = $this->movementsData($request);
        $items = InventoryItem::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
        return view('inventory.reports.movements', compact('movements', 'filters', 'items', 'tenant'));
    }

    public function movementsPdf(Request $request)
    {
        [$tenant, $movements, $filters] = $this->movementsData($request);
        $pdf = Pdf::loadView('inventory.reports.pdf.movements', compact('movements', 'filters', 'tenant'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('Stock_Movements_' . now()->format('Y-m-d') . '.pdf');
    }

    public function movementsExcel(Request $request)
    {
        [$tenant, $movements, $filters] = $this->movementsData($request);
        return Excel::download(
            new StockMovementsExport($movements, $filters, $tenant),
            'Stock_Movements_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function movementsData(Request $request): array
    {
        $tenant = $request->user()->tenant;

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        $filters = [
            'from'    => $from->toDateString(),
            'to'      => $to->toDateString(),
            'item_id' => $request->input('item_id'),
            'type'    => $request->input('type'),
        ];

        $movements = StockMovement::where('stock_movements.tenant_id', $tenant->id)
            ->with(['item', 'creator'])
            ->whereBetween('stock_movements.created_at', [$from, $to])
            ->when($filters['item_id'], fn($q) => $q->where('item_id', $filters['item_id']))
            ->when($filters['type'],    fn($q) => $q->where('type',    $filters['type']))
            ->orderByDesc('stock_movements.created_at')
            ->get();

        return [$tenant, $movements, $filters];
    }

    // ── Sales by Item ─────────────────────────────────────────────────────────

    public function salesByItem(Request $request): View
    {
        [$tenant, $rows, $totals, $filters] = $this->salesByItemData($request);
        return view('inventory.reports.sales-by-item', compact('rows', 'totals', 'filters', 'tenant'));
    }

    public function salesByItemPdf(Request $request)
    {
        [$tenant, $rows, $totals, $filters] = $this->salesByItemData($request);
        $pdf = Pdf::loadView('inventory.reports.pdf.sales-by-item', compact('rows', 'totals', 'filters', 'tenant'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('Sales_By_Item_' . now()->format('Y-m-d') . '.pdf');
    }

    public function salesByItemExcel(Request $request)
    {
        [$tenant, $rows, $totals, $filters] = $this->salesByItemData($request);
        return Excel::download(
            new SalesByItemExport($rows, $totals, $filters, $tenant),
            'Sales_By_Item_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function salesByItemData(Request $request): array
    {
        $tenant = $request->user()->tenant;

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        $filters = ['from' => $from->toDateString(), 'to' => $to->toDateString()];

        $rows = SaleOrderItem::select(
                'sale_order_items.item_id',
                DB::raw('SUM(sale_order_items.quantity) AS units_sold'),
                DB::raw('SUM(sale_order_items.total)    AS revenue'),
                DB::raw('SUM(sale_order_items.quantity * sale_order_items.cost_price_at_sale) AS cogs')
            )
            ->join('sales_orders', 'sales_orders.id', '=', 'sale_order_items.sale_order_id')
            ->where('sales_orders.tenant_id', $tenant->id)
            ->where('sales_orders.status', SalesOrder::STATUS_CONFIRMED)
            ->whereBetween('sales_orders.sale_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('sale_order_items.item_id')
            ->groupBy('sale_order_items.item_id')
            ->with('item')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $row->gross_profit = (float) $row->revenue - (float) $row->cogs;
                $row->margin_pct   = $row->revenue > 0
                    ? round($row->gross_profit / (float) $row->revenue * 100, 1)
                    : 0;
                return $row;
            });

        $totals = [
            'units_sold'   => $rows->sum('units_sold'),
            'revenue'      => $rows->sum('revenue'),
            'cogs'         => $rows->sum('cogs'),
            'gross_profit' => $rows->sum('gross_profit'),
        ];
        $totals['margin_pct'] = $totals['revenue'] > 0
            ? round($totals['gross_profit'] / $totals['revenue'] * 100, 1)
            : 0;

        return [$tenant, $rows, $totals, $filters];
    }

    // ── Sales by Period ───────────────────────────────────────────────────────

    public function salesByPeriod(Request $request): View
    {
        [$tenant, $rows, $totals, $filters] = $this->salesByPeriodData($request);
        return view('inventory.reports.sales-by-period', compact('rows', 'totals', 'filters', 'tenant'));
    }

    public function salesByPeriodPdf(Request $request)
    {
        [$tenant, $rows, $totals, $filters] = $this->salesByPeriodData($request);
        $pdf = Pdf::loadView('inventory.reports.pdf.sales-by-period', compact('rows', 'totals', 'filters', 'tenant'))
            ->setPaper('a4', 'portrait');
        return $pdf->download('Sales_By_Period_' . now()->format('Y-m-d') . '.pdf');
    }

    public function salesByPeriodExcel(Request $request)
    {
        [$tenant, $rows, $totals, $filters] = $this->salesByPeriodData($request);
        return Excel::download(
            new SalesByPeriodExport($rows, $totals, $filters, $tenant),
            'Sales_By_Period_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function salesByPeriodData(Request $request): array
    {
        $tenant = $request->user()->tenant;

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        $groupBy = in_array($request->input('group_by'), ['day', 'week', 'month'])
            ? $request->input('group_by')
            : 'day';

        $filters = ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'group_by' => $groupBy];

        $periodExpr = match ($groupBy) {
            'month' => "TO_CHAR(sale_date, 'YYYY-MM')",
            'week'  => "TO_CHAR(DATE_TRUNC('week', sale_date), 'YYYY-MM-DD')",
            default => "TO_CHAR(sale_date, 'YYYY-MM-DD')",
        };

        $rows = SalesOrder::select(
                DB::raw("{$periodExpr} AS period"),
                DB::raw('COUNT(*)           AS orders'),
                DB::raw('SUM(total_amount)  AS revenue'),
            )
            ->where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('status', SalesOrder::STATUS_CONFIRMED)
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy(DB::raw($periodExpr))
            ->orderBy(DB::raw($periodExpr))
            ->get();

        // Attach COGS per period from sale_order_items
        $cogsByPeriod = SaleOrderItem::select(
                DB::raw("{$periodExpr} AS period"),
                DB::raw('SUM(sale_order_items.quantity * sale_order_items.cost_price_at_sale) AS cogs')
            )
            ->join('sales_orders', 'sales_orders.id', '=', 'sale_order_items.sale_order_id')
            ->where('sales_orders.tenant_id', $tenant->id)
            ->where('sales_orders.status', SalesOrder::STATUS_CONFIRMED)
            ->whereBetween('sales_orders.sale_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy(DB::raw($periodExpr))
            ->pluck('cogs', 'period');

        $rows = $rows->map(function ($row) use ($cogsByPeriod) {
            $row->cogs         = (float) ($cogsByPeriod[$row->period] ?? 0);
            $row->gross_profit = (float) $row->revenue - $row->cogs;
            return $row;
        });

        $totals = [
            'orders'       => $rows->sum('orders'),
            'revenue'      => $rows->sum('revenue'),
            'cogs'         => $rows->sum('cogs'),
            'gross_profit' => $rows->sum('gross_profit'),
        ];

        return [$tenant, $rows, $totals, $filters];
    }

    // ── Restock History ───────────────────────────────────────────────────────

    public function restockHistory(Request $request): View
    {
        [$tenant, $requests, $filters] = $this->restockHistoryData($request);
        return view('inventory.reports.restock-history', compact('requests', 'filters', 'tenant'));
    }

    public function restockHistoryPdf(Request $request)
    {
        [$tenant, $requests, $filters] = $this->restockHistoryData($request);
        $pdf = Pdf::loadView('inventory.reports.pdf.restock-history', compact('requests', 'filters', 'tenant'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('Restock_History_' . now()->format('Y-m-d') . '.pdf');
    }

    public function restockHistoryExcel(Request $request)
    {
        [$tenant, $requests, $filters] = $this->restockHistoryData($request);
        return Excel::download(
            new RestockHistoryExport($requests, $filters, $tenant),
            'Restock_History_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function restockHistoryData(Request $request): array
    {
        $tenant = $request->user()->tenant;

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        $filters = [
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
            'status' => $request->input('status'),
        ];

        $requests = RestockRequest::where('restock_requests.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->with(['item', 'requester', 'approver'])
            ->whereBetween('restock_requests.created_at', [$from, $to])
            ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
            ->orderByDesc('restock_requests.created_at')
            ->get();

        return [$tenant, $requests, $filters];
    }
}
