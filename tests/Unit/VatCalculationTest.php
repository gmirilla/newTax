<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Services\VatService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class VatCalculationTest extends TestCase
{
    private VatService $vatService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vatService = new VatService();
    }

    public function test_it_calculates_output_vat_at_7_5_percent(): void
    {
        // ₦1,000,000 × 7.5% = ₦75,000
        $vat = $this->vatService->calculateOutputVat(1_000_000);
        $this->assertEquals(75_000.00, $vat);
    }

    public function test_it_calculates_vat_on_small_amount(): void
    {
        // ₦500 × 7.5% = ₦37.50
        $vat = $this->vatService->calculateOutputVat(500);
        $this->assertEquals(37.50, $vat);
    }

    public function test_it_extracts_vat_from_vat_inclusive_amount(): void
    {
        // If VAT-inclusive price is ₦107,500, VAT component = ₦7,500
        // Formula: (107,500 × 7.5) / 107.5 = ₦7,500
        $vat = $this->vatService->extractVatFromInclusive(107_500);
        $this->assertEquals(7_500.00, $vat);
    }

    public function test_it_correctly_reverse_calculates_vat(): void
    {
        // Price excl VAT: ₦200,000
        // VAT: ₦15,000
        // Incl price: ₦215,000
        $vat = $this->vatService->extractVatFromInclusive(215_000);
        $this->assertEquals(15_000.00, $vat);
    }

    public function test_vat_rate_constant_is_7_5_percent(): void
    {
        $this->assertEquals(7.5, VatService::VAT_RATE);
    }

    public function test_vat_filing_deadline_is_21st_of_following_month(): void
    {
        // January 2025 VAT is due by February 21, 2025
        $dueDate = $this->vatService->getFilingDueDate(2025, 1);
        $this->assertEquals('2025-02-21', $dueDate);
    }

    public function test_vat_filing_deadline_for_december_is_january_21(): void
    {
        // December 2024 VAT is due by January 21, 2025
        $dueDate = $this->vatService->getFilingDueDate(2024, 12);
        $this->assertEquals('2025-01-21', $dueDate);
    }

    public function test_it_rounds_vat_to_two_decimal_places(): void
    {
        // ₦333.33 × 7.5% = ₦24.9997... → rounds to ₦25.00
        $vat = $this->vatService->calculateOutputVat(333.33);
        $this->assertEquals(25.00, $vat);
    }

    public function test_zero_amount_produces_zero_vat(): void
    {
        $this->assertEquals(0.00, $this->vatService->calculateOutputVat(0));
    }

    // ── sumOutputVatReceived() — VAT recognized on a cash (payment-received) basis ──

    private function makeInvoice(float $vatAmount, float $amountPaid, float $totalAmount): Invoice
    {
        return new Invoice([
            'vat_amount'   => $vatAmount,
            'amount_paid'  => $amountPaid,
            'total_amount' => $totalAmount,
        ]);
    }

    public function test_partial_invoice_contributes_prorated_vat(): void
    {
        // ₦100,000 subtotal + ₦7,500 VAT = ₦107,500 total; only ₦20,000 paid.
        $invoice = $this->makeInvoice(7_500, 20_000, 107_500);

        $vat = $this->vatService->sumOutputVatReceived(new Collection([$invoice]));

        $this->assertEquals(round(7_500 * (20_000 / 107_500), 2), $vat);
        $this->assertLessThan(7_500, $vat);
    }

    public function test_fully_paid_invoice_contributes_full_vat(): void
    {
        $invoice = $this->makeInvoice(7_500, 107_500, 107_500);

        $vat = $this->vatService->sumOutputVatReceived(new Collection([$invoice]));

        $this->assertEquals(7_500.00, $vat);
    }

    public function test_unpaid_invoice_contributes_zero_vat(): void
    {
        $invoice = $this->makeInvoice(7_500, 0, 107_500);

        $vat = $this->vatService->sumOutputVatReceived(new Collection([$invoice]));

        $this->assertEquals(0.00, $vat);
    }

    public function test_zero_total_invoice_does_not_divide_by_zero(): void
    {
        $invoice = $this->makeInvoice(0, 0, 0);

        $vat = $this->vatService->sumOutputVatReceived(new Collection([$invoice]));

        $this->assertEquals(0.00, $vat);
    }

    public function test_overpaid_invoice_clamps_at_full_vat_amount(): void
    {
        // Paid more than the invoice total (e.g. a rounding/overpayment) —
        // must not contribute more VAT than the invoice actually carries.
        $invoice = $this->makeInvoice(7_500, 120_000, 107_500);

        $vat = $this->vatService->sumOutputVatReceived(new Collection([$invoice]));

        $this->assertEquals(7_500.00, $vat);
    }

    public function test_sums_across_multiple_invoices_with_mixed_payment_states(): void
    {
        $invoices = new Collection([
            $this->makeInvoice(7_500, 107_500, 107_500),  // fully paid -> 7,500
            $this->makeInvoice(7_500, 0, 107_500),        // unpaid -> 0
            $this->makeInvoice(7_500, 53_750, 107_500),   // half paid -> 3,750
        ]);

        $vat = $this->vatService->sumOutputVatReceived($invoices);

        $this->assertEquals(11_250.00, $vat);
    }
}
