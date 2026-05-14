<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\RestockRequest;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\RestockRequestedNotification;
use App\Notifications\RestockStatusNotification;
use App\Services\BookkeepingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RestockRequestController extends Controller
{
    public function __construct(private readonly BookkeepingService $bookkeeping) {}

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $tenant = auth()->user()->tenant;

        $query = RestockRequest::where('restock_requests.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->with(['item', 'requester', 'approver'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn($q) => $q->whereHas('item', function ($q) use ($request) {
                $q->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('sku',  'ilike', '%' . $request->search . '%');
            }))
            ->when($request->filled('from'), fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'),   fn($q) => $q->whereDate('created_at', '<=', $request->to));

        $requests = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $stats = RestockRequest::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->selectRaw("
                SUM(CASE WHEN status = 'pending'  THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) AS received
            ")
            ->first();

        return view('inventory.restock.index', compact('requests', 'stats'));
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    public function create(Request $request): View
    {
        $this->authorize('create', RestockRequest::class);

        $tenant = auth()->user()->tenant;

        $items = InventoryItem::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'cost_price', 'current_stock', 'restock_level']);

        $preselectedItemId = $request->integer('item_id');

        return view('inventory.restock.create', compact('items', 'preselectedItemId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', RestockRequest::class);

        $validated = $request->validate([
            'item_id'           => ['required', 'integer', 'exists:inventory_items,id'],
            'quantity_requested'=> ['required', 'numeric', 'min:0.001'],
            'unit_cost'         => ['required', 'numeric', 'min:0'],
            'supplier_name'     => ['nullable', 'string', 'max:150'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        $tenant = auth()->user()->tenant;

        $restockRequest = DB::transaction(function () use ($validated, $tenant) {
            $rr = RestockRequest::create([
                'tenant_id'          => $tenant->id,
                'item_id'            => $validated['item_id'],
                'request_number'     => $this->generateRequestNumber($tenant->id),
                'quantity_requested' => $validated['quantity_requested'],
                'unit_cost'          => $validated['unit_cost'],
                'supplier_name'      => $validated['supplier_name'] ?? null,
                'notes'              => $validated['notes'] ?? null,
                'status'             => RestockRequest::STATUS_PENDING,
                'requested_by'       => auth()->id(),
            ]);

            return $rr;
        });

        // Notify accountants/admins (outside transaction, queued)
        $restockRequest->load('item', 'requester');
        $recipients = User::where('tenant_id', $tenant->id)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_ACCOUNTANT])
            ->where('is_active', true)
            ->where('id', '!=', auth()->id())
            ->get();

        foreach ($recipients as $user) {
            $user->notify(new RestockRequestedNotification($restockRequest));
        }

        return redirect()->route('inventory.restock.show', $restockRequest)
            ->with('success', "Restock request {$restockRequest->request_number} submitted.");
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(RestockRequest $restockRequest): View
    {
        $this->authorize('view', $restockRequest);

        $restockRequest->load(['item.category', 'requester', 'approver', 'invoice']);

        return view('inventory.restock.show', compact('restockRequest'));
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function approve(Request $request, RestockRequest $restockRequest): RedirectResponse
    {
        $this->authorize('approve', $restockRequest);

        $restockRequest->update([
            'status'      => RestockRequest::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Notify the requester
        $restockRequest->load('item', 'requester');
        optional($restockRequest->requester)->notify(
            new RestockStatusNotification($restockRequest, 'approved')
        );

        return back()->with('success', "Request {$restockRequest->request_number} approved.");
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function reject(Request $request, RestockRequest $restockRequest): RedirectResponse
    {
        $this->authorize('reject', $restockRequest);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $restockRequest->update([
            'status'           => RestockRequest::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $restockRequest->load('item', 'requester');
        optional($restockRequest->requester)->notify(
            new RestockStatusNotification($restockRequest, 'rejected')
        );

        return back()->with('success', "Request {$restockRequest->request_number} rejected.");
    }

    // ── Receive ───────────────────────────────────────────────────────────────

    public function receive(Request $request, RestockRequest $restockRequest): RedirectResponse
    {
        $this->authorize('receive', $restockRequest);

        $validated = $request->validate([
            'quantity_received'   => ['required', 'numeric', 'min:0.001'],
            'unit_cost'           => ['required', 'numeric', 'min:0'],
            'supplier_invoice_no' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($validated, $restockRequest) {
            $item     = $restockRequest->item;
            $qtyIn    = (float) $validated['quantity_received'];
            $unitCost = (float) $validated['unit_cost'];
            $tenant   = $restockRequest->tenant;

            // 1. Weighted average cost
            $newAvgCost  = $item->recalculateAvgCost($qtyIn, $unitCost);
            $newStock    = round((float) $item->current_stock + $qtyIn, 3);

            // 2. Stock movement
            StockMovement::create([
                'tenant_id'       => $restockRequest->tenant_id,
                'item_id'         => $item->id,
                'type'            => 'restock',
                'quantity'        => $qtyIn,
                'unit_cost'       => $unitCost,
                'running_balance' => $newStock,
                'reference_type'  => RestockRequest::class,
                'reference_id'    => $restockRequest->id,
                'notes'           => "Restock: {$restockRequest->request_number}",
                'created_by'      => auth()->id(),
            ]);

            // 3. Update item stock + avg_cost (observer will clear resolved alerts)
            $item->update([
                'current_stock' => $newStock,
                'avg_cost'      => $newAvgCost,
            ]);

            // 4. Supplier bill
            $totalCost   = round($qtyIn * $unitCost, 2);
            $billNumber  = $this->generateBillNumber($restockRequest->tenant_id);

            $bill = Invoice::create([
                'tenant_id'      => $restockRequest->tenant_id,
                'customer_id'    => null,
                'invoice_number' => $billNumber,
                'reference'      => $validated['supplier_invoice_no'] ?? null,
                'invoice_date'   => now()->toDateString(),
                'due_date'       => now()->addDays(30)->toDateString(),
                'subtotal'       => $totalCost,
                'vat_amount'     => 0,
                'discount_amount'=> 0,
                'total_amount'   => $totalCost,
                'amount_paid'    => 0,
                'balance_due'    => $totalCost,
                'vat_applicable' => false,
                'status'         => 'sent',
                'is_b2c'         => false,
                'currency'       => 'NGN',
                'notes'          => "Restock: {$restockRequest->request_number}" .
                                    ($restockRequest->supplier_name ? " | Supplier: {$restockRequest->supplier_name}" : ''),
                'created_by'     => auth()->id(),
            ]);

            $bill->items()->create([
                'description'    => "Stock received: {$item->name}" .
                                    ($item->sku ? " (SKU: {$item->sku})" : ''),
                'quantity'       => $qtyIn,
                'unit_price'     => $unitCost,
                'subtotal'       => $totalCost,
                'vat_applicable' => false,
                'vat_rate'       => 0,
                'vat_amount'     => 0,
                'total'          => $totalCost,
                'account_code'   => '1200',
                'sort_order'     => 1,
            ]);

            // 5. GL entries: Dr Inventory 1200, Cr AP 2001
            $accounts = Account::where('tenant_id', $restockRequest->tenant_id)
                ->withoutGlobalScope('tenant')
                ->whereIn('code', ['1200', '2001'])
                ->pluck('id', 'code');

            $missing = array_diff(['1200', '2001'], $accounts->keys()->toArray());
            if (! empty($missing)) {
                throw new \RuntimeException(
                    'GL accounts missing from Chart of Accounts: ' . implode(', ', $missing)
                    . '. Add them under Accounts before receiving stock.'
                );
            }

            $transaction = $this->bookkeeping->postJournalEntry(
                $tenant,
                [
                    'reference'        => $bill->invoice_number,
                    'transaction_date' => now()->toDateString(),
                    'type'             => 'purchase',
                    'description'      => "Stock received: {$restockRequest->request_number} — {$item->name}",
                ],
                [
                    [
                        'account_id'  => $accounts['1200'],
                        'entry_type'  => 'debit',
                        'amount'      => $totalCost,
                        'description' => "Inventory in: {$restockRequest->request_number}",
                    ],
                    [
                        'account_id'  => $accounts['2001'],
                        'entry_type'  => 'credit',
                        'amount'      => $totalCost,
                        'description' => "AP — {$restockRequest->supplier_name}: {$restockRequest->request_number}",
                    ],
                ]
            );

            // Link GL transaction to the bill so it's excluded from AR/liability supplements
            $bill->update(['transaction_id' => $transaction->id]);

            // 6. Finalise the request
            $restockRequest->update([
                'status'              => RestockRequest::STATUS_RECEIVED,
                'quantity_received'   => $qtyIn,
                'unit_cost'           => $unitCost,
                'supplier_invoice_no' => $validated['supplier_invoice_no'] ?? $restockRequest->supplier_invoice_no,
                'received_at'         => now(),
                'invoice_id'          => $bill->id,
            ]);
        });

        // Notify requester of receipt
        $restockRequest->refresh()->load('item', 'requester');
        optional($restockRequest->requester)->notify(
            new RestockStatusNotification($restockRequest, 'received')
        );

        return redirect()->route('inventory.restock.show', $restockRequest)
            ->with('success', "Goods received. Stock updated and supplier bill generated.");
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function cancel(RestockRequest $restockRequest): RedirectResponse
    {
        $this->authorize('cancel', $restockRequest);

        $restockRequest->update(['status' => RestockRequest::STATUS_CANCELLED]);

        return redirect()->route('inventory.restock.index')
            ->with('success', "Request {$restockRequest->request_number} cancelled.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function generateRequestNumber(int $tenantId): string
    {
        $prefix = 'RST-' . now()->format('Ym') . '-';

        $last = RestockRequest::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->where('request_number', 'like', $prefix . '%')
            ->orderBy('request_number', 'desc')
            ->lockForUpdate()
            ->first();

        $next = $last ? ((int) substr($last->request_number, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function generateBillNumber(int $tenantId): string
    {
        $prefix = 'BILL-' . now()->format('Ym') . '-';

        $last = Invoice::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->lockForUpdate()
            ->first();

        $next = $last ? ((int) substr($last->invoice_number, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
