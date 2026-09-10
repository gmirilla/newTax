<?php

namespace Tests\Feature;

use App\Mail\NewStorefrontOrder;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\Plan;
use App\Models\SalesOrder;
use App\Models\Storefront;
use App\Models\StorefrontCategory;
use App\Models\StorefrontOrder;
use App\Models\StorefrontProduct;
use App\Models\StorefrontService;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StorefrontModuleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;
    private User   $staff;
    private Plan   $plan;
    private InventoryItem $item;
    private StorefrontProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name'          => 'Business',
            'slug'          => 'business',
            'price_monthly' => 25000,
            'limits'        => ['inventory' => true, 'storefront' => true],
            'is_active'     => true,
            'is_public'     => true,
        ]);

        $this->tenant = Tenant::create([
            'name'                    => 'Test Trading Co',
            'slug'                    => 'test-trading-co',
            'email'                   => 'shop@testtrading.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 10_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $this->plan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $this->admin = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Admin User',
            'email'             => 'admin@testtrading.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->staff = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Staff User',
            'email'             => 'staff@testtrading.ng',
            'password'          => bcrypt('password'),
            'role'              => 'staff',
            'is_active'         => true,
            'email_verified_at' => now(),
            'module_access'     => [],
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($this->tenant);

        Storefront::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $this->tenant->id,
            'is_enabled'      => true,
            'whatsapp_number' => '+2348012345678',
        ]);

        $this->item = InventoryItem::withoutGlobalScope('tenant')->create([
            'tenant_id'     => $this->tenant->id,
            'name'          => 'Bag of Rice (50kg)',
            'sku'           => 'RICE-50',
            'item_type'     => 'product',
            'unit'          => 'bag',
            'selling_price' => 45000,
            'cost_price'    => 38000,
            'avg_cost'      => 38000,
            'current_stock' => 100,
            'is_active'     => true,
            'created_by'    => $this->admin->id,
        ]);

        $this->product = StorefrontProduct::withoutGlobalScope('tenant')->create([
            'tenant_id'         => $this->tenant->id,
            'inventory_item_id' => $this->item->id,
            'is_published'      => true,
        ]);

        $location = InventoryLocation::withoutGlobalScope('tenant')->create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Main Store',
            'code'       => 'MAIN',
            'is_default' => true,
            'is_active'  => true,
        ]);

        // The stock-availability check reads live StockMovement balances per
        // location (InventoryItem::stockAtLocation), not the current_stock
        // column directly — seed an opening movement so confirm() sees stock.
        StockMovement::create([
            'tenant_id'       => $this->tenant->id,
            'item_id'         => $this->item->id,
            'location_id'     => $location->id,
            'type'            => 'opening',
            'quantity'        => 100,
            'unit_cost'       => 38000,
            'running_balance' => 100,
            'notes'           => 'Opening stock',
            'created_by'      => $this->admin->id,
        ]);
    }

    // ── Scenario 1: Public catalog visibility ─────────────────────────────────

    public function test_public_shop_shows_published_products(): void
    {
        $this->get(route('storefront.index', $this->tenant->slug))
            ->assertOk()
            ->assertSee('Bag of Rice');
    }

    public function test_public_shop_404s_when_storefront_disabled(): void
    {
        $this->tenant->storefront->update(['is_enabled' => false]);

        $this->get(route('storefront.index', $this->tenant->slug))
            ->assertNotFound();
    }

    public function test_public_shop_404s_when_plan_lacks_storefront(): void
    {
        $freePlan = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'price_monthly' => 0,
            'limits' => ['storefront' => false], 'is_active' => true, 'is_public' => true,
        ]);
        $this->tenant->update(['plan_id' => $freePlan->id]);

        $this->get(route('storefront.index', $this->tenant->slug))
            ->assertNotFound();
    }

    public function test_public_shop_only_shows_this_tenants_products(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Shop', 'slug' => 'other-shop', 'email' => 'other@shop.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $this->plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);
        Storefront::withoutGlobalScope('tenant')->create(['tenant_id' => $otherTenant->id, 'is_enabled' => true]);

        $response = $this->get(route('storefront.index', $otherTenant->slug));

        $response->assertOk();
        $response->assertDontSee('Bag of Rice');
    }

    // ── Scenario 2: Guest checkout (web channel) ──────────────────────────────

    public function test_guest_can_add_to_cart_and_checkout(): void
    {
        Mail::fake();

        $this->post(route('storefront.cart.add', $this->tenant->slug), [
            'type'     => 'product',
            'id'       => $this->product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $response = $this->post(route('storefront.checkout.submit', $this->tenant->slug), [
            'customer_name'    => 'Jane Customer',
            'customer_email'   => 'jane@example.com',
            'customer_phone'   => '08011112222',
            'delivery_address' => '10 Test Street, Lagos',
        ]);

        $order = StorefrontOrder::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->first();

        $this->assertNotNull($order);
        $response->assertRedirect(route('storefront.order.status', ['tenant' => $this->tenant->slug, 'token' => $order->token]));

        $this->assertEquals(StorefrontOrder::STATUS_PENDING, $order->status);
        $this->assertEquals(StorefrontOrder::CHANNEL_WEB, $order->channel);
        $this->assertEquals('Jane Customer', $order->customer_name);
        $this->assertEquals(1, $order->items()->count());
        $this->assertEquals(90000, (float) $order->subtotal); // 2 x 45000
        $this->assertEquals(96750, (float) $order->total_amount); // + 7.5% VAT

        Mail::assertQueued(NewStorefrontOrder::class);
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        $this->post(route('storefront.checkout.submit', $this->tenant->slug), [
            'customer_name'  => 'Jane Customer',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '08011112222',
        ])->assertRedirect(route('storefront.cart', $this->tenant->slug));

        $this->assertDatabaseCount('storefront_orders', 0);
    }

    // ── Scenario 3: WhatsApp channel ──────────────────────────────────────────

    public function test_whatsapp_checkout_creates_order_and_redirects_to_wa_link(): void
    {
        Mail::fake();

        $this->post(route('storefront.cart.add', $this->tenant->slug), [
            'type'     => 'product',
            'id'       => $this->product->id,
            'quantity' => 1,
        ]);

        $response = $this->post(route('storefront.checkout.whatsapp', $this->tenant->slug), [
            'customer_name'  => 'Wa Customer',
            'customer_email' => 'wa@example.com',
            'customer_phone' => '08033334444',
        ]);

        $order = StorefrontOrder::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->first();

        $this->assertEquals(StorefrontOrder::CHANNEL_WHATSAPP, $order->channel);
        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/2348012345678', $response->headers->get('Location'));
    }

    // ── Scenario 4: Accept → bridges into existing Sales Order pipeline ───────

    public function test_admin_accepting_order_creates_customer_and_draft_sales_order(): void
    {
        $order = $this->makePendingOrder();

        $response = $this->actingAs($this->admin)
            ->post(route('storefront.orders.accept', $order));

        $order->refresh();
        $this->assertEquals(StorefrontOrder::STATUS_ACCEPTED, $order->status);
        $this->assertNotNull($order->sales_order_id);

        $salesOrder = SalesOrder::withoutGlobalScope('tenant')->find($order->sales_order_id);
        $this->assertNotNull($salesOrder);
        $this->assertEquals(SalesOrder::STATUS_DRAFT, $salesOrder->status);
        $this->assertEquals(1, $salesOrder->items()->count());

        $customer = Customer::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant->id)
            ->where('email', $order->customer_email)
            ->first();
        $this->assertNotNull($customer);

        $response->assertRedirect(route('inventory.sales.show', $salesOrder));
    }

    public function test_accepting_order_twice_is_rejected(): void
    {
        $order = $this->makePendingOrder();

        $this->actingAs($this->admin)->post(route('storefront.orders.accept', $order));
        $this->actingAs($this->admin)
            ->post(route('storefront.orders.accept', $order))
            ->assertRedirect();

        // Only one sales order should ever be created for this storefront order.
        $this->assertEquals(1, SalesOrder::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_admin_can_reject_order(): void
    {
        $order = $this->makePendingOrder();

        $this->actingAs($this->admin)
            ->post(route('storefront.orders.reject', $order), ['rejection_reason' => 'Out of stock'])
            ->assertRedirect();

        $order->refresh();
        $this->assertEquals(StorefrontOrder::STATUS_REJECTED, $order->status);
        $this->assertEquals('Out of stock', $order->rejection_reason);
        $this->assertNull($order->sales_order_id);
        $this->assertDatabaseCount('sales_orders', 0);
    }

    // ── Scenario 5: Access control ────────────────────────────────────────────

    public function test_staff_without_storefront_access_is_denied(): void
    {
        $this->actingAs($this->staff)
            ->get(route('storefront.orders.index'))
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_accountant_can_view_storefront_orders(): void
    {
        // Storefront admin routes sit behind role:admin,accountant (same as
        // Inventory/Maintenance) — staff can never reach them regardless of
        // module_access. Accountants pass the role gate and default to all
        // modules on, so they're the right role to exercise canAccess() here.
        $accountant = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Accountant User',
            'email'             => 'accountant@testtrading.ng',
            'password'          => bcrypt('password'),
            'role'              => 'accountant',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($accountant)
            ->get(route('storefront.orders.index'))
            ->assertOk();
    }

    public function test_tenant_on_plan_without_storefront_is_redirected_from_admin_routes(): void
    {
        $freePlan = Plan::create([
            'name' => 'Free', 'slug' => 'free-2', 'price_monthly' => 0,
            'limits' => ['storefront' => false], 'is_active' => true, 'is_public' => true,
        ]);
        $this->tenant->update(['plan_id' => $freePlan->id]);

        $this->actingAs($this->admin)
            ->get(route('storefront.orders.index'))
            ->assertRedirect(route('billing'));
    }

    public function test_admin_cannot_action_another_tenants_order(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Shop', 'slug' => 'other-shop-2', 'email' => 'other2@shop.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $this->plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        $order = StorefrontOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'        => $otherTenant->id,
            'order_number'     => 'WEB-000001',
            'customer_name'    => 'Someone',
            'customer_email'   => 'someone@example.com',
            'customer_phone'   => '08000000000',
            'status'           => StorefrontOrder::STATUS_PENDING,
            'channel'          => StorefrontOrder::CHANNEL_WEB,
        ]);

        $this->actingAs($this->admin)
            ->post(route('storefront.orders.accept', $order))
            ->assertForbidden();
    }

    // ── Scenario 6: Categories ─────────────────────────────────────────────────

    public function test_admin_category_and_service_index_pages_render(): void
    {
        $this->actingAs($this->admin)->get(route('storefront.categories.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('storefront.services.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('storefront.products.index'))->assertOk();
    }

    public function test_admin_can_create_update_and_delete_category(): void
    {
        $this->actingAs($this->admin)
            ->post(route('storefront.categories.store'), ['name' => 'Groceries'])
            ->assertRedirect();

        $category = StorefrontCategory::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertEquals('Groceries', $category->name);

        $this->actingAs($this->admin)
            ->put(route('storefront.categories.update', $category), ['name' => 'Food & Groceries', 'is_active' => true])
            ->assertRedirect();
        $this->assertEquals('Food & Groceries', $category->fresh()->name);

        $this->product->update(['storefront_category_id' => $category->id]);

        $this->actingAs($this->admin)
            ->delete(route('storefront.categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseMissing('storefront_categories', ['id' => $category->id]);
        // Deleting a category uncategorizes its products rather than deleting them.
        $this->assertNotNull($this->product->fresh());
        $this->assertNull($this->product->fresh()->storefront_category_id);
    }

    // ── Scenario 7: Services ───────────────────────────────────────────────────

    public function test_admin_can_create_publish_and_unpublish_a_service(): void
    {
        $this->actingAs($this->admin)
            ->post(route('storefront.services.store'), [
                'name'  => 'Home Cleaning',
                'price' => 15000,
            ])->assertRedirect();

        $service = StorefrontService::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertFalse($service->is_published);

        $this->actingAs($this->admin)
            ->post(route('storefront.services.publish', $service))
            ->assertRedirect();
        $this->assertTrue($service->fresh()->is_published);

        $this->get(route('storefront.service', ['tenant' => $this->tenant->slug, 'storefrontService' => $service->id]))
            ->assertOk()
            ->assertSee('Home Cleaning');

        $this->actingAs($this->admin)
            ->post(route('storefront.services.unpublish', $service))
            ->assertRedirect();
        $this->assertFalse($service->fresh()->is_published);
    }

    public function test_public_catalog_shows_products_and_services_filtered_by_category(): void
    {
        $groceries = StorefrontCategory::withoutGlobalScope('tenant')->create(['tenant_id' => $this->tenant->id, 'name' => 'Groceries']);
        $services  = StorefrontCategory::withoutGlobalScope('tenant')->create(['tenant_id' => $this->tenant->id, 'name' => 'Services']);

        $this->product->update(['storefront_category_id' => $groceries->id]);

        $service = StorefrontService::withoutGlobalScope('tenant')->create([
            'tenant_id'               => $this->tenant->id,
            'storefront_category_id'  => $services->id,
            'name'                    => 'Home Cleaning',
            'price'                   => 15000,
            'is_published'            => true,
        ]);

        $response = $this->get(route('storefront.index', $this->tenant->slug));
        $response->assertOk()->assertSee('Bag of Rice')->assertSee('Home Cleaning');

        $groceriesOnly = $this->get(route('storefront.index', ['tenant' => $this->tenant->slug, 'category' => $groceries->id]));
        $groceriesOnly->assertOk()->assertSee('Bag of Rice')->assertDontSee('Home Cleaning');

        $servicesOnly = $this->get(route('storefront.index', ['tenant' => $this->tenant->slug, 'category' => $services->id]));
        $servicesOnly->assertOk()->assertSee('Home Cleaning')->assertDontSee('Bag of Rice');
    }

    // ── Scenario 8: Mixed cart (product + service) ─────────────────────────────

    public function test_cart_can_hold_a_product_and_a_service_and_checkout_creates_mixed_order(): void
    {
        Mail::fake();

        $service = StorefrontService::withoutGlobalScope('tenant')->create([
            'tenant_id'     => $this->tenant->id,
            'name'          => 'Delivery',
            'price'         => 2000,
            'is_published'  => true,
        ]);

        $this->post(route('storefront.cart.add', $this->tenant->slug), [
            'type' => 'product', 'id' => $this->product->id, 'quantity' => 1,
        ])->assertRedirect();

        $this->post(route('storefront.cart.add', $this->tenant->slug), [
            'type' => 'service', 'id' => $service->id, 'quantity' => 1,
        ])->assertRedirect();

        $this->get(route('storefront.cart', $this->tenant->slug))
            ->assertOk()->assertSee('Bag of Rice')->assertSee('Delivery');

        $this->get(route('storefront.checkout', $this->tenant->slug))
            ->assertOk()->assertSee('Bag of Rice')->assertSee('Delivery');

        $this->post(route('storefront.checkout.submit', $this->tenant->slug), [
            'customer_name'  => 'Mixed Cart Customer',
            'customer_email' => 'mixed@example.com',
            'customer_phone' => '08099998888',
        ])->assertRedirect();

        $order = StorefrontOrder::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertEquals(2, $order->items()->count());

        $itemLine    = $order->items()->whereNotNull('inventory_item_id')->first();
        $serviceLine = $order->items()->whereNotNull('storefront_service_id')->first();

        $this->assertNotNull($itemLine);
        $this->assertNotNull($serviceLine);
        $this->assertEquals($this->item->id, $itemLine->inventory_item_id);
        $this->assertEquals($service->id, $serviceLine->storefront_service_id);
        $this->assertEquals(45000 + 2000, (float) $order->subtotal);
    }

    // ── Scenario 9: Accept + confirm a mixed order through the Sales Order pipeline ──

    public function test_accepting_and_confirming_a_mixed_order_only_moves_stock_for_the_item_line(): void
    {
        $service = StorefrontService::withoutGlobalScope('tenant')->create([
            'tenant_id'     => $this->tenant->id,
            'name'          => 'Delivery',
            'price'         => 2000,
            'is_published'  => true,
        ]);

        $order = $this->makeMixedPendingOrder($service);

        $this->actingAs($this->admin)
            ->post(route('storefront.orders.accept', $order))
            ->assertRedirect();

        $order->refresh();
        $salesOrder = SalesOrder::withoutGlobalScope('tenant')->findOrFail($order->sales_order_id);
        $this->assertEquals(2, $salesOrder->items()->count());

        $itemLine    = $salesOrder->items()->whereNotNull('item_id')->first();
        $serviceLine = $salesOrder->items()->whereNull('item_id')->first();
        $this->assertNotNull($itemLine);
        $this->assertNotNull($serviceLine);
        $this->assertEquals('Delivery', $serviceLine->description);

        $stockBefore = (float) $this->item->fresh()->current_stock;

        $this->actingAs($this->admin)
            ->post(route('inventory.sales.confirm', $salesOrder))
            ->assertRedirect(route('inventory.sales.show', $salesOrder));

        $salesOrder->refresh();
        $this->assertEquals(SalesOrder::STATUS_CONFIRMED, $salesOrder->status);

        // Stock only moved for the item line — the service line carries no inventory_item_id.
        $this->assertEquals($stockBefore - 2, (float) $this->item->fresh()->current_stock);
        $this->assertEquals(1, StockMovement::where('tenant_id', $this->tenant->id)
            ->withoutGlobalScope('tenant')->where('reference_id', $salesOrder->id)->count());

        $invoice = $salesOrder->invoice;
        $this->assertNotNull($invoice);
        $this->assertEquals(2, $invoice->items()->count());
    }

    public function test_confirming_and_cancelling_an_all_service_order_does_not_touch_stock(): void
    {
        $service = StorefrontService::withoutGlobalScope('tenant')->create([
            'tenant_id'     => $this->tenant->id,
            'name'          => 'Consultation',
            'price'         => 5000,
            'is_published'  => true,
        ]);

        $order = StorefrontOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'     => $this->tenant->id,
            'order_number'  => 'WEB-' . now()->format('Ym') . '-0002',
            'customer_name' => 'Jane Customer',
            'customer_email'=> 'jane2@example.com',
            'customer_phone'=> '08011112222',
            'status'        => StorefrontOrder::STATUS_PENDING,
            'channel'       => StorefrontOrder::CHANNEL_WEB,
            'subtotal'      => 5000,
            'vat_amount'    => 375,
            'total_amount'  => 5375,
        ]);
        $order->items()->create([
            'storefront_service_id' => $service->id,
            'description'           => $service->name,
            'quantity'              => 1,
            'unit_price'            => 5000,
            'vat_amount'            => 375,
            'subtotal'              => 5000,
            'total'                 => 5375,
        ]);

        $this->actingAs($this->admin)->post(route('storefront.orders.accept', $order))->assertRedirect();

        $salesOrder = SalesOrder::withoutGlobalScope('tenant')->findOrFail($order->fresh()->sales_order_id);

        $this->actingAs($this->admin)
            ->post(route('inventory.sales.confirm', $salesOrder))
            ->assertRedirect(route('inventory.sales.show', $salesOrder));

        $this->assertEquals(0, StockMovement::where('tenant_id', $this->tenant->id)
            ->withoutGlobalScope('tenant')->where('reference_id', $salesOrder->id)->count());

        $this->actingAs($this->admin)
            ->post(route('inventory.sales.cancel', $salesOrder->fresh()))
            ->assertRedirect();

        $this->assertEquals(SalesOrder::STATUS_CANCELLED, $salesOrder->fresh()->status);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function makeMixedPendingOrder(StorefrontService $service): StorefrontOrder
    {
        $order = StorefrontOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'        => $this->tenant->id,
            'order_number'     => 'WEB-' . now()->format('Ym') . '-0003',
            'customer_name'    => 'Mixed Customer',
            'customer_email'   => 'mixed2@example.com',
            'customer_phone'   => '08011112222',
            'status'           => StorefrontOrder::STATUS_PENDING,
            'channel'          => StorefrontOrder::CHANNEL_WEB,
            'subtotal'         => 47000,
            'vat_amount'       => 3525,
            'total_amount'     => 50525,
        ]);

        $order->items()->create([
            'inventory_item_id' => $this->item->id,
            'description'       => $this->item->name,
            'quantity'          => 2,
            'unit_price'        => 45000,
            'vat_amount'        => 6750,
            'subtotal'          => 90000,
            'total'             => 96750,
        ]);

        $order->items()->create([
            'storefront_service_id' => $service->id,
            'description'           => $service->name,
            'quantity'              => 1,
            'unit_price'            => 2000,
            'vat_amount'            => 150,
            'subtotal'              => 2000,
            'total'                 => 2150,
        ]);

        return $order;
    }

    private function makePendingOrder(): StorefrontOrder
    {
        $order = StorefrontOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'        => $this->tenant->id,
            'order_number'     => 'WEB-' . now()->format('Ym') . '-0001',
            'customer_name'    => 'Jane Customer',
            'customer_email'   => 'jane@example.com',
            'customer_phone'   => '08011112222',
            'delivery_address' => '10 Test Street, Lagos',
            'status'           => StorefrontOrder::STATUS_PENDING,
            'channel'          => StorefrontOrder::CHANNEL_WEB,
            'subtotal'         => 90000,
            'vat_amount'       => 6750,
            'total_amount'     => 96750,
        ]);

        $order->items()->create([
            'inventory_item_id' => $this->item->id,
            'description'       => $this->item->name,
            'quantity'          => 2,
            'unit_price'        => 45000,
            'vat_amount'        => 6750,
            'subtotal'          => 90000,
            'total'             => 96750,
        ]);

        return $order;
    }
}
