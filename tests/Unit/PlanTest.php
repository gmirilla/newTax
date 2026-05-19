<?php

namespace Tests\Unit;

use App\Models\Plan;
use PHPUnit\Framework\TestCase;

class PlanTest extends TestCase
{
    // ── limit() ───────────────────────────────────────────────────────────────

    public function test_limit_returns_stored_value_when_present(): void
    {
        $plan = new Plan(['limits' => ['invoices_per_month' => 50]]);

        $this->assertEquals(50, $plan->limit('invoices_per_month'));
    }

    public function test_limit_falls_back_to_default_when_key_missing(): void
    {
        $plan = new Plan(['limits' => []]);

        // Default for invoices_per_month is 5 (from LIMIT_DEFAULTS)
        $this->assertEquals(5, $plan->limit('invoices_per_month'));
    }

    public function test_limit_returns_null_for_unknown_key(): void
    {
        $plan = new Plan(['limits' => []]);

        $this->assertNull($plan->limit('nonexistent_feature'));
    }

    public function test_limit_can_store_null_for_unlimited(): void
    {
        $plan = new Plan(['limits' => ['invoices_per_month' => null]]);

        $this->assertNull($plan->limit('invoices_per_month'));
    }

    // ── allows() ─────────────────────────────────────────────────────────────

    public function test_allows_returns_true_when_feature_enabled(): void
    {
        $plan = new Plan(['limits' => ['inventory' => true]]);

        $this->assertTrue($plan->allows('inventory'));
    }

    public function test_allows_returns_false_when_feature_disabled(): void
    {
        $plan = new Plan(['limits' => ['inventory' => false]]);

        $this->assertFalse($plan->allows('inventory'));
    }

    public function test_allows_defaults_to_false_for_boolean_features(): void
    {
        // No limits stored — falls back to LIMIT_DEFAULTS which has false
        $plan = new Plan(['limits' => []]);

        $this->assertFalse($plan->allows('payroll'));
        $this->assertFalse($plan->allows('manufacturing'));
        $this->assertFalse($plan->allows('maintenance'));
        $this->assertFalse($plan->allows('inventory'));
    }

    public function test_allows_returns_false_for_unknown_feature(): void
    {
        $plan = new Plan(['limits' => []]);

        $this->assertFalse($plan->allows('nonexistent'));
    }

    // ── priceLabel() ─────────────────────────────────────────────────────────

    public function test_price_label_shows_free_for_zero_price(): void
    {
        $plan = new Plan(['price_monthly' => 0]);

        $this->assertEquals('Free', $plan->priceLabel());
    }

    public function test_price_label_formats_naira_price(): void
    {
        $plan = new Plan(['price_monthly' => 25000]);

        $this->assertEquals('₦25,000/mo', $plan->priceLabel());
    }

    // ── yearlyDiscount() ──────────────────────────────────────────────────────

    public function test_yearly_discount_calculates_percentage_saved(): void
    {
        // Monthly: ₦10,000 → yearly: ₦96,000 (₦8,000/mo) → 20% saving
        $plan = new Plan(['price_monthly' => 10000, 'price_yearly' => 96000]);

        $this->assertEquals(20, $plan->yearlyDiscount());
    }

    public function test_yearly_discount_is_zero_when_no_yearly_price(): void
    {
        $plan = new Plan(['price_monthly' => 10000, 'price_yearly' => null]);

        $this->assertEquals(0, $plan->yearlyDiscount());
    }
}
