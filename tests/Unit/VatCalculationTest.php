<?php

namespace Tests\Unit;

use App\Services\VatService;
use PHPUnit\Framework\TestCase;

class VatCalculationTest extends TestCase
{
    private VatService $vatService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vatService = new VatService();
    }

    /** @test */
    public function it_calculates_output_vat_at_7_5_percent(): void
    {
        // ₦1,000,000 × 7.5% = ₦75,000
        $vat = $this->vatService->calculateOutputVat(1_000_000);
        $this->assertEquals(75_000.00, $vat);
    }

    /** @test */
    public function it_calculates_vat_on_small_amount(): void
    {
        // ₦500 × 7.5% = ₦37.50
        $vat = $this->vatService->calculateOutputVat(500);
        $this->assertEquals(37.50, $vat);
    }

    /** @test */
    public function it_extracts_vat_from_vat_inclusive_amount(): void
    {
        // If VAT-inclusive price is ₦107,500, VAT component = ₦7,500
        // Formula: (107,500 × 7.5) / 107.5 = ₦7,500
        $vat = $this->vatService->extractVatFromInclusive(107_500);
        $this->assertEquals(7_500.00, $vat);
    }

    /** @test */
    public function it_correctly_reverse_calculates_vat(): void
    {
        // Price excl VAT: ₦200,000
        // VAT: ₦15,000
        // Incl price: ₦215,000
        $vat = $this->vatService->extractVatFromInclusive(215_000);
        $this->assertEquals(15_000.00, $vat);
    }

    /** @test */
    public function vat_rate_constant_is_7_5_percent(): void
    {
        $this->assertEquals(7.5, VatService::VAT_RATE);
    }

    /** @test */
    public function vat_filing_deadline_is_21st_of_following_month(): void
    {
        // January 2025 VAT is due by February 21, 2025
        $dueDate = $this->vatService->getFilingDueDate(2025, 1);
        $this->assertEquals('2025-02-21', $dueDate);
    }

    /** @test */
    public function vat_filing_deadline_for_december_is_january_21(): void
    {
        // December 2024 VAT is due by January 21, 2025
        $dueDate = $this->vatService->getFilingDueDate(2024, 12);
        $this->assertEquals('2025-01-21', $dueDate);
    }

    /** @test */
    public function it_rounds_vat_to_two_decimal_places(): void
    {
        // ₦333.33 × 7.5% = ₦24.9997... → rounds to ₦25.00
        $vat = $this->vatService->calculateOutputVat(333.33);
        $this->assertEquals(25.00, $vat);
    }

    /** @test */
    public function zero_amount_produces_zero_vat(): void
    {
        $this->assertEquals(0.00, $this->vatService->calculateOutputVat(0));
    }
}
