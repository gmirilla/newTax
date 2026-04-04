<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Customer;
use App\Services\CitService;
use App\Services\VatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxComplianceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $smallTenant;
    private Tenant $mediumTenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smallTenant = Tenant::create([
            'name'            => 'Small Biz NG',
            'slug'            => 'small-biz-ng',
            'email'           => 'small@biz.ng',
            'tax_category'    => 'small',
            'annual_turnover' => 20_000_000,
            'vat_registered'  => false,
            'currency'        => 'NGN',
            'is_active'       => true,
        ]);

        $this->mediumTenant = Tenant::create([
            'name'            => 'Medium Corp NG',
            'slug'            => 'medium-corp-ng',
            'email'           => 'finance@mediumcorp.ng',
            'tax_category'    => 'medium',
            'annual_turnover' => 75_000_000,
            'vat_registered'  => true,
            'currency'        => 'NGN',
            'is_active'       => true,
        ]);
    }

    // ── CIT Tests ────────────────────────────────────────────────────────────

    /** @test */
    public function small_company_is_exempt_from_cit(): void
    {
        $citService = app(CitService::class);

        // Create user for auth
        User::create([
            'tenant_id' => $this->smallTenant->id,
            'name'      => 'Admin',
            'email'     => 'a@small.ng',
            'password'  => bcrypt('pass'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        app(\App\Services\BookkeepingService::class)
            ->provisionDefaultAccounts($this->smallTenant);

        $computation = $citService->compute($this->smallTenant, now()->year);

        $this->assertEquals('small',   $computation['company_size']);
        $this->assertEquals(0,         $computation['cit_rate']);
        $this->assertTrue($computation['is_exempt']);
    }

    /** @test */
    public function small_company_must_still_file_cit_return(): void
    {
        // Even exempt companies must file; is_exempt just means 0% rate
        $citService = app(CitService::class);
        $size       = $citService->determineCompanySize(24_000_000);
        $this->assertEquals('small', $size);

        // Filing is required even when exempt
        $dueDate = $citService->getFilingDueDate(2024);
        $this->assertNotEmpty($dueDate);
    }

    /** @test */
    public function medium_company_pays_20_percent_cit(): void
    {
        $citService = app(CitService::class);
        $size       = $citService->determineCompanySize(75_000_000);
        $rate       = CitService::CIT_RATES[$size];

        $this->assertEquals('medium', $size);
        $this->assertEquals(20, $rate);
    }

    // ── VAT Tests ────────────────────────────────────────────────────────────

    /** @test */
    public function small_company_below_25m_is_not_vat_registered(): void
    {
        $this->assertFalse($this->smallTenant->vat_registered);
        $this->assertFalse($this->smallTenant->isVatRegistered());
    }

    /** @test */
    public function medium_company_above_25m_is_vat_registered(): void
    {
        $this->assertTrue($this->mediumTenant->vat_registered);
        $this->assertTrue($this->mediumTenant->isVatRegistered());
    }

    /** @test */
    public function vat_return_computes_net_vat_correctly(): void
    {
        $vatService = app(VatService::class);
        $customer   = Customer::create([
            'tenant_id'  => $this->mediumTenant->id,
            'name'       => 'Test Customer',
            'is_company' => true,
            'is_active'  => true,
        ]);

        User::create([
            'tenant_id' => $this->mediumTenant->id,
            'name'      => 'Finance User',
            'email'     => 'f@medium.ng',
            'password'  => bcrypt('pass'),
            'role'      => 'accountant',
            'is_active' => true,
        ]);

        // Create invoice with VAT in January 2025
        $invoice = Invoice::create([
            'tenant_id'      => $this->mediumTenant->id,
            'customer_id'    => $customer->id,
            'invoice_number' => 'INV-202501-0001',
            'invoice_date'   => '2025-01-15',
            'due_date'       => '2025-02-15',
            'subtotal'       => 1_000_000,
            'vat_amount'     => 75_000,
            'total_amount'   => 1_075_000,
            'vat_applicable' => true,
            'status'         => 'paid',
            'created_by'     => 1,
        ]);

        $data = $vatService->computeMonthlyReturn($this->mediumTenant, 2025, 1);

        $this->assertEquals(75_000, $data['output_vat']);
        $this->assertEquals(0,      $data['input_vat']);
        $this->assertEquals(75_000, $data['net_vat_payable']);
    }

    /** @test */
    public function vat_threshold_is_25_million_naira(): void
    {
        $this->assertEquals(25_000_000, Tenant::VAT_THRESHOLD);
    }

    /** @test */
    public function tenant_update_tax_category_sets_vat_registered_at_25m(): void
    {
        $this->smallTenant->annual_turnover = 26_000_000;
        $this->smallTenant->save();
        $this->smallTenant->updateTaxCategory();
        $this->smallTenant->refresh();

        $this->assertTrue($this->smallTenant->vat_registered);
        $this->assertEquals('medium', $this->smallTenant->tax_category);
    }
}
