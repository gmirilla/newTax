<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Plan;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BookkeepingService;
use App\Services\FIRS\UblPayloadBuilder;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'slug' => 'disc-' . uniqid(), 'name' => 'Disc', 'price_monthly' => 0,
            'is_active' => true, 'limits' => [],
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Disc Co', 'slug' => 'disc-co-' . uniqid(), 'email' => 'disc@test.ng',
            'tax_category' => 'small', 'annual_turnover' => 1_000_000, 'currency' => 'NGN',
            'is_active' => true, 'plan_id' => $plan->id,
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(),
        ]);

        app(BookkeepingService::class)->provisionDefaultAccounts($this->tenant);

        $this->admin    = $this->makeUser('admin');
        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Cust', 'is_company' => true, 'is_active' => true,
        ]);
    }

    private function makeUser(string $role): User
    {
        return User::forceCreate([
            'tenant_id' => $this->tenant->id, 'name' => ucfirst($role), 'email' => $role . uniqid() . '@test.ng',
            'password' => bcrypt('password'), 'role' => $role, 'is_active' => true, 'email_verified_at' => now(),
        ]);
    }

    private function invoicePayload(array $extra = []): array
    {
        return array_merge([
            'customer_id'    => $this->customer->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'vat_applicable' => '1',
            'items'          => [['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100_000, 'vat_applicable' => '1']],
        ], $extra);
    }

    private function quotePayload(array $extra = []): array
    {
        return array_merge([
            'customer_id'    => $this->customer->id,
            'quote_date'     => now()->toDateString(),
            'expiry_date'    => now()->addDays(30)->toDateString(),
            'vat_applicable' => '1',
            'items'          => [['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100_000]],
        ], $extra);
    }

    // ── Invoices ─────────────────────────────────────────────────────────────

    public function test_invoice_can_be_created_with_a_percentage_discount(): void
    {
        $this->actingAs($this->admin)
            ->post(route('invoices.store'), $this->invoicePayload(['discount_type' => 'percent', 'discount_value' => 10]))
            ->assertSessionDoesntHaveErrors();

        $invoice = Invoice::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->assertSame('percent', $invoice->discount_type);
        $this->assertEquals(10_000, $invoice->discount_amount);
        $this->assertEquals(6_750, $invoice->vat_amount);       // VAT on the discounted 90,000
        $this->assertEquals(96_750, $invoice->total_amount);
    }

    public function test_invoice_can_be_created_with_a_fixed_discount(): void
    {
        $this->actingAs($this->admin)
            ->post(route('invoices.store'), $this->invoicePayload(['discount_type' => 'fixed', 'discount_value' => 5_000]));

        $invoice = Invoice::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->assertEquals(5_000, $invoice->discount_amount);
        $this->assertEquals(7_125, $invoice->vat_amount);       // 7.5% of 95,000
        $this->assertEquals(102_125, $invoice->total_amount);
    }

    public function test_invoice_without_a_discount_is_unchanged(): void
    {
        $this->actingAs($this->admin)->post(route('invoices.store'), $this->invoicePayload());

        $invoice = Invoice::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->assertEquals(0, $invoice->discount_amount);
        $this->assertEquals(107_500, $invoice->total_amount);
    }

    public function test_invalid_discounts_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('invoices.store'), $this->invoicePayload(['discount_type' => 'percent', 'discount_value' => 150]))
            ->assertSessionHasErrors('discount_value');

        $this->actingAs($this->admin)
            ->post(route('invoices.store'), $this->invoicePayload(['discount_type' => 'fixed', 'discount_value' => -5]))
            ->assertSessionHasErrors('discount_value');

        $this->actingAs($this->admin)
            ->post(route('invoices.store'), $this->invoicePayload(['discount_type' => 'bogus', 'discount_value' => 5]))
            ->assertSessionHasErrors('discount_type');

        $this->assertSame(0, Invoice::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_staff_cannot_create_a_discounted_invoice(): void
    {
        $this->actingAs($this->makeUser('staff'))
            ->post(route('invoices.store'), $this->invoicePayload(['discount_type' => 'percent', 'discount_value' => 10]))
            ->assertRedirect(); // staff are bounced by the route's role middleware

        $this->assertSame(0, Invoice::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_editing_an_invoice_applies_a_new_discount_and_keeps_vat(): void
    {
        $this->actingAs($this->admin);
        $invoice = app(InvoiceService::class)->create($this->tenant, [
            'customer_id' => $this->customer->id, 'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(), 'vat_applicable' => true,
        ], [['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100_000, 'vat_applicable' => true]]);

        $this->put(route('invoices.update', $invoice), $this->invoicePayload([
            'discount_type' => 'percent', 'discount_value' => 20,
        ]))->assertSessionDoesntHaveErrors();

        $invoice->refresh();
        $this->assertEquals(20_000, $invoice->discount_amount);
        $this->assertEquals(6_000, $invoice->vat_amount);       // edit must not zero the VAT
        $this->assertEquals(86_000, $invoice->total_amount);
    }

    public function test_discounted_invoice_journal_balances_and_posts_net_revenue(): void
    {
        $this->actingAs($this->admin);
        $invoice = app(InvoiceService::class)->create($this->tenant, [
            'customer_id' => $this->customer->id, 'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(), 'vat_applicable' => true,
            'wht_applicable' => true, 'wht_rate' => 5,
            'discount_type' => 'percent', 'discount_value' => 10,
        ], [['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100_000, 'vat_applicable' => true]]);

        app(InvoiceService::class)->postRevenueJournal($invoice);
        $invoice->refresh();

        // net 90,000 + VAT 6,750 - WHT 4,500
        $this->assertEquals(92_250, $invoice->total_amount);
        $this->assertNotNull($invoice->transaction_id);

        $entries = JournalEntry::withoutGlobalScopes()->where('transaction_id', $invoice->transaction_id)->get();
        $this->assertNotEmpty($entries);
        $this->assertEquals(
            round($entries->where('entry_type', 'debit')->sum('amount'), 2),
            round($entries->where('entry_type', 'credit')->sum('amount'), 2),
        );
    }

    public function test_legacy_invoice_keeps_its_old_totals_until_edited(): void
    {
        // A pre-feature row: discount_type NULL, flat discount, VAT on the gross subtotal
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id, 'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-LEGACY-1', 'invoice_date' => now(), 'due_date' => now()->addDays(30),
            'discount_amount' => 10_000, 'vat_applicable' => true, 'status' => 'draft', 'currency' => 'NGN',
            'created_by' => $this->admin->id,
        ]);
        $invoice->items()->create([
            'description' => 'Old', 'quantity' => 1, 'unit_price' => 100_000, 'subtotal' => 100_000,
            'vat_applicable' => true, 'vat_rate' => 7.5, 'vat_amount' => 7_500, 'total' => 107_500, 'sort_order' => 1,
        ]);

        $invoice->load('items')->recalculateTotals();

        $this->assertNull($invoice->discount_type);
        $this->assertEquals(7_500, $invoice->vat_amount);
        $this->assertEquals(97_500, $invoice->total_amount);
    }

    public function test_ubl_payload_uses_the_discounted_tax_base(): void
    {
        $this->actingAs($this->admin);
        $invoice = app(InvoiceService::class)->create($this->tenant, [
            'customer_id' => $this->customer->id, 'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(), 'vat_applicable' => true,
            'discount_type' => 'fixed', 'discount_value' => 10_000,
        ], [['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100_000, 'vat_applicable' => true]]);

        $payload = app(UblPayloadBuilder::class)->build($invoice, 'IRN-TEST');

        $this->assertSame(10_000.0, $payload['LegalMonetaryTotal']['AllowanceTotalAmount']);
        $this->assertSame(90_000.0, $payload['LegalMonetaryTotal']['TaxExclusiveAmount']);
        $this->assertSame(96_750.0, $payload['LegalMonetaryTotal']['TaxInclusiveAmount']);
        $this->assertSame(90_000.0, $payload['TaxTotal'][0]['TaxSubtotal'][0]['TaxableAmount']);
        $this->assertSame(6_750.0, $payload['TaxTotal'][0]['TaxAmount']);
    }

    public function test_discount_label_appears_on_invoice_pages(): void
    {
        $this->actingAs($this->admin)
            ->post(route('invoices.store'), $this->invoicePayload(['discount_type' => 'percent', 'discount_value' => 10]));
        $invoice = Invoice::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->actingAs($this->admin)->get(route('invoices.show', $invoice))->assertOk()->assertSee('Discount (10%)');
        $this->actingAs($this->admin)->get(route('invoices.edit', $invoice))->assertOk()->assertSee('discount_value', false);
        $this->actingAs($this->admin)->get(route('invoices.create'))->assertOk()->assertSee('name="discount_type"', false);
    }

    // ── Quotes ───────────────────────────────────────────────────────────────

    public function test_quote_can_be_created_with_a_percentage_discount(): void
    {
        $this->actingAs($this->admin)
            ->post(route('quotes.store'), $this->quotePayload(['discount_type' => 'percent', 'discount_value' => 10]))
            ->assertSessionDoesntHaveErrors();

        $quote = Quote::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->assertSame('percent', $quote->discount_type);
        $this->assertEquals(10_000, $quote->discount_amount);
        $this->assertEquals(6_750, $quote->vat_amount);
        $this->assertEquals(96_750, $quote->total_amount);
    }

    public function test_quote_rejects_an_out_of_range_percentage(): void
    {
        $this->actingAs($this->admin)
            ->post(route('quotes.store'), $this->quotePayload(['discount_type' => 'percent', 'discount_value' => 101]))
            ->assertSessionHasErrors('discount_value');
    }

    public function test_quote_edit_updates_the_discount(): void
    {
        $this->actingAs($this->admin)->post(route('quotes.store'), $this->quotePayload());
        $quote = Quote::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('quotes.update', $quote), $this->quotePayload(['discount_type' => 'fixed', 'discount_value' => 20_000]))
            ->assertSessionDoesntHaveErrors();

        $quote->refresh();
        $this->assertEquals(20_000, $quote->discount_amount);
        $this->assertEquals(6_000, $quote->vat_amount);
        $this->assertEquals(86_000, $quote->total_amount);
    }

    public function test_accepting_a_discounted_quote_creates_an_invoice_with_the_same_totals(): void
    {
        $this->actingAs($this->admin)
            ->post(route('quotes.store'), $this->quotePayload(['discount_type' => 'percent', 'discount_value' => 10]));
        $quote = Quote::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->actingAs($this->admin)->post(route('quotes.accept', $quote));

        $quote->refresh();
        $invoice = Invoice::withoutGlobalScope('tenant')->findOrFail($quote->converted_invoice_id);

        $this->assertSame('percent', $invoice->discount_type);
        $this->assertEquals(10, $invoice->discount_value);
        $this->assertEquals($quote->discount_amount, $invoice->discount_amount);
        $this->assertEquals($quote->vat_amount, $invoice->vat_amount);
        $this->assertEquals($quote->total_amount, $invoice->total_amount);
    }

    public function test_discount_label_appears_on_quote_pages(): void
    {
        $this->actingAs($this->admin)
            ->post(route('quotes.store'), $this->quotePayload(['discount_type' => 'percent', 'discount_value' => 10]));
        $quote = Quote::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->actingAs($this->admin)->get(route('quotes.show', $quote))->assertOk()->assertSee('Discount (10%)');
        $this->actingAs($this->admin)->get($quote->publicUrl())->assertOk()->assertSee('Discount (10%)');
        $this->actingAs($this->admin)->get(route('quotes.create'))->assertOk()->assertSee('name="discount_type"', false);
    }
}
