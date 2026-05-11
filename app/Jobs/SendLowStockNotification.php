<?php

namespace App\Jobs;

use App\Models\InventoryAlert;
use App\Models\InventoryItem;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLowStockNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly InventoryItem  $item,
        public readonly InventoryAlert $alert,
    ) {}

    public function handle(): void
    {
        // Find all admin and accountant users for this tenant
        $recipients = User::where('tenant_id', $this->item->tenant_id)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_ACCOUNTANT])
            ->where('is_active', true)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new LowStockNotification($this->item, $this->alert);

        foreach ($recipients as $user) {
            $user->notify($notification);
        }

        // Mark the alert as notified
        $this->alert->update(['notified_at' => now()]);
    }
}
