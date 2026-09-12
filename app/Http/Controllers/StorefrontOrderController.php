<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SaleOrderItem;
use App\Models\SalesOrder;
use App\Models\StorefrontOrder;
use App\Traits\ResolvesLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StorefrontOrderController extends Controller
{
    use ResolvesLocation;

    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;

        $orders = StorefrontOrder::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $pendingCount = StorefrontOrder::where('tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->where('status', StorefrontOrder::STATUS_PENDING)
            ->count();

        return view('storefront_admin.orders.index', compact('orders', 'pendingCount'));
    }

    public function show(Request $request, StorefrontOrder $storefrontOrder): View
    {
        $this->authorizeOrder($request, $storefrontOrder);

        $storefrontOrder->load(['items.item', 'salesOrder']);

        return view('storefront_admin.orders.show', ['order' => $storefrontOrder]);
    }

    public function accept(Request $request, StorefrontOrder $storefrontOrder): RedirectResponse
    {
        $this->authorizeOrder($request, $storefrontOrder);

        if (!$storefrontOrder->canBeActioned()) {
            return back()->with('error', 'This order has already been actioned.');
        }

        $tenant   = $request->user()->tenant;
        $location = $this->defaultLocation($tenant);

        $storefrontOrder->load('items.item', 'items.service');

        $salesOrder = DB::transaction(function () use ($storefrontOrder, $tenant, $location, $request) {
            $customer = Customer::withoutGlobalScope('tenant')->firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $storefrontOrder->customer_email],
                [
                    'name'      => $storefrontOrder->customer_name,
                    'phone'     => $storefrontOrder->customer_phone,
                    'address'   => $storefrontOrder->delivery_address,
                    'is_active' => true,
                ]
            );

            $salesOrder = SalesOrder::withoutGlobalScope('tenant')->create([
                'tenant_id'      => $tenant->id,
                'location_id'    => $location->id,
                'order_number'   => $this->generateSalesOrderNumber($tenant->id),
                'customer_id'    => $customer->id,
                'sale_date'      => now()->toDateString(),
                'payment_method' => 'bank_transfer',
                'notes'          => "From storefront order {$storefrontOrder->order_number}."
                    . ($storefrontOrder->notes ? " Customer notes: {$storefrontOrder->notes}" : ''),
                'status'         => SalesOrder::STATUS_DRAFT,
                'created_by'     => $request->user()->id,
            ]);

            foreach ($storefrontOrder->items as $index => $storefrontItem) {
                // Re-price against the current selling price — protects against
                // stale prices between the customer's order and the tenant's review.
                $unitPrice = (float) (
                    $storefrontItem->inventory_item_id
                        ? ($storefrontItem->item->selling_price ?? $storefrontItem->unit_price)
                        : ($storefrontItem->service->price ?? $storefrontItem->unit_price)
                );

                $line = new SaleOrderItem([
                    'sale_order_id'      => $salesOrder->id,
                    'item_id'            => $storefrontItem->inventory_item_id,
                    'description'        => $storefrontItem->description,
                    'quantity'           => $storefrontItem->quantity,
                    'unit_price'         => $unitPrice,
                    'cost_price_at_sale' => 0,
                    'vat_applicable'     => $storefrontItem->vat_applicable,
                    'vat_rate'           => Invoice::VAT_RATE,
                    'sort_order'         => $index + 1,
                ]);
                $line->calculateTotals();
                $line->save();
            }

            $salesOrder->recalculateTotals();

            $storefrontOrder->update([
                'status'         => StorefrontOrder::STATUS_ACCEPTED,
                'sales_order_id' => $salesOrder->id,
            ]);

            return $salesOrder;
        });

        AuditLog::record('storefront_order.accepted', $storefrontOrder, [], ['sales_order_id' => $salesOrder->id]);

        return redirect()->route('inventory.sales.show', $salesOrder)
            ->with('success', "Order {$storefrontOrder->order_number} accepted — review and confirm the draft sales order below.");
    }

    public function reject(Request $request, StorefrontOrder $storefrontOrder): RedirectResponse
    {
        $this->authorizeOrder($request, $storefrontOrder);

        if (!$storefrontOrder->canBeActioned()) {
            return back()->with('error', 'This order has already been actioned.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $storefrontOrder->update([
            'status'            => StorefrontOrder::STATUS_REJECTED,
            'rejection_reason'  => $validated['rejection_reason'] ?? null,
        ]);

        AuditLog::record('storefront_order.rejected', $storefrontOrder, [], ['reason' => $validated['rejection_reason'] ?? null]);

        return back()->with('success', "Order {$storefrontOrder->order_number} declined.");
    }

    private function authorizeOrder(Request $request, StorefrontOrder $storefrontOrder): void
    {
        abort_unless($storefrontOrder->tenant_id === $request->user()->tenant_id, 403);
    }

    private function generateSalesOrderNumber(int $tenantId): string
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
