<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name'          => 'Business',
            'slug'          => 'business',
            'price_monthly' => 25000,
            'limits'        => ['inventory' => true],
            'is_active'     => true,
            'is_public'     => true,
        ]);

        $this->tenant = Tenant::create([
            'name'                    => 'Test Company',
            'slug'                    => 'test-company',
            'email'                   => 'test@company.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 10_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $plan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $this->admin = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Test Admin',
            'email'             => 'admin@test.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($this->tenant);
    }

    // ── Create Item ──────────────────────────────────────────────────────────

    public function test_admin_can_create_inventory_item(): void
    {
        $this->actingAs($this->admin)
            ->post(route('inventory.items.store'), [
                'name'          => 'Steel Rod',
                'sku'           => 'STL-001',
                'item_type'     => 'product',
                'unit'          => 'piece',
                'selling_price' => 2000,
                'cost_price'    => 1500,
                'restock_level' => 20,
            ])
            ->assertRedirect(route('inventory.items.index'));

        $this->assertDatabaseHas('inventory_items', [
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Steel Rod',
            'sku'        => 'STL-001',
            'cost_price' => 1500,
            'is_active'  => true,
        ]);
    }

    public function test_item_creation_with_opening_stock_posts_gl_entry(): void
    {
        $this->actingAs($this->admin)
            ->post(route('inventory.items.store'), [
                'name'          => 'Copper Wire',
                'item_type'     => 'raw_material',
                'unit'          => 'roll',
                'selling_price' => 8000,
                'cost_price'    => 6000,
                'restock_level' => 5,
                'opening_stock' => 10,
            ]);

        $item = InventoryItem::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant->id)
            ->where('name', 'Copper Wire')
            ->firstOrFail();

        $this->assertEquals(10.0, (float) $item->current_stock);

        // Opening stock movement recorded
        $this->assertDatabaseHas('stock_movements', [
            'item_id'  => $item->id,
            'type'     => 'opening',
            'quantity' => 10,
        ]);

        // GL journal: Dr 1200 Inventory / Cr 3001 Equity at ₦60,000 (10 × 6,000)
        $tx = Transaction::where('tenant_id', $this->tenant->id)
            ->where('type', 'opening_balance')
            ->first();
        $this->assertNotNull($tx);
        $this->assertEquals(60000.0, (float) $tx->amount);
    }

    public function test_item_creation_without_opening_stock_posts_no_gl(): void
    {
        $this->actingAs($this->admin)
            ->post(route('inventory.items.store'), [
                'name'          => 'Empty Item',
                'item_type'     => 'product',
                'unit'          => 'piece',
                'selling_price' => 500,
                'cost_price'    => 300,
                'restock_level' => 0,
            ]);

        $this->assertEquals(
            0,
            Transaction::where('tenant_id', $this->tenant->id)->count()
        );
    }

    // ── Stock Adjustment ─────────────────────────────────────────────────────

    public function test_admin_can_adjust_stock_upward(): void
    {
        $item = InventoryItem::withoutGlobalScope('tenant')->create([
            'tenant_id'     => $this->tenant->id,
            'name'          => 'Paint Tin',
            'item_type'     => 'product',
            'unit'          => 'tin',
            'selling_price' => 3000,
            'cost_price'    => 2500,
            'avg_cost'      => 2500,
            'current_stock' => 10,
            'restock_level' => 5,
            'is_active'     => true,
            'created_by'    => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventory.items.adjust', $item), [
                'type'     => 'adjustment_in',
                'quantity' => 5,
                'notes'    => 'Found in warehouse',
            ])
            ->assertRedirect();

        $item->refresh();
        $this->assertEquals(15.0, (float) $item->current_stock);

        $this->assertDatabaseHas('stock_movements', [
            'item_id'  => $item->id,
            'type'     => 'adjustment_in',
            'quantity' => 5,
        ]);
    }

    public function test_admin_can_adjust_stock_downward(): void
    {
        $item = InventoryItem::withoutGlobalScope('tenant')->create([
            'tenant_id'     => $this->tenant->id,
            'name'          => 'Nails',
            'item_type'     => 'product',
            'unit'          => 'kg',
            'selling_price' => 500,
            'cost_price'    => 300,
            'avg_cost'      => 300,
            'current_stock' => 20,
            'restock_level' => 5,
            'is_active'     => true,
            'created_by'    => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventory.items.adjust', $item), [
                'type'     => 'adjustment_out',
                'quantity' => 8,
                'notes'    => 'Damaged goods written off',
            ])
            ->assertRedirect();

        $item->refresh();
        $this->assertEquals(12.0, (float) $item->current_stock);
    }

    public function test_stock_adjustment_out_cannot_exceed_current_stock(): void
    {
        $item = InventoryItem::withoutGlobalScope('tenant')->create([
            'tenant_id'     => $this->tenant->id,
            'name'          => 'Screws',
            'item_type'     => 'product',
            'unit'          => 'pack',
            'selling_price' => 200,
            'cost_price'    => 100,
            'avg_cost'      => 100,
            'current_stock' => 3,
            'restock_level' => 1,
            'is_active'     => true,
            'created_by'    => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventory.items.adjust', $item), [
                'type'     => 'adjustment_out',
                'quantity' => 10,
                'notes'    => 'Too much',
            ])
            ->assertSessionHasErrors('quantity');
    }

    // ── Plan Gate ────────────────────────────────────────────────────────────

    public function test_inventory_routes_redirect_to_billing_without_inventory_plan(): void
    {
        $freePlan = Plan::create([
            'name'          => 'Free',
            'slug'          => 'free',
            'price_monthly' => 0,
            'limits'        => ['inventory' => false],
            'is_active'     => true,
            'is_public'     => true,
        ]);

        $freeTenant = Tenant::create([
            'name'                    => 'Free Company',
            'slug'                    => 'free-co',
            'email'                   => 'free@co.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 5_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $freePlan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $freeUser = User::forceCreate([
            'tenant_id'         => $freeTenant->id,
            'name'              => 'Free Admin',
            'email'             => 'admin@free-co.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($freeUser)
            ->get(route('inventory.items.index'))
            ->assertRedirect(route('billing'));
    }
}
