<?php

namespace App\Http\Controllers;

use App\Mail\NewStorefrontOrder;
use App\Models\Invoice;
use App\Models\StorefrontCategory;
use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderItem;
use App\Models\StorefrontProduct;
use App\Models\StorefrontService;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    // ── Catalog ──────────────────────────────────────────────────────────────

    public function index(Request $request, Tenant $tenant): View
    {
        $categoryId = $request->integer('category') ?: null;

        $products = StorefrontProduct::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->when($categoryId, fn($q) => $q->where('storefront_category_id', $categoryId))
            ->with(['item', 'images'])
            ->whereHas('item', fn($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get();

        $services = StorefrontService::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->when($categoryId, fn($q) => $q->where('storefront_category_id', $categoryId))
            ->with('images')
            ->orderBy('sort_order')
            ->get();

        $categoryIds = $products->pluck('storefront_category_id')
            ->merge($services->pluck('storefront_category_id'))
            ->filter()
            ->unique();

        $categories = StorefrontCategory::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $categoryIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $storeVatApplicable = (bool) ($tenant->storefront?->vat_applicable ?? true);

        return view('storefront.index', compact('tenant', 'products', 'services', 'categories', 'categoryId', 'storeVatApplicable'));
    }

    public function show(Tenant $tenant, StorefrontProduct $storefrontProduct): View
    {
        abort_unless($storefrontProduct->tenant_id === $tenant->id && $storefrontProduct->is_published, 404);

        $storefrontProduct->load(['item', 'images']);

        return view('storefront.show', ['tenant' => $tenant, 'product' => $storefrontProduct]);
    }

    public function showService(Tenant $tenant, StorefrontService $storefrontService): View
    {
        abort_unless($storefrontService->tenant_id === $tenant->id && $storefrontService->is_published, 404);

        $storefrontService->load('images');

        return view('storefront.service', ['tenant' => $tenant, 'service' => $storefrontService]);
    }

    // ── Cart (session-based, no persistence until checkout) ────────────────────

    public function cart(Tenant $tenant): View
    {
        $lines = $this->cartLines($tenant);

        return view('storefront.cart', ['tenant' => $tenant, 'lines' => $lines]);
    }

    public function addToCart(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'type'       => 'required|in:product,service',
            'id'         => 'required|integer',
            'quantity'   => 'required|numeric|min:0.01',
        ]);

        $this->assertPublished($tenant, $validated['type'], $validated['id']);

        $key  = $this->cartLineKey($validated['type'], (int) $validated['id']);
        $cart = session()->get($this->cartKey($tenant), []);
        $cart[$key] = ($cart[$key] ?? 0) + (float) $validated['quantity'];
        session()->put($this->cartKey($tenant), $cart);

        return back()->with('success', 'Added to cart.');
    }

    public function removeFromCart(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'id'   => 'required|integer',
        ]);

        $cart = session()->get($this->cartKey($tenant), []);
        unset($cart[$this->cartLineKey($validated['type'], (int) $validated['id'])]);
        session()->put($this->cartKey($tenant), $cart);

        return back()->with('success', 'Removed from cart.');
    }

    // ── Checkout ─────────────────────────────────────────────────────────────

    public function checkout(Tenant $tenant): View|RedirectResponse
    {
        $lines = $this->cartLines($tenant);

        if ($lines->isEmpty()) {
            return redirect()->route('storefront.cart', $tenant->slug)->with('error', 'Your cart is empty.');
        }

        return view('storefront.checkout', ['tenant' => $tenant, 'lines' => $lines]);
    }

    public function placeOrder(Request $request, Tenant $tenant): RedirectResponse
    {
        $order = $this->createOrderFromCart($request, $tenant, StorefrontOrder::CHANNEL_WEB);

        if (!$order) {
            return redirect()->route('storefront.cart', $tenant->slug)->with('error', 'Your cart is empty.');
        }

        return redirect()->route('storefront.order.status', ['tenant' => $tenant->slug, 'token' => $order->token])
            ->with('success', "Order {$order->order_number} submitted — {$tenant->name} will be in touch shortly.");
    }

    public function placeOrderViaWhatsapp(Request $request, Tenant $tenant): RedirectResponse
    {
        $storefront = $tenant->storefront;

        if (!$storefront || !$storefront->whatsapp_number) {
            return back()->with('error', 'WhatsApp ordering is not available for this store.');
        }

        $order = $this->createOrderFromCart($request, $tenant, StorefrontOrder::CHANNEL_WHATSAPP);

        if (!$order) {
            return redirect()->route('storefront.cart', $tenant->slug)->with('error', 'Your cart is empty.');
        }

        return redirect()->away($storefront->whatsappLink($order->summaryText()));
    }

    public function orderStatus(Tenant $tenant, string $token): View
    {
        $order = StorefrontOrder::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('token', $token)
            ->with('items')
            ->firstOrFail();

        return view('storefront.order-status', ['tenant' => $tenant, 'order' => $order]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function cartKey(Tenant $tenant): string
    {
        return "storefront_cart_{$tenant->id}";
    }

    private function cartLineKey(string $type, int $id): string
    {
        return "{$type}_{$id}";
    }

    private function assertPublished(Tenant $tenant, string $type, int $id): void
    {
        if ($type === 'product') {
            StorefrontProduct::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('is_published', true)
                ->findOrFail($id);
        } else {
            StorefrontService::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('is_published', true)
                ->findOrFail($id);
        }
    }

    /** Cart lines with live product/service pricing data, uniform across both types. */
    private function cartLines(Tenant $tenant)
    {
        $cart = session()->get($this->cartKey($tenant), []);

        if (empty($cart)) {
            return collect();
        }

        // Store-level VAT is a master switch: if it's off, nothing on this
        // storefront ever carries VAT regardless of the item's own setting.
        $storeVatApplicable = (bool) ($tenant->storefront?->vat_applicable ?? true);

        $productIds = [];
        $serviceIds = [];
        foreach (array_keys($cart) as $key) {
            if (str_starts_with($key, 'product_')) {
                $productIds[] = (int) substr($key, strlen('product_'));
            } elseif (str_starts_with($key, 'service_')) {
                $serviceIds[] = (int) substr($key, strlen('service_'));
            }
        }

        $lines = collect();

        if (!empty($productIds)) {
            $lines = $lines->merge(
                StorefrontProduct::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('id', $productIds)
                    ->with('item', 'images')
                    ->get()
                    ->map(function (StorefrontProduct $product) use ($cart, $storeVatApplicable) {
                        $qty           = (float) $cart[$this->cartLineKey('product', $product->id)];
                        $unitPrice     = (float) $product->item->selling_price;
                        $subtotal      = round($qty * $unitPrice, 2);
                        $vatApplicable = $storeVatApplicable && $product->vat_applicable;
                        $vatAmount     = $vatApplicable ? round($subtotal * Invoice::VAT_RATE / 100, 2) : 0.0;

                        return (object) [
                            'type'           => 'product',
                            'product'        => $product,
                            'service'        => null,
                            'name'           => $product->item->name,
                            'quantity'       => $qty,
                            'unit_price'     => $unitPrice,
                            'subtotal'       => $subtotal,
                            'vat_applicable' => $vatApplicable,
                            'vat_amount'     => $vatAmount,
                            'total'          => $subtotal + $vatAmount,
                        ];
                    })
            );
        }

        if (!empty($serviceIds)) {
            $lines = $lines->merge(
                StorefrontService::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('id', $serviceIds)
                    ->with('images')
                    ->get()
                    ->map(function (StorefrontService $service) use ($cart, $storeVatApplicable) {
                        $qty           = (float) $cart[$this->cartLineKey('service', $service->id)];
                        $unitPrice     = (float) $service->price;
                        $subtotal      = round($qty * $unitPrice, 2);
                        $vatApplicable = $storeVatApplicable && $service->vat_applicable;
                        $vatAmount     = $vatApplicable ? round($subtotal * Invoice::VAT_RATE / 100, 2) : 0.0;

                        return (object) [
                            'type'           => 'service',
                            'product'        => null,
                            'service'        => $service,
                            'name'           => $service->name,
                            'quantity'       => $qty,
                            'unit_price'     => $unitPrice,
                            'subtotal'       => $subtotal,
                            'vat_applicable' => $vatApplicable,
                            'vat_amount'     => $vatAmount,
                            'total'          => $subtotal + $vatAmount,
                        ];
                    })
            );
        }

        return $lines;
    }

    private function createOrderFromCart(Request $request, Tenant $tenant, string $channel): ?StorefrontOrder
    {
        $lines = $this->cartLines($tenant);

        if ($lines->isEmpty()) {
            return null;
        }

        $validated = $request->validate([
            'customer_name'     => 'required|string|max:150',
            'customer_email'    => 'required|email|max:150',
            'customer_phone'    => 'required|string|max:30',
            'delivery_address'  => 'nullable|string|max:1000',
            'notes'             => 'nullable|string|max:1000',
        ]);

        $order = DB::transaction(function () use ($validated, $tenant, $channel, $lines) {
            $order = StorefrontOrder::withoutGlobalScope('tenant')->create([
                'tenant_id'         => $tenant->id,
                'order_number'      => $this->generateOrderNumber($tenant->id),
                'customer_name'     => $validated['customer_name'],
                'customer_email'    => $validated['customer_email'],
                'customer_phone'    => $validated['customer_phone'],
                'delivery_address'  => $validated['delivery_address'] ?? null,
                'notes'             => $validated['notes'] ?? null,
                'status'            => StorefrontOrder::STATUS_PENDING,
                'channel'           => $channel,
            ]);

            foreach ($lines as $line) {
                $item = new StorefrontOrderItem([
                    'storefront_order_id'    => $order->id,
                    'inventory_item_id'      => $line->type === 'product' ? $line->product->inventory_item_id : null,
                    'storefront_service_id'  => $line->type === 'service' ? $line->service->id : null,
                    'description'            => $line->name,
                    'quantity'               => $line->quantity,
                    'unit_price'             => $line->unit_price,
                    'vat_applicable'         => $line->vat_applicable,
                ]);
                $item->calculateTotals();
                $item->save();
            }

            $order->load('items');
            $order->update([
                'subtotal'     => $order->items->sum('subtotal'),
                'vat_amount'   => $order->items->sum('vat_amount'),
                'total_amount' => $order->items->sum('total'),
            ]);

            return $order;
        });

        session()->forget($this->cartKey($tenant));

        Mail::to($tenant->email)->queue(new NewStorefrontOrder($order, $tenant));

        return $order;
    }

    private function generateOrderNumber(int $tenantId): string
    {
        $prefix = 'WEB-' . now()->format('Ym') . '-';

        $last = StorefrontOrder::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('order_number', 'like', $prefix . '%')
            ->orderBy('order_number', 'desc')
            ->lockForUpdate()
            ->first();

        $next = $last ? ((int) substr($last->order_number, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
