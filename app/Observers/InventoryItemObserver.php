<?php

namespace App\Observers;

use App\Jobs\SendLowStockNotification;
use App\Models\InventoryAlert;
use App\Models\InventoryItem;

class InventoryItemObserver
{
    public function updated(InventoryItem $item): void
    {
        if (! $item->wasChanged('current_stock')) {
            return;
        }

        $stock = (float) $item->current_stock;
        $level = (float) $item->restock_level;

        // Clear out_of_stock alert if stock was replenished
        if ($stock > 0) {
            InventoryAlert::where('tenant_id', $item->tenant_id)
                ->where('item_id', $item->id)
                ->where('type', 'out_of_stock')
                ->whereNull('seen_at')
                ->update(['seen_at' => now()]);
        }

        // Clear low_stock alert if stock is back above restock level
        if ($level > 0 && $stock > $level) {
            InventoryAlert::where('tenant_id', $item->tenant_id)
                ->where('item_id', $item->id)
                ->where('type', 'low_stock')
                ->whereNull('seen_at')
                ->update(['seen_at' => now()]);
        }

        // No new alert needed when stock is healthy
        if ($stock > 0 && ($level <= 0 || $stock > $level)) {
            return;
        }

        // out_of_stock fires regardless of restock_level; low_stock requires a configured level
        if ($stock <= 0) {
            $type = 'out_of_stock';
        } elseif ($level > 0 && $stock <= $level) {
            $type = 'low_stock';
        } else {
            return;
        }

        // Only create a new alert and notify if no unacknowledged one exists for this item+type
        $alert = InventoryAlert::firstOrCreate(
            [
                'tenant_id' => $item->tenant_id,
                'item_id'   => $item->id,
                'type'      => $type,
                'seen_at'   => null,
            ],
            ['stock_at_alert' => $stock]
        );

        // Dispatch notification job if this is a fresh alert (not a duplicate)
        if ($alert->wasRecentlyCreated) {
            SendLowStockNotification::dispatch($item, $alert);
        }
    }
}
