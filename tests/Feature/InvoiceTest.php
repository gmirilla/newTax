<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\VatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'            => 'Test Company Ltd',
            'slug'            => 'test-company',
            'email'           => 'test@company.ng',
            'tax_category'    => 'small',
            'annual_turnover' => 10_000_000,
            'currency'        => 'NGN',
            'is_active'       => true,
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Test Admin',
            'email'     => 'admin@test.ng',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'ACME Corp Nigeria',
            'email'      => 'ap@acme.ng',
            'is_company' => true,
            'is_active'  => true,
        ]);

        // Provision chart of accounts
        app(\App\Services\BookkeepingService::class)
            ->provisionDefaultAccounts($this->tenant);

        // InvoiceService::create() reads auth()->id() for created_by — these
        // tests call the service directly, so an authenticated user must
        // already be set before any test body runs.
        $this->actingAs($this->admin);
    }

    public function test_it_creates_invoice_with_correct_vat_calculation(): void
    {
        $service = app(InvoiceService::class);

        $invoice = $service->create(
            $this->tenant,
            [
                'customer_id'    => $this->customer->id,
                'invoice_date'   => now()->toDateString(),
                'due_date'       => now()->addDays(30)->toDateString(),
                'vat_applicable' => true,
                'wht_applicable' => false,
            ],
            [
                [
                    'description' => 'IT Consulting – Q1 2025',
                    'quantity'    => 1,
                    'unit_price'  => 1_000_000,
                    'vat_applicable' => true,
                ],
            ]
        );

        $this->assertNotNull($invoice);
        $this->assertEquals(1_000_000, $invoice->subtotal);
        $this->assertEquals(75_000,    $invoice->vat_amount);    // 7.5% of 1M
        $this->assertEquals(1_075_000, $invoice->total_amount);  // subtotal + VAT
        $this->assertEquals(0,         $invoice->wht_amount);
        $this->assertEquals('draft',   $invoice->status);
    }

    public function test_invoice_number_is_generated_in_correct_format(): void
    {
        $service = app(InvoiceService::class);
        $number  = $service->generateInvoiceNumber($this->tenant);

        // Format: INV-YYYYMM-NNNN
        $this->assertMatchesRegularExpression('/^INV-\d{6}-\d{4}$/', $number);
    }

    public function test_sequential_invoice_numbers_are_unique(): void
    {
        $service = app(InvoiceService::class);

        $n1 = $service->generateInvoiceNumber($this->tenant);
        // Manually insert a dummy invoice to force next sequence
        Invoice::create([
            'tenant_id'      => $this->tenant->id,
            'customer_id'    => $this->customer->id,
            'invoice_number' => $n1,
            'invoice_date'   => now(),
            'due_date'       => now()->addDays(30),
            'status'         => 'draft',
            'created_by'     => $this->admin->id,
        ]);

        $n2 = $service->generateInvoiceNumber($this->tenant);
        $this->assertNotEquals($n1, $n2);
    }

    public function test_it_calculates_wht_deduction_correctly(): void
    {
        $service = app(InvoiceService::class);

        $invoice = $service->create(
            $this->tenant,
            [
                'customer_id'    => $this->customer->id,
                'invoice_date'   => now()->toDateString(),
                'due_date'       => now()->addDays(30)->toDateString(),
                'vat_applicable' => true,
                'wht_applicable' => true,
                'wht_rate'       => 5,
            ],
            [
                [
                    'description'    => 'Services rendered',
                    'quantity'       => 1,
                    'unit_price'     => 500_000,
                    'vat_applicable' => true,
                ],
            ]
        );

        $this->assertEquals(500_000, $invoice->subtotal);
        $this->assertEquals(37_500,  $invoice->vat_amount);  // 7.5% VAT
        $this->assertEquals(25_000,  $invoice->wht_amount);  // 5% WHT on subtotal
        // Total = 500,000 + 37,500 - 25,000 = 512,500
        $this->assertEquals(512_500, $invoice->total_amount);
    }

    public function test_recording_payment_updates_invoice_status_to_paid(): void
    {
        $service = app(InvoiceService::class);

        $invoice = $service->create(
            $this->tenant,
            [
                'customer_id'    => $this->customer->id,
                'invoice_date'   => now()->toDateString(),
                'due_date'       => now()->addDays(30)->toDateString(),
                'vat_applicable' => true,
            ],
            [
                ['description' => 'Test service', 'quantity' => 1, 'unit_price' => 200_000, 'vat_applicable' => true],
            ]
        );

        $this->actingAs($this->admin);

        $service->recordPayment($invoice, [
            'payment_date' => now()->toDateString(),
            'amount'       => $invoice->total_amount,
            'method'       => 'bank_transfer',
        ]);

        $invoice->refresh();

        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $invoice->balance_due);
    }

    public function test_vat_is_zero_when_not_applicable(): void
    {
        $service = app(InvoiceService::class);

        $invoice = $service->create(
            $this->tenant,
            [
                'customer_id'    => $this->customer->id,
                'invoice_date'   => now()->toDateString(),
                'due_date'       => now()->addDays(30)->toDateString(),
                'vat_applicable' => false,
            ],
            [
                ['description' => 'Exempt supply', 'quantity' => 1, 'unit_price' => 100_000, 'vat_applicable' => false],
            ]
        );

        $this->assertEquals(0, $invoice->vat_amount);
        $this->assertEquals(100_000, $invoice->total_amount);
    }

    public function test_editing_an_invoice_via_the_edit_form_succeeds(): void
    {
        $service = app(InvoiceService::class);

        $invoice = $service->create(
            $this->tenant,
            [
                'customer_id'    => $this->customer->id,
                'invoice_date'   => now()->toDateString(),
                'due_date'       => now()->addDays(30)->toDateString(),
                'vat_applicable' => true,
            ],
            [
                ['description' => 'Original line', 'quantity' => 1, 'unit_price' => 100_000, 'vat_applicable' => true],
            ]
        );

        $newInvoiceDate = now()->subDays(2)->toDateString();
        $newDueDate     = now()->addDays(45)->toDateString();

        // Mirrors exactly what resources/views/invoices/edit.blade.php submits —
        // this previously used the field name "issue_date", which InvoiceRequest
        // never recognised, so every edit failed with "The invoice date field is required."
        $response = $this->actingAs($this->admin)->put(route('invoices.update', $invoice), [
            'customer_id'    => $this->customer->id,
            'invoice_date'   => $newInvoiceDate,
            'due_date'       => $newDueDate,
            'vat_applicable' => '1',
            'items' => [
                ['description' => 'Updated line', 'quantity' => 2, 'unit_price' => 50_000, 'vat_applicable' => '1'],
            ],
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionDoesntHaveErrors();

        $invoice->refresh();
        $this->assertSame($newInvoiceDate, $invoice->invoice_date->toDateString());
        $this->assertSame($newDueDate, $invoice->due_date->toDateString());
        $this->assertCount(1, $invoice->items);
        $this->assertSame('Updated line', $invoice->items->first()->description);
    }
}
