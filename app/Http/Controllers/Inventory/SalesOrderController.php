<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\SaleOrderItem;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Services\BookkeepingService;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly BookkeepingService $bookkeeping,
        private readonly InvoiceService     $invoiceService,
    ) {}

    // ── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $tenant = auth()->user()->tenant;

        $query = SalesOrder::where('sales_orders.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->with(['customer', 'creator'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn($q) => $q->whereDate('sale_date', '>=', $request->from))
            ->when($request->filled('to'),   fn($q) => $q->whereDate('sale_date', '<=', $request->to))
            ->when($request->filled('search'), fn($q) => $q->where(function ($q) use ($request) {
                $q->where('order_number', 'ilike', '%'.$request->search.'%')
                  ->orWhere('customer_name', 'ilike', '%'.$request->search.'%');
            }));

        $orders = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $stats = SalesOrder::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->selectRaw("
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
                SUM(CASE WHEN status = 'draft'     THEN 1 ELSE 0 END) AS drafts,
                COALESCE(SUM(CASE WHEN status = 'confirmed' THEN total_amount ELSE 0 END), 0) AS total_revenue
            ")
            ->first();

        return view('inventory.sales.index', compact('orders', 'stats'));
    }

    // ── Create / Store (Draft) ────────────────────────────────────────────────

    public function create(Request $request): View
    {
        $tenant = auth()->user()->tenant;

        $inventoryItems = InventoryItem::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'selling_price', 'avg_cost', 'current_stock', 'restock_level']);

        $customers = Customer::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $order = null;

        // Pre-select item if coming from item detail page
        $preselectedItemId = $request->integer('item_id');

        return view('inventory.sales.create', compact('inventoryItems', 'customers', 'order', 'preselectedItemId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOrderForm($request);
        $tenant    = auth()->user()->tenant;

        $order = null;
        DB::transaction(function () use ($validated, $tenant, &$order) {
            $order = SalesOrder::create([
                'tenant_id'         => $tenant->id,
                'order_number'      => $this->generateOrderNumber($tenant->id),
                'customer_id'       => $validated['customer_id'] ?? null,
                'customer_name'     => $validated['customer_name'] ?? null,
                'sale_date'         => $validated['sale_date'],
                'payment_method'    => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'discount_amount'   => $validated['discount_amount'] ?? 0,
                'notes'             => $validated['notes'] ?? null,
                'status'            => SalesOrder::STATUS_DRAFT,
                'created_by'        => auth()->id(),
            ]);

            $this->syncLineItems($order, $validated['items']);
            $order->recalculateTotals();
        });

        if ($request->has('confirm')) {
            return $this->runConfirm($order);
        }

        return redirect()->route('inventory.sales.show', $order)
            ->with('success', "Draft order {$order->order_number} created.");
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function show(SalesOrder $salesOrder): View
    {
        $this->authorize('view', $salesOrder);

        $salesOrder->load(['items.item', 'customer', 'invoice', 'transaction', 'creator']);

        $bankAccounts = BankAccount::withoutGlobalScope('tenant')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'bank_name', 'is_default']);

        return view('inventory.sales.show', compact('salesOrder', 'bankAccounts'));
    }

    // ── Edit / Update (Draft only) ────────────────────────────────────────────

    public function edit(SalesOrder $salesOrder): View
    {
        $this->authorize('update', $salesOrder);

        $tenant = $salesOrder->tenant;

        $inventoryItems = InventoryItem::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'selling_price', 'avg_cost', 'current_stock', 'restock_level']);

        $customers = Customer::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $order = $salesOrder->load('items.item');
        $preselectedItemId = 0;

        return view('inventory.sales.create', compact('inventoryItems', 'customers', 'order', 'preselectedItemId'));
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('update', $salesOrder);

        $validated = $this->validateOrderForm($request);

        DB::transaction(function () use ($validated, $salesOrder) {
            $salesOrder->update([
                'customer_id'       => $validated['customer_id'] ?? null,
                'customer_name'     => $validated['customer_name'] ?? null,
                'sale_date'         => $validated['sale_date'],
                'payment_method'    => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'discount_amount'   => $validated['discount_amount'] ?? 0,
                'notes'             => $validated['notes'] ?? null,
            ]);

            $salesOrder->items()->delete();
            $this->syncLineItems($salesOrder, $validated['items']);
            $salesOrder->recalculateTotals();
        });

        if ($request->has('confirm')) {
            return $this->runConfirm($salesOrder);
        }

        return redirect()->route('inventory.sales.show', $salesOrder)
            ->with('success', 'Order updated.');
    }

    // ── Confirm ───────────────────────────────────────────────────────────────

    public function confirm(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('confirm', $salesOrder);

        if (! $salesOrder->canBeConfirmed()) {
            return back()->with('error', 'Order cannot be confirmed in its current state.');
        }

        // Optionally record which bank account received the payment
        if ($request->filled('bank_account_id')) {
            $request->validate([
                'bank_account_id' => ['integer', Rule::exists('bank_accounts', 'id')
                    ->where('tenant_id', auth()->user()->tenant_id)],
            ]);
            $salesOrder->update(['bank_account_id' => $request->bank_account_id]);
        }

        return $this->runConfirm($salesOrder);
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function cancel(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('cancel', $salesOrder);

        if (! $salesOrder->canBeCancelled()) {
            return back()->with('error', 'Order cannot be cancelled.');
        }

        DB::transaction(function () use ($salesOrder) {
            if ($salesOrder->status === SalesOrder::STATUS_CONFIRMED) {
                $this->reverseConfirmedOrder($salesOrder);
            }

            $salesOrder->update(['status' => SalesOrder::STATUS_CANCELLED]);
        });

        return redirect()->route('inventory.sales.index')
            ->with('success', "Order {$salesOrder->order_number} cancelled.");
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function runConfirm(SalesOrder $salesOrder): RedirectResponse
    {
        try {
            DB::transaction(function () use ($salesOrder) {
                $salesOrder->load('items.item');
                $tenant = $salesOrder->tenant;

                // 1. Validate stock availability
                foreach ($salesOrder->items as $line) {
                    if ((float) $line->item->current_stock < (float) $line->quantity) {
                        throw ValidationException::withMessages([
                            'stock' => "Insufficient stock for \"{$line->item->name}\": "
                                . number_format($line->item->current_stock, 3) . " {$line->item->unit} available, "
                                . number_format($line->quantity, 3) . ' requested.',
                        ]);
                    }
                }

                // 2. Write stock movements and decrement stock
                $totalCOGS = 0;
                foreach ($salesOrder->items as $line) {
                    $avgCost  = (float) $line->item->avg_cost;
                    $newStock = round((float) $line->item->current_stock - (float) $line->quantity, 3);

                    $line->update(['cost_price_at_sale' => $avgCost]);

                    StockMovement::create([
                        'tenant_id'       => $salesOrder->tenant_id,
                        'item_id'         => $line->item_id,
                        'type'            => 'sale',
                        'quantity'        => $line->quantity,
                        'unit_cost'       => $avgCost,
                        'running_balance' => $newStock,
                        'reference_type'  => SalesOrder::class,
                        'reference_id'    => $salesOrder->id,
                        'notes'           => "Sale: {$salesOrder->order_number}",
                        'created_by'      => auth()->id(),
                    ]);

                    $line->item->update(['current_stock' => $newStock]);
                    $totalCOGS += (float) $line->quantity * $avgCost;
                }

                // 3. Create Invoice
                $invoiceNumber = $this->invoiceService->generateInvoiceNumber($tenant);

                $invoice = Invoice::create([
                    'tenant_id'       => $salesOrder->tenant_id,
                    'customer_id'     => $salesOrder->customer_id,
                    'invoice_number'  => $invoiceNumber,
                    'invoice_date'    => $salesOrder->sale_date,
                    'due_date'        => $salesOrder->sale_date,
                    'subtotal'        => $salesOrder->subtotal,
                    'vat_amount'      => $salesOrder->vat_amount,
                    'discount_amount' => $salesOrder->discount_amount,
                    'total_amount'    => $salesOrder->total_amount,
                    'amount_paid'     => $salesOrder->total_amount,
                    'balance_due'     => 0,
                    'vat_applicable'  => $salesOrder->vat_amount > 0,
                    'status'          => 'paid',
                    'is_b2c'          => is_null($salesOrder->customer_id),
                    'currency'        => 'NGN',
                    'notes'           => $salesOrder->notes,
                    'created_by'      => auth()->id(),
                ]);

                foreach ($salesOrder->items->sortBy('sort_order') as $line) {
                    $invoice->items()->create([
                        'description'    => $line->description,
                        'quantity'       => $line->quantity,
                        'unit_price'     => $line->unit_price,
                        'subtotal'       => $line->subtotal,
                        'vat_applicable' => $line->vat_applicable,
                        'vat_rate'       => $line->vat_rate,
                        'vat_amount'     => $line->vat_amount,
                        'total'          => $line->total,
                        'account_code'   => '4001',
                        'sort_order'     => $line->sort_order,
                    ]);
                }
                $invoice->recalculateTotals();

                // 4. Post GL entries
                // Use the selected bank account's GL code; fall back to '1002' for
                // electronic payments and '1001' for cash.
                $bankGlId = null;
                if ($salesOrder->bank_account_id) {
                    $bankGlId = BankAccount::withoutGlobalScope('tenant')
                        ->where('id', $salesOrder->bank_account_id)
                        ->where('tenant_id', $salesOrder->tenant_id)
                        ->value('gl_account_id');
                }

                $isBankPayment = in_array($salesOrder->payment_method, ['bank_transfer', 'pos', 'cheque', 'online']);
                $cashAccountCode = $isBankPayment ? '1002' : '1001';

                $accountCodes = ['1001', '1002', '4001', '2100', '5001', '1200'];
                $accounts = Account::where('tenant_id', $salesOrder->tenant_id)
                    ->withoutGlobalScope('tenant')
                    ->whereIn('code', $accountCodes)
                    ->pluck('id', 'code');

                // Resolve the debit account id: prefer the selected bank account GL,
                // fall back to the code-based lookup.
                $cashAccountId = $bankGlId ?? ($accounts[$cashAccountCode] ?? null);

                // Pre-flight: ensure every required GL account exists
                $required = ['4001', '5001', '1200'];
                if (! $bankGlId) {
                    $required[] = $cashAccountCode;
                }
                if ((float) $salesOrder->vat_amount > 0) {
                    $required[] = '2100';
                }
                $missing = array_diff($required, $accounts->keys()->toArray());
                if (! empty($missing) || ! $cashAccountId) {
                    throw ValidationException::withMessages([
                        'stock' => 'GL accounts missing from Chart of Accounts: ' . implode(', ', $missing)
                            . '. Go to Accounts and add them before confirming.',
                    ]);
                }

                $netSalesRevenue = round(
                    (float) $salesOrder->subtotal - (float) $salesOrder->discount_amount,
                    2
                );

                $entries = [
                    [
                        'account_id'  => $cashAccountId,
                        'entry_type'  => 'debit',
                        'amount'      => (float) $salesOrder->total_amount,
                        'description' => "Cash receipt: {$salesOrder->order_number}",
                    ],
                    [
                        'account_id'  => $accounts['4001'],
                        'entry_type'  => 'credit',
                        'amount'      => $netSalesRevenue,
                        'description' => "Sales revenue: {$salesOrder->order_number}",
                    ],
                ];

                if ((float) $salesOrder->vat_amount > 0 && isset($accounts['2100'])) {
                    $entries[] = [
                        'account_id'  => $accounts['2100'],
                        'entry_type'  => 'credit',
                        'amount'      => (float) $salesOrder->vat_amount,
                        'description' => "VAT collected: {$salesOrder->order_number}",
                    ];
                }

                $totalCOGS = round($totalCOGS, 2);
                if ($totalCOGS > 0) {
                    $entries[] = [
                        'account_id'  => $accounts['5001'],
                        'entry_type'  => 'debit',
                        'amount'      => $totalCOGS,
                        'description' => "COGS: {$salesOrder->order_number}",
                    ];
                    $entries[] = [
                        'account_id'  => $accounts['1200'],
                        'entry_type'  => 'credit',
                        'amount'      => $totalCOGS,
                        'description' => "Inventory reduction: {$salesOrder->order_number}",
                    ];
                }

                $customerName = $salesOrder->customer?->name ?? $salesOrder->customer_name ?? 'Walk-in';
                $transaction = $this->bookkeeping->postJournalEntry(
                    $tenant,
                    [
                        'reference'        => $invoice->invoice_number,
                        'transaction_date' => $salesOrder->sale_date->toDateString(),
                        'type'             => 'sale',
                        'description'      => "Sale: {$salesOrder->order_number} — {$customerName}",
                    ],
                    $entries
                );

                // Link transaction to invoice so P&L supplement skips this invoice (no double-counting)
                $invoice->update(['transaction_id' => $transaction->id]);

                $salesOrder->update([
                    'status'         => SalesOrder::STATUS_CONFIRMED,
                    'invoice_id'     => $invoice->id,
                    'transaction_id' => $transaction->id,
                ]);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        }

        return redirect()->route('inventory.sales.show', $salesOrder)
            ->with('success', "Order {$salesOrder->order_number} confirmed. Invoice generated.");
    }

    private function reverseConfirmedOrder(SalesOrder $salesOrder): void
    {
        $salesOrder->load('items.item');

        // Reverse stock movements and restore avg_cost
        foreach ($salesOrder->items as $line) {
            $item        = $line->item;
            $qtyReturned = (float) $line->quantity;
            $costAtSale  = (float) $line->cost_price_at_sale;
            $newStock    = round((float) $item->current_stock + $qtyReturned, 3);

            // Weighted average cost: blend returned stock back in at the original sale cost
            $newAvgCost = $item->recalculateAvgCost($qtyReturned, $costAtSale);

            StockMovement::create([
                'tenant_id'       => $salesOrder->tenant_id,
                'item_id'         => $line->item_id,
                'type'            => 'adjustment_in',
                'quantity'        => $qtyReturned,
                'unit_cost'       => $costAtSale,
                'running_balance' => $newStock,
                'reference_type'  => SalesOrder::class,
                'reference_id'    => $salesOrder->id,
                'notes'           => "Cancellation of {$salesOrder->order_number}",
                'created_by'      => auth()->id(),
            ]);

            $item->update(['current_stock' => $newStock, 'avg_cost' => $newAvgCost]);
        }

        // Void the invoice
        if ($salesOrder->invoice_id) {
            $salesOrder->invoice()->update(['status' => 'void', 'transaction_id' => null]);
        }

        // Post GL reversal — flip every debit→credit and credit→debit from the original transaction
        if ($salesOrder->transaction_id) {
            $originalTxn = Transaction::find($salesOrder->transaction_id);

            if ($originalTxn) {
                $reversalEntries = $originalTxn->journalEntries()
                    ->get()
                    ->map(fn($e) => [
                        'account_id'  => $e->account_id,
                        'entry_type'  => $e->entry_type === 'debit' ? 'credit' : 'debit',
                        'amount'      => (float) $e->amount,
                        'description' => 'Reversal: ' . ($e->description ?? ''),
                    ])
                    ->toArray();

                if (! empty($reversalEntries)) {
                    $this->bookkeeping->postJournalEntry(
                        $salesOrder->tenant,
                        [
                            'reference'        => 'REV-' . $salesOrder->order_number,
                            'transaction_date' => now()->toDateString(),
                            'type'             => 'reversal',
                            'description'      => "Cancellation: {$salesOrder->order_number}",
                        ],
                        $reversalEntries
                    );
                }
            }
        }
    }

    private function syncLineItems(SalesOrder $order, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $line = new SaleOrderItem([
                'sale_order_id'      => $order->id,
                'item_id'            => $itemData['item_id'],
                'description'        => $itemData['description'],
                'quantity'           => $itemData['quantity'],
                'unit_price'         => $itemData['unit_price'],
                'cost_price_at_sale' => 0,
                'vat_applicable'     => (bool) ($itemData['vat_applicable'] ?? false),
                'vat_rate'           => Invoice::VAT_RATE,
                'sort_order'         => $index + 1,
            ]);
            $line->calculateTotals();
            $line->save();
        }
    }

    private function validateOrderForm(Request $request): array
    {
        return $request->validate([
            'customer_id'            => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name'          => ['nullable', 'string', 'max:150'],
            'sale_date'              => ['required', 'date'],
            'payment_method'         => ['required', 'in:cash,bank_transfer,pos,cheque,online'],
            'payment_reference'      => ['nullable', 'string', 'max:100'],
            'discount_amount'        => ['nullable', 'numeric', 'min:0'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.item_id'        => ['required', 'integer', Rule::exists('inventory_items', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'items.*.description'    => ['required', 'string', 'max:255'],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.vat_applicable' => ['nullable', 'boolean'],
        ]);
    }

    private function generateOrderNumber(int $tenantId): string
    {
        $prefix = 'SO-' . now()->format('Ym') . '-';

        $last = SalesOrder::where('tenant_id', $tenantId)
            ->withoutGlobalScope('tenant')
            ->where('order_number', 'like', $prefix . '%')
            ->orderBy('order_number', 'desc')
            ->lockForUpdate()
            ->first();

        $next = $last ? ((int) substr($last->order_number, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
