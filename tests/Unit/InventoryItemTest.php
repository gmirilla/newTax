<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use PHPUnit\Framework\TestCase;

class InventoryItemTest extends TestCase
{
    // ── isOutOfStock ──────────────────────────────────────────────────────────

    public function test_is_out_of_stock_when_zero(): void
    {
        $item = new InventoryItem(['current_stock' => 0]);
        $this->assertTrue($item->isOutOfStock());
    }

    public function test_is_out_of_stock_when_negative(): void
    {
        $item = new InventoryItem(['current_stock' => -1]);
        $this->assertTrue($item->isOutOfStock());
    }

    public function test_not_out_of_stock_when_positive(): void
    {
        $item = new InventoryItem(['current_stock' => 0.001]);
        $this->assertFalse($item->isOutOfStock());
    }

    // ── isBelowRestockLevel ───────────────────────────────────────────────────

    public function test_is_below_restock_level_when_stock_equals_level(): void
    {
        $item = new InventoryItem(['current_stock' => 10, 'restock_level' => 10]);
        $this->assertTrue($item->isBelowRestockLevel());
    }

    public function test_is_below_restock_level_when_stock_less_than_level(): void
    {
        $item = new InventoryItem(['current_stock' => 5, 'restock_level' => 20]);
        $this->assertTrue($item->isBelowRestockLevel());
    }

    public function test_not_below_restock_level_when_sufficient_stock(): void
    {
        $item = new InventoryItem(['current_stock' => 50, 'restock_level' => 20]);
        $this->assertFalse($item->isBelowRestockLevel());
    }

    public function test_not_below_restock_level_when_level_is_zero(): void
    {
        // A restock_level of 0 means no threshold set — should not trigger
        $item = new InventoryItem(['current_stock' => 0, 'restock_level' => 0]);
        $this->assertFalse($item->isBelowRestockLevel());
    }

    // ── recalculateAvgCost ────────────────────────────────────────────────────

    public function test_weighted_average_cost_with_existing_stock(): void
    {
        // 100 units at ₦500 avg cost + 50 units at ₦800 = (50000 + 40000) / 150 = ₦600
        $item = new InventoryItem(['current_stock' => 100, 'avg_cost' => 500]);

        $newAvg = $item->recalculateAvgCost(50, 800);

        $this->assertEquals(600.0, $newAvg);
    }

    public function test_avg_cost_when_starting_from_zero_stock(): void
    {
        // No existing stock — new cost becomes the avg cost
        $item = new InventoryItem(['current_stock' => 0, 'avg_cost' => 0]);

        $newAvg = $item->recalculateAvgCost(20, 350);

        $this->assertEquals(350.0, $newAvg);
    }

    public function test_avg_cost_rounds_to_four_decimal_places(): void
    {
        // 1 unit at ₦100 + 2 units at ₦200 = 500 / 3 = 166.6667
        $item = new InventoryItem(['current_stock' => 1, 'avg_cost' => 100]);

        $newAvg = $item->recalculateAvgCost(2, 200);

        $this->assertEquals(166.6667, $newAvg);
    }

    public function test_avg_cost_falls_back_to_unit_cost_when_total_becomes_zero(): void
    {
        // Edge case: negative existing stock + small restock = total <= 0
        $item = new InventoryItem(['current_stock' => -10, 'avg_cost' => 100]);

        $newAvg = $item->recalculateAvgCost(5, 250);

        // total = -10 + 5 = -5 <= 0, so returns unitCost
        $this->assertEquals(250.0, $newAvg);
    }
}
