<?php

namespace Tests\Unit;

use App\Services\WhtService;
use PHPUnit\Framework\TestCase;

class WhtCalculationTest extends TestCase
{
    private WhtService $whtService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->whtService = new WhtService();
    }

    /** @test */
    public function services_rendered_by_company_attracts_5_percent_wht(): void
    {
        $rate = $this->whtService->getRate('services', isCompany: true);
        $this->assertEquals(5.0, $rate);
    }

    /** @test */
    public function services_rendered_by_individual_attracts_10_percent_wht(): void
    {
        $rate = $this->whtService->getRate('services', isCompany: false);
        $this->assertEquals(10.0, $rate);
    }

    /** @test */
    public function rent_payments_attract_10_percent_wht(): void
    {
        $rateCompany    = $this->whtService->getRate('rent', isCompany: true);
        $rateIndividual = $this->whtService->getRate('rent', isCompany: false);
        $this->assertEquals(10.0, $rateCompany);
        $this->assertEquals(10.0, $rateIndividual);
    }

    /** @test */
    public function dividend_payments_attract_10_percent_wht(): void
    {
        $rate = $this->whtService->getRate('dividends', isCompany: true);
        $this->assertEquals(10.0, $rate);
    }

    /** @test */
    public function contract_payments_attract_5_percent_wht(): void
    {
        $rate = $this->whtService->getRate('contracts', isCompany: true);
        $this->assertEquals(5.0, $rate);
    }

    /** @test */
    public function it_calculates_wht_amount_correctly_for_services(): void
    {
        // Gross: ₦500,000, Rate: 5%, WHT: ₦25,000, Net: ₦475,000
        $result = $this->whtService->calculate(500_000, 'services', isCompany: true);

        $this->assertEquals(500_000, $result['gross_amount']);
        $this->assertEquals(5.0,    $result['wht_rate']);
        $this->assertEquals(25_000, $result['wht_amount']);
        $this->assertEquals(475_000,$result['net_payment']);
    }

    /** @test */
    public function it_calculates_wht_for_individual_service_provider(): void
    {
        // Gross: ₦200,000, Rate: 10%, WHT: ₦20,000, Net: ₦180,000
        $result = $this->whtService->calculate(200_000, 'services', isCompany: false);

        $this->assertEquals(10.0,    $result['wht_rate']);
        $this->assertEquals(20_000,  $result['wht_amount']);
        $this->assertEquals(180_000, $result['net_payment']);
    }

    /** @test */
    public function it_calculates_wht_for_rent_payment(): void
    {
        // Monthly rent: ₦800,000, WHT: 10% = ₦80,000, Net: ₦720,000
        $result = $this->whtService->calculate(800_000, 'rent', isCompany: true);

        $this->assertEquals(10.0,    $result['wht_rate']);
        $this->assertEquals(80_000,  $result['wht_amount']);
        $this->assertEquals(720_000, $result['net_payment']);
    }

    /** @test */
    public function gross_equals_wht_plus_net(): void
    {
        $result = $this->whtService->calculate(1_500_000, 'services', isCompany: true);

        $this->assertEquals(
            $result['gross_amount'],
            $result['wht_amount'] + $result['net_payment']
        );
    }

    /** @test */
    public function wht_amount_is_rounded_to_two_decimal_places(): void
    {
        // ₦333,333 × 5% = ₦16,666.65
        $result = $this->whtService->calculate(333_333, 'services', isCompany: true);
        $this->assertEquals(16_666.65, $result['wht_amount']);
    }
}
