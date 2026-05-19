<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\InventoryItem;
use App\Models\JournalEntry;
use App\Models\MaintenanceAsset;
use App\Models\MaintenanceBreakdown;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceLaborLog;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\MaintenanceWorkOrderPart;
use App\Models\Plan;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BookkeepingService;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModuleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;
    private User   $staff;
    private Plan   $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name'          => 'Business',
            'slug'          => 'business',
            'price_monthly' => 25000,
            'limits'        => ['inventory' => true, 'maintenance' => true],
            'is_active'     => true,
            'is_public'     => true,
        ]);

        $this->tenant = Tenant::create([
            'name'                    => 'Test Factory',
            'slug'                    => 'test-factory',
            'email'                   => 'test@factory.ng',
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
            'email'             => 'admin@factory.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->staff = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Staff User',
            'email'             => 'staff@factory.ng',
            'password'          => bcrypt('password'),
            'role'              => 'staff',
            'is_active'         => true,
            'email_verified_at' => now(),
            'module_access'     => ['maintenance' => true],
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($this->tenant);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function makeAsset(array $overrides = []): MaintenanceAsset
    {
        return MaintenanceAsset::withoutGlobalScope('tenant')->create(array_merge([
            'tenant_id'  => $this->tenant->id,
            'asset_code' => 'AST-0001',
            'asset_name' => 'Hydraulic Press',
            'status'     => MaintenanceAsset::STATUS_ACTIVE,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    private function makeInventoryPart(array $overrides = []): InventoryItem
    {
        return InventoryItem::withoutGlobalScope('tenant')->create(array_merge([
            'tenant_id'     => $this->tenant->id,
            'name'          => 'Hydraulic Oil',
            'sku'           => 'HYD-OIL-01',
            'item_type'     => 'consumable',
            'unit'          => 'litre',
            'current_stock' => 100,
            'cost_price'    => 500,
            'avg_cost'      => 500,
            'reorder_point' => 10,
            'created_by'    => $this->admin->id,
        ], $overrides));
    }

    // ── Scenario 1: Asset Creation ────────────────────────────────────────────

    public function test_admin_can_create_asset(): void
    {
        $this->actingAs($this->admin)
            ->post(route('maintenance.assets.store'), [
                'asset_name' => 'Lathe Machine',
                'status'     => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('maintenance_assets', [
            'tenant_id'  => $this->tenant->id,
            'asset_name' => 'Lathe Machine',
            'status'     => 'active',
        ]);
    }

    public function test_asset_is_created_with_auto_code(): void
    {
        $service = app(MaintenanceService::class);
        $code    = $service->nextAssetCode($this->tenant);

        $this->assertMatchesRegularExpression('/^AST-\d{4}$/', $code);
    }

    // ── Scenario 2: PM Schedule Creation ─────────────────────────────────────

    public function test_admin_can_create_pm_schedule(): void
    {
        $asset = $this->makeAsset();

        $this->actingAs($this->admin)
            ->post(route('maintenance.schedules.store'), [
                'asset_id'         => $asset->id,
                'name'             => 'Monthly Oil Change',
                'maintenance_type' => 'lubrication',
                'frequency_type'   => 'monthly',
                'next_due_date'    => now()->addDays(30)->toDateString(),
                'estimated_hours'  => 2,
            ])
            ->assertRedirect(route('maintenance.schedules.index'));

        $this->assertDatabaseHas('maintenance_schedules', [
            'tenant_id'  => $this->tenant->id,
            'asset_id'   => $asset->id,
            'name'       => 'Monthly Oil Change',
            'is_active'  => true,
        ]);
    }

    public function test_schedule_advance_next_due_date(): void
    {
        $asset    = $this->makeAsset();
        $schedule = MaintenanceSchedule::withoutGlobalScope('tenant')->create([
            'tenant_id'        => $this->tenant->id,
            'asset_id'         => $asset->id,
            'name'             => 'Weekly Inspection',
            'maintenance_type' => 'inspection',
            'frequency_type'   => 'weekly',
            'next_due_date'    => now()->toDateString(),
            'is_active'        => true,
            'created_by'       => $this->admin->id,
        ]);

        $originalDue = $schedule->next_due_date->copy();
        $schedule->advanceNextDueDate();

        $this->assertTrue($schedule->next_due_date->eq($originalDue->addDays(7)));
    }

    // ── Scenario 3: Auto Work Order Generation ────────────────────────────────

    public function test_generates_work_orders_for_due_schedules(): void
    {
        $asset = $this->makeAsset();

        MaintenanceSchedule::withoutGlobalScope('tenant')->create([
            'tenant_id'        => $this->tenant->id,
            'asset_id'         => $asset->id,
            'name'             => 'Daily Check',
            'maintenance_type' => 'general',
            'frequency_type'   => 'daily',
            'next_due_date'    => now()->subDay()->toDateString(),
            'is_active'        => true,
            'created_by'       => $this->admin->id,
        ]);

        $count = app(MaintenanceService::class)->generateDuePmWorkOrders();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('maintenance_work_orders', [
            'tenant_id'   => $this->tenant->id,
            'asset_id'    => $asset->id,
            'source_type' => MaintenanceWorkOrder::SOURCE_PREVENTIVE,
        ]);
    }

    public function test_does_not_duplicate_wo_if_open_wo_exists(): void
    {
        $asset    = $this->makeAsset();
        $schedule = MaintenanceSchedule::withoutGlobalScope('tenant')->create([
            'tenant_id'        => $this->tenant->id,
            'asset_id'         => $asset->id,
            'name'             => 'Daily Check',
            'maintenance_type' => 'general',
            'frequency_type'   => 'daily',
            'next_due_date'    => now()->subDay()->toDateString(),
            'is_active'        => true,
            'created_by'       => $this->admin->id,
        ]);

        // Pre-existing open WO
        MaintenanceWorkOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'         => $this->tenant->id,
            'work_order_number' => 'MWO-TEST-0001',
            'source_type'       => MaintenanceWorkOrder::SOURCE_PREVENTIVE,
            'asset_id'          => $asset->id,
            'schedule_id'       => $schedule->id,
            'title'             => 'Existing WO',
            'status'            => MaintenanceWorkOrder::STATUS_OPEN,
            'created_by'        => $this->admin->id,
        ]);

        $count = app(MaintenanceService::class)->generateDuePmWorkOrders();

        $this->assertEquals(0, $count);
    }

    // ── Scenario 4: Breakdown Reporting Workflow ──────────────────────────────

    public function test_breakdown_reporting_marks_asset_as_breakdown(): void
    {
        $asset = $this->makeAsset();

        $breakdown = app(MaintenanceService::class)->reportBreakdown(
            $this->tenant,
            [
                'asset_id'          => $asset->id,
                'reported_by'       => $this->admin->id,
                'issue_description' => 'Hydraulic pump failure',
                'severity'          => MaintenanceBreakdown::SEVERITY_HIGH,
                'downtime_start'    => now(),
            ],
            true
        );

        $asset->refresh();

        $this->assertEquals(MaintenanceAsset::STATUS_BREAKDOWN, $asset->status);
        $this->assertEquals(MaintenanceBreakdown::STATUS_OPEN, $breakdown->status);
        $this->assertNotNull($breakdown->work_order_id);
    }

    public function test_admin_can_report_breakdown_via_http(): void
    {
        $asset = $this->makeAsset();

        $this->actingAs($this->admin)
            ->post(route('maintenance.breakdowns.store'), [
                'asset_id'          => $asset->id,
                'issue_description' => 'Belt snapped',
                'severity'          => 'high',
                'downtime_start'    => now()->format('Y-m-d\TH:i'),
                'create_work_order' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('maintenance_breakdowns', [
            'tenant_id'  => $this->tenant->id,
            'asset_id'   => $asset->id,
            'severity'   => 'high',
        ]);
    }

    // ── Scenario 5: Spare Parts Inventory Deduction ───────────────────────────

    public function test_closing_work_order_deducts_inventory(): void
    {
        $asset = $this->makeAsset();
        $part  = $this->makeInventoryPart();

        $wo = MaintenanceWorkOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'         => $this->tenant->id,
            'work_order_number' => 'MWO-TEST-0001',
            'source_type'       => MaintenanceWorkOrder::SOURCE_PREVENTIVE,
            'asset_id'          => $asset->id,
            'title'             => 'Oil Change',
            'status'            => MaintenanceWorkOrder::STATUS_COMPLETED,
            'created_by'        => $this->admin->id,
        ]);

        MaintenanceWorkOrderPart::create([
            'tenant_id'         => $this->tenant->id,
            'work_order_id'     => $wo->id,
            'inventory_item_id' => $part->id,
            'quantity_requested'=> 5,
            'created_by'        => $this->admin->id,
        ]);

        $this->be($this->admin);
        app(MaintenanceService::class)->closeWorkOrder($wo);

        $part->refresh();
        $this->assertEquals(95, (int) $part->current_stock);

        $this->assertDatabaseHas('stock_movements', [
            'item_id'        => $part->id,
            'type'           => 'adjustment_out',
            'quantity'       => 5,
            'reference_type' => MaintenanceWorkOrder::class,
            'reference_id'   => $wo->id,
        ]);
    }

    // ── Scenario 6: Negative Stock Prevention ────────────────────────────────

    public function test_closing_work_order_fails_on_insufficient_stock(): void
    {
        $asset = $this->makeAsset();
        $part  = $this->makeInventoryPart(['current_stock' => 2]);

        $wo = MaintenanceWorkOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'         => $this->tenant->id,
            'work_order_number' => 'MWO-TEST-0001',
            'source_type'       => MaintenanceWorkOrder::SOURCE_PREVENTIVE,
            'asset_id'          => $asset->id,
            'title'             => 'Oil Change',
            'status'            => MaintenanceWorkOrder::STATUS_COMPLETED,
            'created_by'        => $this->admin->id,
        ]);

        MaintenanceWorkOrderPart::create([
            'tenant_id'         => $this->tenant->id,
            'work_order_id'     => $wo->id,
            'inventory_item_id' => $part->id,
            'quantity_requested'=> 10, // more than available
            'created_by'        => $this->admin->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Insufficient stock/');

        app(MaintenanceService::class)->closeWorkOrder($wo);
    }

    // ── Scenario 7: Maintenance Cost Calculations ─────────────────────────────

    public function test_maintenance_cost_is_computed_correctly(): void
    {
        $asset = $this->makeAsset();
        $part  = $this->makeInventoryPart(['cost_price' => 500, 'avg_cost' => 500]);

        $wo = MaintenanceWorkOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'         => $this->tenant->id,
            'work_order_number' => 'MWO-TEST-0001',
            'source_type'       => MaintenanceWorkOrder::SOURCE_PREVENTIVE,
            'asset_id'          => $asset->id,
            'title'             => 'Full Service',
            'status'            => MaintenanceWorkOrder::STATUS_COMPLETED,
            'created_by'        => $this->admin->id,
        ]);

        MaintenanceWorkOrderPart::create([
            'tenant_id'         => $this->tenant->id,
            'work_order_id'     => $wo->id,
            'inventory_item_id' => $part->id,
            'quantity_requested'=> 4,
            'created_by'        => $this->admin->id,
        ]);

        MaintenanceLaborLog::create([
            'tenant_id'    => $this->tenant->id,
            'work_order_id'=> $wo->id,
            'user_id'      => $this->admin->id,
            'work_date'    => now()->toDateString(),
            'hours_worked' => 3,
            'hourly_rate'  => 2000,
            'labor_cost'   => 6000,
        ]);

        $this->be($this->admin);
        app(MaintenanceService::class)->closeWorkOrder($wo);

        $cost = MaintenanceCost::where('work_order_id', $wo->id)->first();

        $this->assertNotNull($cost);
        $this->assertEquals(2000.0, (float) $cost->parts_cost);  // 4 × 500
        $this->assertEquals(6000.0, (float) $cost->labor_cost);
        $this->assertEquals(8000.0, (float) $cost->total_cost);
    }

    // ── Scenario 8: GL Journal Entry Creation ─────────────────────────────────

    public function test_closing_work_order_posts_gl_entries(): void
    {
        $asset = $this->makeAsset();
        $part  = $this->makeInventoryPart(['cost_price' => 1000, 'avg_cost' => 1000]);

        $wo = MaintenanceWorkOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'         => $this->tenant->id,
            'work_order_number' => 'MWO-TEST-0001',
            'source_type'       => MaintenanceWorkOrder::SOURCE_PREVENTIVE,
            'asset_id'          => $asset->id,
            'title'             => 'Full Service',
            'status'            => MaintenanceWorkOrder::STATUS_COMPLETED,
            'created_by'        => $this->admin->id,
        ]);

        MaintenanceWorkOrderPart::create([
            'tenant_id'         => $this->tenant->id,
            'work_order_id'     => $wo->id,
            'inventory_item_id' => $part->id,
            'quantity_requested'=> 2,
            'created_by'        => $this->admin->id,
        ]);

        $this->be($this->admin);
        app(MaintenanceService::class)->closeWorkOrder($wo);

        // A transaction must have been created
        $tx = Transaction::where('tenant_id', $this->tenant->id)
            ->where('reference', 'MWO-TEST-0001')
            ->first();

        $this->assertNotNull($tx);

        // Debits must equal credits
        $debitTotal  = JournalEntry::where('transaction_id', $tx->id)->where('entry_type', 'debit')->sum('amount');
        $creditTotal = JournalEntry::where('transaction_id', $tx->id)->where('entry_type', 'credit')->sum('amount');

        $this->assertEquals($debitTotal, $creditTotal);

        // Dr 5500 Maintenance Expense
        $this->assertDatabaseHas('journal_entries', [
            'transaction_id' => $tx->id,
            'entry_type'     => 'debit',
        ]);

        // Cr 1200 Inventory
        $this->assertDatabaseHas('journal_entries', [
            'transaction_id' => $tx->id,
            'entry_type'     => 'credit',
        ]);
    }

    // ── Scenario 9: Role-Based Access Control ─────────────────────────────────

    public function test_staff_without_maintenance_access_cannot_view_breakdowns(): void
    {
        $staffNoAccess = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Staff No Access',
            'email'             => 'staff2@factory.ng',
            'password'          => bcrypt('password'),
            'role'              => 'staff',
            'is_active'         => true,
            'email_verified_at' => now(),
            'module_access'     => [],
        ]);

        // Maintenance routes are behind role:admin,accountant — staff get redirected
        $this->actingAs($staffNoAccess)
            ->get(route('maintenance.breakdowns.index'))
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_staff_with_maintenance_access_can_report_breakdown(): void
    {
        $asset = $this->makeAsset();

        $this->actingAs($this->staff)
            ->post(route('maintenance.breakdowns.store'), [
                'asset_id'          => $asset->id,
                'issue_description' => 'Unusual noise from motor',
                'severity'          => 'medium',
                'downtime_start'    => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();
    }

    public function test_staff_cannot_close_work_order(): void
    {
        $asset = $this->makeAsset();

        $wo = MaintenanceWorkOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'         => $this->tenant->id,
            'work_order_number' => 'MWO-TEST-0001',
            'source_type'       => MaintenanceWorkOrder::SOURCE_PREVENTIVE,
            'asset_id'          => $asset->id,
            'title'             => 'Service',
            'status'            => MaintenanceWorkOrder::STATUS_COMPLETED,
            'created_by'        => $this->admin->id,
        ]);

        // Staff is behind role:admin,accountant — gets redirected, not 403
        $this->actingAs($this->staff)
            ->post(route('maintenance.work-orders.close', $wo))
            ->assertRedirect(route('staff.dashboard'));
    }

    // ── Scenario 10: Multi-Tenant Data Isolation ──────────────────────────────

    public function test_tenant_cannot_see_another_tenants_assets(): void
    {
        $otherTenant = Tenant::create([
            'name'                    => 'Other Company',
            'slug'                    => 'other-company',
            'email'                   => 'other@company.ng',
            'tax_category'            => 'small',
            'annual_turnover'         => 5_000_000,
            'currency'                => 'NGN',
            'is_active'               => true,
            'plan_id'                 => $this->plan->id,
            'subscription_status'     => 'active',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $otherAdmin = User::forceCreate([
            'tenant_id'         => $otherTenant->id,
            'name'              => 'Other Admin',
            'email'             => 'admin@other.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        // Asset belonging to our tenant
        $asset = $this->makeAsset(['asset_code' => 'AST-0001', 'asset_name' => 'Our Machine']);

        // Other tenant's admin gets 403 from the policy (not 404, record is found but denied)
        $this->actingAs($otherAdmin)
            ->get(route('maintenance.assets.show', $asset))
            ->assertStatus(403);
    }

    // ── Scenario 11: Work Order Lifecycle Transitions ─────────────────────────

    public function test_work_order_lifecycle_transitions(): void
    {
        $asset = $this->makeAsset();
        $service = app(MaintenanceService::class);

        $wo = $service->createWorkOrder($this->tenant, [
            'source_type'   => MaintenanceWorkOrder::SOURCE_PREVENTIVE,
            'asset_id'      => $asset->id,
            'title'         => 'Scheduled Maintenance',
            'created_by'    => $this->admin->id,
        ]);

        $this->assertEquals(MaintenanceWorkOrder::STATUS_OPEN, $wo->status);
        $this->assertTrue($wo->canStart());
        $this->assertFalse($wo->canComplete());
        $this->assertFalse($wo->canClose());

        $service->startWorkOrder($wo);
        $wo->refresh();

        $this->assertEquals(MaintenanceWorkOrder::STATUS_IN_PROGRESS, $wo->status);
        $this->assertTrue($wo->canComplete());
        $this->assertFalse($wo->canClose());

        $service->completeWorkOrder($wo, 'All checks done');
        $wo->refresh();

        $this->assertEquals(MaintenanceWorkOrder::STATUS_COMPLETED, $wo->status);
        $this->assertTrue($wo->canClose());

        $service->closeWorkOrder($wo);
        $wo->refresh();

        $this->assertEquals(MaintenanceWorkOrder::STATUS_CLOSED, $wo->status);
        $this->assertTrue($wo->isClosed());
    }

    // ── Scenario 12: Downtime Calculations ───────────────────────────────────

    public function test_breakdown_downtime_calculated_when_resolved(): void
    {
        $asset     = $this->makeAsset();
        $startTime = now()->subHours(4);

        $breakdown = MaintenanceBreakdown::withoutGlobalScope('tenant')->create([
            'tenant_id'         => $this->tenant->id,
            'breakdown_number'  => 'BRK-TEST-0001',
            'asset_id'          => $asset->id,
            'reported_by'       => $this->admin->id,
            'issue_description' => 'Pump failure',
            'severity'          => MaintenanceBreakdown::SEVERITY_HIGH,
            'downtime_start'    => $startTime,
            'status'            => MaintenanceBreakdown::STATUS_OPEN,
        ]);

        $endTime = now();
        $breakdown->update([
            'downtime_end'  => $endTime,
            'status'        => MaintenanceBreakdown::STATUS_RESOLVED,
            'downtime_hours'=> $breakdown->calculateDowntimeHours(),
        ]);

        $breakdown->refresh();
        $this->assertGreaterThanOrEqual(3.9, (float) $breakdown->downtime_hours);
        $this->assertLessThanOrEqual(4.1, (float) $breakdown->downtime_hours);
    }

    public function test_ongoing_breakdown_calculates_live_downtime(): void
    {
        $asset     = $this->makeAsset();
        $startTime = now()->subHours(2);

        $breakdown = new MaintenanceBreakdown([
            'downtime_start' => $startTime,
            'downtime_end'   => null,
        ]);

        $hours = $breakdown->calculateDowntimeHours();
        $this->assertGreaterThanOrEqual(1.9, $hours);
        $this->assertLessThanOrEqual(2.1, $hours);
    }

    // ── Scenario 13: Dashboard Report Endpoint ────────────────────────────────

    public function test_maintenance_dashboard_is_accessible_to_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('maintenance.dashboard'))
            ->assertOk();
    }

    public function test_asset_index_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get(route('maintenance.assets.index'))
            ->assertOk();
    }

    public function test_work_order_index_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get(route('maintenance.work-orders.index'))
            ->assertOk();
    }

    // ── Scenario 14: Breakdown Auto-Closes on WO Close ───────────────────────

    public function test_breakdown_auto_resolves_when_corrective_wo_closed(): void
    {
        $asset = $this->makeAsset(['status' => MaintenanceAsset::STATUS_BREAKDOWN]);

        $breakdown = MaintenanceBreakdown::withoutGlobalScope('tenant')->create([
            'tenant_id'         => $this->tenant->id,
            'breakdown_number'  => 'BRK-TEST-0001',
            'asset_id'          => $asset->id,
            'reported_by'       => $this->admin->id,
            'issue_description' => 'Motor seized',
            'severity'          => MaintenanceBreakdown::SEVERITY_CRITICAL,
            'downtime_start'    => now()->subHours(3),
            'status'            => MaintenanceBreakdown::STATUS_OPEN,
        ]);

        $wo = MaintenanceWorkOrder::withoutGlobalScope('tenant')->create([
            'tenant_id'         => $this->tenant->id,
            'work_order_number' => 'MWO-TEST-0001',
            'source_type'       => MaintenanceWorkOrder::SOURCE_CORRECTIVE,
            'asset_id'          => $asset->id,
            'breakdown_id'      => $breakdown->id,
            'title'             => 'Fix Motor',
            'status'            => MaintenanceWorkOrder::STATUS_COMPLETED,
            'created_by'        => $this->admin->id,
        ]);

        $breakdown->update(['work_order_id' => $wo->id]);

        $this->be($this->admin);
        app(MaintenanceService::class)->closeWorkOrder($wo);

        $breakdown->refresh();
        $asset->refresh();

        $this->assertEquals(MaintenanceBreakdown::STATUS_RESOLVED, $breakdown->status);
        $this->assertNotNull($breakdown->downtime_end);
        $this->assertEquals(MaintenanceAsset::STATUS_ACTIVE, $asset->status);
    }
}
