<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Plan;
use App\Models\RestockRequest;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BookkeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestockLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant      $tenant;
    private User        $admin;
    private User        $accountant;
    private InventoryItem $item;
    private BankAccount $bankAccount;

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

        $this->accountant = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Test Accountant',
            'email'             => 'accountant@test.ng',
            'password'          => bcrypt('password'),
            'role'              => 'accountant',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($this->tenant);

        $this->item = InventoryItem::withoutGlobalScope('tenant')->create([
            'tenant_id'     => $this->tenant->id,
            'name'          => 'Cement Bag',
            'sku'           => 'CEM-001',
            'item_type'     => 'product',
            'unit'          => 'bag',
            'selling_price' => 5000,
            'cost_price'    => 4000,
            'avg_cost'      => 0,
            'current_stock' => 0,
            'restock_level' => 10,
            'is_active'     => true,
            'created_by'    => $this->admin->id,
        ]);

        // Bank GL account in the 1004–1099 range used by BankAccount
        $bankGl = Account::create([
            'tenant_id' => $this->tenant->id,
            'code'      => '1004',
            'name'      => 'Access Bank',
            'type'      => 'asset',
            'sub_type'  => 'bank',
            'is_active' => true,
        ]);

        $this->bankAccount = BankAccount::create([
            'tenant_id'      => $this->tenant->id,
            'name'           => 'Access Bank - Main',
            'bank_name'      => 'Access Bank',
            'account_number' => '0123456789',
            'account_type'   => 'current',
            'currency'       => 'NGN',
            'gl_account_id'  => $bankGl->id,
            'is_active'      => true,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_submit_restock_request(): void
    {
        $this->actingAs($this->admin)
            ->post(route('inventory.restock.store'), [
                'item_id'            => $this->item->id,
                'quantity_requested' => 50,
                'unit_cost'          => 4000,
                'supplier_name'      => 'ABC Supplies Ltd',
                'notes'              => 'Urgent restock needed',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('restock_requests', [
            'tenant_id'          => $this->tenant->id,
            'item_id'            => $this->item->id,
            'quantity_requested' => 50,
            'status'             => RestockRequest::STATUS_PENDING,
            'requested_by'       => $this->admin->id,
        ]);
    }

    public function test_staff_cannot_access_restock_routes(): void
    {
        $staff = User::forceCreate([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Warehouse Staff',
            'email'             => 'staff@test.ng',
            'password'          => bcrypt('password'),
            'role'              => 'staff',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('inventory.restock.index'))
            ->assertRedirect(route('staff.dashboard'));
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function test_admin_can_approve_pending_request(): void
    {
        $rr = RestockRequest::withoutGlobalScope('tenant')->create([
            'tenant_id'          => $this->tenant->id,
            'item_id'            => $this->item->id,
            'request_number'     => 'RST-' . now()->format('Ym') . '-0001',
            'quantity_requested' => 20,
            'unit_cost'          => 4000,
            'status'             => RestockRequest::STATUS_PENDING,
            'requested_by'       => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventory.restock.approve', $rr))
            ->assertRedirect();

        $this->assertDatabaseHas('restock_requests', [
            'id'          => $rr->id,
            'status'      => RestockRequest::STATUS_APPROVED,
            'approved_by' => $this->admin->id,
        ]);
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function test_admin_can_reject_pending_request_with_reason(): void
    {
        $rr = RestockRequest::withoutGlobalScope('tenant')->create([
            'tenant_id'          => $this->tenant->id,
            'item_id'            => $this->item->id,
            'request_number'     => 'RST-' . now()->format('Ym') . '-0002',
            'quantity_requested' => 20,
            'unit_cost'          => 4000,
            'status'             => RestockRequest::STATUS_PENDING,
            'requested_by'       => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventory.restock.reject', $rr), [
                'rejection_reason' => 'Budget not approved this quarter.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('restock_requests', [
            'id'               => $rr->id,
            'status'           => RestockRequest::STATUS_REJECTED,
            'rejection_reason' => 'Budget not approved this quarter.',
        ]);
    }

    // ── Receive ───────────────────────────────────────────────────────────────

    public function test_receiving_stock_updates_inventory_and_creates_supplier_bill(): void
    {
        $rr = RestockRequest::withoutGlobalScope('tenant')->create([
            'tenant_id'          => $this->tenant->id,
            'item_id'            => $this->item->id,
            'request_number'     => 'RST-' . now()->format('Ym') . '-0001',
            'quantity_requested' => 50,
            'unit_cost'          => 4000,
            'supplier_name'      => 'ABC Supplies',
            'status'             => RestockRequest::STATUS_APPROVED,
            'requested_by'       => $this->admin->id,
            'approved_by'        => $this->admin->id,
            'approved_at'        => now(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventory.restock.receive', $rr), [
                'quantity_received'   => 50,
                'unit_cost'           => 4000,
                'supplier_invoice_no' => 'SI-2026-001',
            ])
            ->assertRedirect();

        // Stock updated on the item
        $this->item->refresh();
        $this->assertEquals(50.0, (float) $this->item->current_stock);
        $this->assertEquals(4000.0, (float) $this->item->avg_cost);

        // Supplier bill created (invoice_number starts with BILL-)
        $this->assertDatabaseHas('invoices', [
            'tenant_id'    => $this->tenant->id,
            'customer_id'  => null,
            'total_amount' => 200000,
            'balance_due'  => 200000,
            'status'       => 'sent',
        ]);

        // Restock marked received and linked to bill
        $this->assertDatabaseHas('restock_requests', [
            'id'     => $rr->id,
            'status' => RestockRequest::STATUS_RECEIVED,
        ]);
        $rr->refresh();
        $this->assertNotNull($rr->invoice_id);

        // GL: Dr Inventory 1200, Cr AP 2001 — balanced at ₦200,000
        $tx = Transaction::where('tenant_id', $this->tenant->id)
            ->where('type', 'purchase')
            ->first();
        $this->assertNotNull($tx);
        $this->assertEquals(200000.0, (float) $tx->amount);

        $entries = JournalEntry::where('transaction_id', $tx->id)->get();
        $this->assertEquals(200000.0, $entries->where('entry_type', 'debit')->sum('amount'));
        $this->assertEquals(200000.0, $entries->where('entry_type', 'credit')->sum('amount'));
    }

    // ── Pay ───────────────────────────────────────────────────────────────────

    public function test_accountant_can_record_full_supplier_bill_payment(): void
    {
        $bill = Invoice::create([
            'tenant_id'      => $this->tenant->id,
            'customer_id'    => null,
            'invoice_number' => 'BILL-' . now()->format('Ym') . '-0001',
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'subtotal'       => 200000,
            'vat_amount'     => 0,
            'discount_amount'=> 0,
            'total_amount'   => 200000,
            'amount_paid'    => 0,
            'balance_due'    => 200000,
            'vat_applicable' => false,
            'status'         => 'sent',
            'is_b2c'         => false,
            'currency'       => 'NGN',
            'created_by'     => $this->admin->id,
        ]);

        $rr = RestockRequest::withoutGlobalScope('tenant')->create([
            'tenant_id'          => $this->tenant->id,
            'item_id'            => $this->item->id,
            'request_number'     => 'RST-' . now()->format('Ym') . '-0001',
            'quantity_requested' => 50,
            'quantity_received'  => 50,
            'unit_cost'          => 4000,
            'supplier_name'      => 'ABC Supplies',
            'status'             => RestockRequest::STATUS_RECEIVED,
            'requested_by'       => $this->admin->id,
            'approved_by'        => $this->admin->id,
            'approved_at'        => now(),
            'received_at'        => now(),
            'invoice_id'         => $bill->id,
        ]);

        $this->actingAs($this->accountant)
            ->post(route('inventory.restock.pay', $rr), [
                'amount'          => 200000,
                'bank_account_id' => $this->bankAccount->id,
                'payment_date'    => now()->toDateString(),
                'method'          => 'bank_transfer',
                'reference'       => 'TRF-001',
                'notes'           => 'Full payment',
            ])
            ->assertRedirect();

        // Bill fully paid
        $bill->refresh();
        $this->assertEquals('paid', $bill->status);
        $this->assertEquals(200000.0, (float) $bill->amount_paid);
        $this->assertEquals(0.0, (float) $bill->balance_due);

        // GL: Dr AP 2001, Cr Bank 1004 — balanced at ₦200,000
        $paymentTx = Transaction::where('tenant_id', $this->tenant->id)
            ->where('type', 'payment')
            ->first();
        $this->assertNotNull($paymentTx);

        $entries = JournalEntry::where('transaction_id', $paymentTx->id)->get();
        $this->assertEquals(200000.0, $entries->where('entry_type', 'debit')->sum('amount'));
        $this->assertEquals(200000.0, $entries->where('entry_type', 'credit')->sum('amount'));
    }

    public function test_partial_payment_leaves_bill_in_partial_status(): void
    {
        $bill = Invoice::create([
            'tenant_id'      => $this->tenant->id,
            'customer_id'    => null,
            'invoice_number' => 'BILL-' . now()->format('Ym') . '-0002',
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'subtotal'       => 200000,
            'vat_amount'     => 0,
            'discount_amount'=> 0,
            'total_amount'   => 200000,
            'amount_paid'    => 0,
            'balance_due'    => 200000,
            'vat_applicable' => false,
            'status'         => 'sent',
            'is_b2c'         => false,
            'currency'       => 'NGN',
            'created_by'     => $this->admin->id,
        ]);

        $rr = RestockRequest::withoutGlobalScope('tenant')->create([
            'tenant_id'          => $this->tenant->id,
            'item_id'            => $this->item->id,
            'request_number'     => 'RST-' . now()->format('Ym') . '-0002',
            'quantity_requested' => 50,
            'quantity_received'  => 50,
            'unit_cost'          => 4000,
            'status'             => RestockRequest::STATUS_RECEIVED,
            'requested_by'       => $this->admin->id,
            'approved_by'        => $this->admin->id,
            'received_at'        => now(),
            'invoice_id'         => $bill->id,
        ]);

        $this->actingAs($this->accountant)
            ->post(route('inventory.restock.pay', $rr), [
                'amount'          => 100000,
                'bank_account_id' => $this->bankAccount->id,
                'payment_date'    => now()->toDateString(),
                'method'          => 'cheque',
            ])
            ->assertRedirect();

        $bill->refresh();
        $this->assertEquals('partial', $bill->status);
        $this->assertEquals(100000.0, (float) $bill->amount_paid);
        $this->assertEquals(100000.0, (float) $bill->balance_due);
    }

    public function test_cannot_pay_supplier_bill_when_status_is_not_received(): void
    {
        $rr = RestockRequest::withoutGlobalScope('tenant')->create([
            'tenant_id'          => $this->tenant->id,
            'item_id'            => $this->item->id,
            'request_number'     => 'RST-' . now()->format('Ym') . '-0003',
            'quantity_requested' => 50,
            'unit_cost'          => 4000,
            'status'             => RestockRequest::STATUS_APPROVED,
            'requested_by'       => $this->admin->id,
            'approved_by'        => $this->admin->id,
        ]);

        $this->actingAs($this->accountant)
            ->post(route('inventory.restock.pay', $rr), [
                'amount'          => 200000,
                'bank_account_id' => $this->bankAccount->id,
                'payment_date'    => now()->toDateString(),
                'method'          => 'bank_transfer',
            ])
            ->assertForbidden();
    }
}
