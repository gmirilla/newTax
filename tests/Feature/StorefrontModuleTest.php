<?php

namespace Tests\Feature;

use App\Mail\NewStorefrontOrder;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Plan;
use App\Models\SalesOrder;
use App\Models\Storefront;
use App\Models\StorefrontOrder;
use App\Models\StorefrontProduct;
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
            'storefront_product_id' => $this->product->id,
            'quantity'               => 2,
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
            'storefront_product_id' => $this->product->id,
            'quantity'               => 1,
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

    // ── Helper ────────────────────────────────────────────────────────────────

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
