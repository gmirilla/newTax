<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierBillController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = auth()->user()->tenant;

        $query = Invoice::where('invoices.tenant_id', $tenant->id)
            ->whereNull('customer_id')
            ->where('invoice_number', 'like', 'BILL-%')
            ->with(['restockRequest.item', 'payments'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn($q) => $q->where(function ($q) use ($request) {
                $q->where('invoice_number', 'ilike', '%' . $request->search . '%')
                  ->orWhere('notes', 'ilike', '%' . $request->search . '%');
            }))
            ->when($request->filled('from'), fn($q) => $q->whereDate('invoice_date', '>=', $request->from))
            ->when($request->filled('to'),   fn($q) => $q->whereDate('invoice_date', '<=', $request->to));

        $bills = $query->orderByDesc('invoice_date')->paginate(25)->withQueryString();

        $stats = Invoice::where('tenant_id', $tenant->id)
            ->whereNull('customer_id')
            ->where('invoice_number', 'like', 'BILL-%')
            ->selectRaw("
                COUNT(*) FILTER (WHERE status IN ('sent','partial')) AS outstanding_count,
                COALESCE(SUM(balance_due) FILTER (WHERE status IN ('sent','partial')), 0) AS outstanding_value,
                COUNT(*) FILTER (WHERE status IN ('sent','partial') AND due_date < CURRENT_DATE) AS overdue_count,
                COALESCE(SUM(balance_due) FILTER (WHERE status IN ('sent','partial') AND due_date < CURRENT_DATE), 0) AS overdue_value
            ")
            ->first();

        $bankAccounts = BankAccount::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.bills.index', compact('bills', 'stats', 'bankAccounts'));
    }
}
