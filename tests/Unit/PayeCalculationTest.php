<?php

namespace Tests\Unit;

use App\Services\PayeService;
use PHPUnit\Framework\TestCase;

class PayeCalculationTest extends TestCase
{
    private PayeService $payeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payeService = new PayeService();
    }

    /** @test */
    public function zero_income_produces_zero_paye(): void
    {
        $tax = $this->payeService->computeProgressiveTax(0);
        $this->assertEquals(0, $tax);
    }

    /** @test */
    public function negative_income_produces_zero_paye(): void
    {
        $tax = $this->payeService->computeProgressiveTax(-1000);
        $this->assertEquals(0, $tax);
    }

    /** @test */
    public function first_300k_is_taxed_at_7_percent(): void
    {
        // ₦300,000 × 7% = ₦21,000
        $tax = $this->payeService->computeProgressiveTax(300_000);
        $this->assertEquals(21_000, $tax);
    }

    /** @test */
    public function income_spanning_two_bands_computed_correctly(): void
    {
        // ₦600,000:
        // First  ₦300,000 @ 7%  = ₦21,000
        // Next   ₦300,000 @ 11% = ₦33,000
        // Total: ₦54,000
        $tax = $this->payeService->computeProgressiveTax(600_000);
        $this->assertEquals(54_000, $tax);
    }

    /** @test */
    public function consolidated_relief_includes_flat_and_percentage(): void
    {
        // Annual gross: ₦5,000,000
        // CRA = max(₦200,000, 1% × ₦5M) + 20% × ₦5M
        //     = max(200,000, 50,000) + 1,000,000
        //     = 200,000 + 1,000,000
        //     = ₦1,200,000
        $cra = $this->payeService->computeConsolidatedRelief(5_000_000);
        $this->assertEquals(1_200_000, $cra);
    }

    /** @test */
    public function consolidated_relief_uses_1_percent_when_larger_than_200k(): void
    {
        // Annual gross: ₦30,000,000
        // 1% of gross = ₦300,000 > ₦200,000 → use ₦300,000
        // CRA = 300,000 + 20% × 30M = 300,000 + 6,000,000 = ₦6,300,000
        $cra = $this->payeService->computeConsolidatedRelief(30_000_000);
        $this->assertEquals(6_300_000, $cra);
    }

    /** @test */
    public function pension_rate_constants_are_correct(): void
    {
        $this->assertEquals(8,  PayeService::PENSION_EMPLOYEE_RATE);
        $this->assertEquals(10, PayeService::PENSION_EMPLOYER_RATE);
    }

    /** @test */
    public function nhf_rate_is_2_5_percent(): void
    {
        $this->assertEquals(2.5, PayeService::NHF_RATE);
    }

    /** @test */
    public function high_income_earner_reaches_24_percent_band(): void
    {
        // ₦5,000,000 annual taxable income should hit 24% band
        $tax = $this->payeService->computeProgressiveTax(5_000_000);

        // First  ₦300,000  @ 7%  = 21,000
        // Next   ₦300,000  @ 11% = 33,000
        // Next   ₦500,000  @ 15% = 75,000
        // Next   ₦500,000  @ 19% = 95,000
        // Next ₦1,600,000  @ 21% = 336,000
        // Remaining ₦1,800,000 @ 24% = 432,000
        // Total = 992,000
        $this->assertEquals(992_000, $tax);
    }
}
