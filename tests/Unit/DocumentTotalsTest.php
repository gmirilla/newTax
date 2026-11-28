<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Quote;
use App\Support\DocumentTotals;
use PHPUnit\Framework\TestCase;

class DocumentTotalsTest extends TestCase
{
    /** ₦100,000 of VAT-able lines => ₦7,500 line VAT before any discount. */
    private function calc(?string $type, float $value, bool $vat = true, bool $wht = false, float $legacy = 0.0): array
    {
        return DocumentTotals::calculate(100_000, 7_500, $vat, $wht, 5, $type, $value, $legacy);
    }

    public function test_no_discount_leaves_totals_unchanged(): void
    {
        $t = $this->calc('fixed', 0);

        $this->assertSame(0.0, $t['discount']);
        $this->assertSame(7_500.0, $t['vat']);
        $this->assertSame(107_500.0, $t['total']);
    }

    public function test_fixed_discount_reduces_the_vat_base(): void
    {
        $t = $this->calc('fixed', 10_000);

        $this->assertSame(10_000.0, $t['discount']);
        $this->assertSame(90_000.0, $t['net']);
        $this->assertSame(6_750.0, $t['vat']);    // 7.5% of 90,000
        $this->assertSame(96_750.0, $t['total']);
    }

    public function test_percent_discount_is_taken_from_the_subtotal(): void
    {
        $t = $this->calc('percent', 10);

        $this->assertSame(10_000.0, $t['discount']);
        $this->assertSame(6_750.0, $t['vat']);
        $this->assertSame(96_750.0, $t['total']);
    }

    public function test_wht_is_charged_on_the_discounted_amount(): void
    {
        $t = $this->calc('percent', 20, true, true);

        $this->assertSame(80_000.0, $t['net']);
        $this->assertSame(4_000.0, $t['wht']);    // 5% of 80,000, not of 100,000
        $this->assertSame(6_000.0, $t['vat']);
        $this->assertSame(82_000.0, $t['total']); // 80,000 + 6,000 - 4,000
    }

    public function test_hundred_percent_discount_zeroes_everything(): void
    {
        $t = $this->calc('percent', 100, true, true);

        $this->assertSame(100_000.0, $t['discount']);
        $this->assertSame(0.0, $t['vat']);
        $this->assertSame(0.0, $t['wht']);
        $this->assertSame(0.0, $t['total']);
    }

    public function test_discount_is_capped_at_the_subtotal(): void
    {
        $fixed   = $this->calc('fixed', 250_000);
        $percent = $this->calc('percent', 250);

        $this->assertSame(100_000.0, $fixed['discount']);
        $this->assertSame(0.0, $fixed['total']);
        $this->assertSame(100_000.0, $percent['discount']);
        $this->assertSame(0.0, $percent['total']);
    }

    public function test_vat_off_ignores_line_vat_but_still_discounts(): void
    {
        $t = $this->calc('fixed', 10_000, false);

        $this->assertSame(0.0, $t['vat']);
        $this->assertSame(90_000.0, $t['total']);
    }

    public function test_zero_subtotal_does_not_divide_by_zero(): void
    {
        $t = DocumentTotals::calculate(0, 0, true, true, 5, 'percent', 10, 0);

        $this->assertSame(0.0, $t['total']);
    }

    public function test_legacy_documents_keep_the_old_maths(): void
    {
        // type = null: flat discount, VAT and WHT on the pre-discount subtotal
        $t = $this->calc(null, 0, true, true, 10_000);

        $this->assertSame(10_000.0, $t['discount']);
        $this->assertSame(7_500.0, $t['vat']);
        $this->assertSame(5_000.0, $t['wht']);
        $this->assertSame(92_500.0, $t['total']); // 100,000 - 10,000 + 7,500 - 5,000
    }

    public function test_label_shows_the_percentage_only_for_percent_discounts(): void
    {
        $this->assertSame('Discount (10%)', DocumentTotals::label('percent', 10));
        $this->assertSame('Discount (12.5%)', DocumentTotals::label('percent', 12.5));
        $this->assertSame('Discount', DocumentTotals::label('fixed', 5_000));
        $this->assertSame('Discount', DocumentTotals::label(null, 0));
    }

    public function test_models_expose_label_and_has_discount(): void
    {
        $invoice = new Invoice(['discount_type' => 'percent', 'discount_value' => 15, 'discount_amount' => 1_500]);
        $quote   = new Quote(['discount_type' => 'fixed', 'discount_value' => 0, 'discount_amount' => 0]);

        $this->assertSame('Discount (15%)', $invoice->discountLabel());
        $this->assertTrue($invoice->hasDiscount());
        $this->assertSame('Discount', $quote->discountLabel());
        $this->assertFalse($quote->hasDiscount());
    }
}
