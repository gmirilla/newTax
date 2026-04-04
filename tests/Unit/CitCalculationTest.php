<?php

namespace Tests\Unit;

use App\Services\CitService;
use PHPUnit\Framework\TestCase;

class CitCalculationTest extends TestCase
{
    private CitService $citService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->citService = new CitService();
    }

    /** @test */
    public function small_company_with_turnover_below_25m_is_0_percent(): void
    {
        $size = $this->citService->determineCompanySize(24_999_999);
        $this->assertEquals('small', $size);
        $this->assertEquals(0, CitService::CIT_RATES['small']);
    }

    /** @test */
    public function company_at_25m_exactly_is_small(): void
    {
        $size = $this->citService->determineCompanySize(25_000_000);
        $this->assertEquals('small', $size);
    }

    /** @test */
    public function company_with_turnover_between_25m_and_100m_is_medium(): void
    {
        $size = $this->citService->determineCompanySize(50_000_000);
        $this->assertEquals('medium', $size);
        $this->assertEquals(20, CitService::CIT_RATES['medium']);
    }

    /** @test */
    public function company_with_turnover_above_100m_is_large(): void
    {
        $size = $this->citService->determineCompanySize(100_000_001);
        $this->assertEquals('large', $size);
        $this->assertEquals(30, CitService::CIT_RATES['large']);
    }

    /** @test */
    public function education_tax_rate_is_2_5_percent(): void
    {
        $this->assertEquals(2.5, CitService::EDUCATION_TAX_RATE);
    }

    /** @test */
    public function minimum_tax_rate_is_0_5_percent(): void
    {
        $this->assertEquals(0.5, CitService::MINIMUM_TAX_RATE);
    }

    /** @test */
    public function minimum_tax_floor_is_200000_naira(): void
    {
        $this->assertEquals(200_000, CitService::MINIMUM_TAX_FLOOR);
    }

    /** @test */
    public function cit_filing_due_date_is_june_30_of_following_year(): void
    {
        // December year-end for 2024: due June 30, 2025
        $dueDate = $this->citService->getFilingDueDate(2024);
        $this->assertEquals('2025-06-30', $dueDate);
    }

    /** @test */
    public function it_computes_education_tax_for_profitable_company(): void
    {
        // Taxable profit: ₦10,000,000
        // Education tax: 2.5% × ₦10M = ₦250,000
        $profit    = 10_000_000;
        $eduTax    = round($profit * CitService::EDUCATION_TAX_RATE / 100, 2);
        $this->assertEquals(250_000.00, $eduTax);
    }

    /** @test */
    public function medium_company_cit_is_20_percent_of_taxable_profit(): void
    {
        // Taxable profit: ₦30,000,000 (medium company)
        // CIT: 20% = ₦6,000,000
        $taxableProfit = 30_000_000;
        $rate          = CitService::CIT_RATES['medium'];
        $citAmount     = $taxableProfit * $rate / 100;
        $this->assertEquals(6_000_000, $citAmount);
    }

    /** @test */
    public function large_company_cit_is_30_percent_of_taxable_profit(): void
    {
        $taxableProfit = 150_000_000;
        $rate          = CitService::CIT_RATES['large'];
        $citAmount     = $taxableProfit * $rate / 100;
        $this->assertEquals(45_000_000, $citAmount);
    }
}
