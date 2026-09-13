<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\ReportService;
use App\Services\VatService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatReturnCalculationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Customer $customer;
    private VatService $vatService;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name'          => 'Business',
            'slug'          => 'business',
            'price_monthly' => 25000,
            'limits'        => [],
            'is_active'     => true,
            'is_public'     => true,
        ]);

        $this->tenant = Tenant::create([
            'name'                    => 'Test Trading Co',
            'slug'                    => 'test-trading-co-vat',
            'email'                   => 'vat@testtrading.ng',
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
            'name'              => 'Admin User',
            'email'             => 'admin@testtrading-vat.ng',
            'password'          => bcrypt('password'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->customer = Customer::withoutGlobalScope('tenant')->create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Jane Buyer',
            'is_company' => false,
            'is_active'  => true,
        ]);

        $this->vatService = app(VatService::class);
    }

    private function makeInvoice(string $status, float $subtotal, float $vat, float $amountPaid): Invoice
    {
        $date  = now()->startOfMonth()->addDays(2);
        $total = $subtotal + $vat;

        return Invoice::create([
            'tenant_id'      => $this->tenant->id,
            'customer_id'    => $this->customer->id,
            'invoice_number' => 'INV-' . uniqid(),
            'invoice_date'   => $date,
            'due_date'       => $date->copy()->addDays(14),
            'subtotal'       => $subtotal,
            'vat_amount'     => $vat,
            'total_amount'   => $total,
            'amount_paid'    => $amountPaid,
            'balance_due'    => $total - $amountPaid,
            'vat_applicable' => true,
            'status'         => $status,
            'currency'       => 'NGN',
            'created_by'     => $this->admin->id,
        ]);
    }

    public function test_partial_invoice_reduces_computed_output_vat(): void
    {
        $this->makeInvoice('paid', 100_000, 7_500, 107_500);   // fully paid
        $this->makeInvoice('partial', 100_000, 7_500, 20_000); // only 20k of 107.5k paid

        $data = $this->vatService->computeMonthlyReturn($this->tenant, now()->year, now()->month);

        $expected = round(7_500 + (7_500 * (20_000 / 107_500)), 2);
        $this->assertEquals($expected, $data['output_vat']);
        $this->assertLessThan(15_000, $data['output_vat']); // less than the full sum of both invoices
    }

    public function test_recalculating_a_pending_return_updates_figures_and_writes_audit_log(): void
    {
        $this->actingAs($this->admin);

        $invoice = $this->makeInvoice('sent', 100_000, 7_500, 0);
        $year = now()->year;
        $month = now()->month;

        $return = $this->vatService->createOrUpdateReturn($this->tenant, $year, $month);
        $this->assertEquals(0.0, (float) $return->output_vat);

        // Payment comes in later — recalculating should pick it up.
        $invoice->update(['amount_paid' => 107_500, 'balance_due' => 0, 'status' => 'paid']);

        $return = $this->vatService->createOrUpdateReturn($this->tenant, $year, $month);
        $this->assertEquals(7_500.0, (float) $return->output_vat);

        $this->assertDatabaseHas('audit_logs', [
            'event'         => 'vat_return.recalculated',
            'auditable_id'  => $return->id,
        ]);
    }

    public function test_recalculating_with_no_change_does_not_write_a_new_audit_log(): void
    {
        $this->actingAs($this->admin);

        $this->makeInvoice('paid', 100_000, 7_500, 107_500);
        $year = now()->year;
        $month = now()->month;

        $this->vatService->createOrUpdateReturn($this->tenant, $year, $month);
        $countAfterFirst = AuditLog::count();

        // Nothing changed since the first compute — recalculating again should be a no-op for auditing.
        $this->vatService->createOrUpdateReturn($this->tenant, $year, $month);
        $this->assertEquals($countAfterFirst, AuditLog::count());
    }

    public function test_recalculating_a_filed_return_does_not_change_its_figures(): void
    {
        $this->actingAs($this->admin);

        $this->makeInvoice('paid', 100_000, 7_500, 107_500);
        $year = now()->year;
        $month = now()->month;

        $return = $this->vatService->createOrUpdateReturn($this->tenant, $year, $month);
        $return->update(['status' => 'filed', 'filing_reference' => 'NRS-001', 'filed_date' => now()]);

        // A new qualifying invoice appears after filing — recompute must not pick it up.
        $this->makeInvoice('paid', 200_000, 15_000, 215_000);

        $recomputed = $this->vatService->createOrUpdateReturn($this->tenant, $year, $month);

        $this->assertEquals(7_500.00, (float) $recomputed->output_vat);
        $this->assertEquals('filed', $recomputed->status);
        $this->assertEquals('NRS-001', $recomputed->filing_reference);
    }

    public function test_recalculating_a_paid_return_does_not_change_its_figures(): void
    {
        $this->actingAs($this->admin);

        $this->makeInvoice('paid', 100_000, 7_500, 107_500);
        $year = now()->year;
        $month = now()->month;

        $return = $this->vatService->createOrUpdateReturn($this->tenant, $year, $month);
        $return->update(['status' => 'paid', 'paid_date' => now(), 'amount_paid' => $return->net_vat_payable]);

        $this->makeInvoice('paid', 200_000, 15_000, 215_000);

        $recomputed = $this->vatService->createOrUpdateReturn($this->tenant, $year, $month);
        $this->assertEquals(7_500.00, (float) $recomputed->output_vat);
        $this->assertEquals('paid', $recomputed->status);
    }

    public function test_recalculate_route_updates_a_pending_return_after_new_payment(): void
    {
        $invoice = $this->makeInvoice('sent', 100_000, 7_500, 0);
        $year = now()->year;
        $month = now()->month;

        $this->actingAs($this->admin)
            ->get(route('tax.vat.compute', ['year' => $year, 'month' => $month]))
            ->assertOk();

        $return = VatReturn::where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertEquals(0.0, (float) $return->output_vat);

        $invoice->update(['amount_paid' => 107_500, 'balance_due' => 0, 'status' => 'paid']);

        $this->actingAs($this->admin)
            ->get(route('tax.vat.compute', ['year' => $year, 'month' => $month]))
            ->assertOk();

        $this->assertEquals(7_500.0, (float) $return->fresh()->output_vat);
    }

    public function test_vat_index_shows_recalculate_link_for_pending_return(): void
    {
        $this->makeInvoice('sent', 100_000, 7_500, 0);
        $this->vatService->createOrUpdateReturn($this->tenant, now()->year, now()->month);

        $this->actingAs($this->admin)
            ->get(route('tax.vat.index'))
            ->assertOk()
            ->assertSee('Recalculate');
    }

    public function test_compliance_dashboard_vat_matches_vat_service_for_same_period(): void
    {
        $this->makeInvoice('partial', 100_000, 7_500, 20_000);

        $vatData   = $this->vatService->computeMonthlyReturn($this->tenant, now()->year, now()->month);
        $dashboard = app(ReportService::class)->getComplianceDashboard($this->tenant, now()->year);

        // Same tenant/period, only invoice falls in the current month — figures must agree.
        $this->assertEquals($vatData['output_vat'], $dashboard['vat']['output_total']);
    }

    // ── Amend a filed VAT return ────────────────────────────────────────────

    private function makeFiledReturn(): VatReturn
    {
        $this->makeInvoice('paid', 100_000, 7_500, 107_500);

        $return = $this->vatService->createOrUpdateReturn($this->tenant, now()->year, now()->month);
        $return->update([
            'status'           => 'filed',
            'filing_reference' => 'NRS-ORIGINAL',
            'filed_date'       => now(),
            'filed_by'         => $this->admin->id,
        ]);

        return $return->fresh();
    }

    public function test_amending_a_filed_return_recomputes_resets_and_audits(): void
    {
        $return = $this->makeFiledReturn();

        // A new qualifying invoice appears after filing — this is exactly
        // what the amendment should now pick up.
        $this->makeInvoice('paid', 200_000, 15_000, 215_000);

        $this->actingAs($this->admin)
            ->post(route('tax.vat.amend', $return), ['reason' => 'Late invoice discovered'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $return->refresh();
        $this->assertEquals('pending', $return->status);
        $this->assertNull($return->filing_reference);
        $this->assertNull($return->filed_date);
        $this->assertNull($return->filed_by);
        $this->assertEquals(22_500.0, (float) $return->output_vat); // 7,500 + 15,000
        $this->assertStringContainsString('Late invoice discovered', $return->notes);

        $this->assertDatabaseHas('audit_logs', [
            'event'        => 'vat_return.amended',
            'auditable_id' => $return->id,
        ]);
    }

    public function test_amending_a_paid_return_is_rejected(): void
    {
        $return = $this->makeFiledReturn();
        $return->update(['status' => 'paid', 'paid_date' => now(), 'amount_paid' => $return->net_vat_payable]);

        $this->actingAs($this->admin)
            ->post(route('tax.vat.amend', $return), ['reason' => 'Trying anyway'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $return->refresh();
        $this->assertEquals('paid', $return->status);
        $this->assertEquals('NRS-ORIGINAL', $return->filing_reference);
    }

    public function test_amending_a_pending_return_is_rejected(): void
    {
        $this->makeInvoice('partial', 100_000, 7_500, 20_000);
        $return = $this->vatService->createOrUpdateReturn($this->tenant, now()->year, now()->month);

        $this->actingAs($this->admin)
            ->post(route('tax.vat.amend', $return), ['reason' => 'Nothing filed yet'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals('pending', $return->fresh()->status);
    }

    public function test_amend_requires_a_reason(): void
    {
        $return = $this->makeFiledReturn();

        $this->actingAs($this->admin)
            ->post(route('tax.vat.amend', $return), [])
            ->assertSessionHasErrors('reason');

        $this->assertEquals('filed', $return->fresh()->status);
    }

    public function test_vat_index_renders_amend_control_for_filed_and_note_for_paid(): void
    {
        $filed = $this->makeFiledReturn();

        $this->actingAs($this->admin)
            ->get(route('tax.vat.index'))
            ->assertOk()
            ->assertSee('Amend Return');

        $filed->update(['status' => 'paid', 'paid_date' => now(), 'amount_paid' => $filed->net_vat_payable]);

        $this->actingAs($this->admin)
            ->get(route('tax.vat.index'))
            ->assertOk()
            ->assertSee('Contact support to amend a paid return');
    }

    public function test_an_amended_return_can_be_refiled_normally(): void
    {
        $return = $this->makeFiledReturn();

        $this->actingAs($this->admin)
            ->post(route('tax.vat.amend', $return), ['reason' => 'Correction'])
            ->assertRedirect();

        $return->refresh();

        $this->actingAs($this->admin)
            ->post(route('tax.vat.filed', $return), [
                'filed_date'       => now()->toDateString(),
                'filing_reference' => 'NRS-AMENDED-001',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $return->refresh();
        $this->assertEquals('filed', $return->status);
        $this->assertEquals('NRS-AMENDED-001', $return->filing_reference);
    }
}
