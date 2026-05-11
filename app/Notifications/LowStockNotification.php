<?php

namespace App\Notifications;

use App\Models\InventoryAlert;
use App\Models\InventoryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly InventoryItem  $item,
        public readonly InventoryAlert $alert,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isOut    = $this->alert->type === 'out_of_stock';
        $subject  = $isOut
            ? '🚨 Out of Stock — ' . $this->item->name
            : '⚠️ Low Stock Alert — ' . $this->item->name;

        $viewUrl   = route('inventory.items.show', $this->item->id);
        $restockUrl = route('inventory.restock.create', ['item_id' => $this->item->id]);

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line(
                $isOut
                    ? "**{$this->item->name}** is now out of stock on AccountTaxNG."
                    : "**{$this->item->name}** has dropped to or below its restock threshold."
            )
            ->line('')
            ->line('**Item Details**')
            ->line('Name: ' . $this->item->name . ($this->item->sku ? ' (SKU: ' . $this->item->sku . ')' : ''))
            ->line('Current Stock: ' . number_format((float) $this->item->current_stock, 3) . ' ' . $this->item->unit)
            ->line('Restock Level: ' . number_format((float) $this->item->restock_level, 3) . ' ' . $this->item->unit)
            ->line('Status: ' . ($isOut ? '🔴 Out of Stock' : '🟡 Low Stock'))
            ->line('')
            ->line('Please arrange a restock to avoid disrupting sales.')
            ->action('View Item', $viewUrl)
            ->line('')
            ->line('[Request Restock](' . $restockUrl . ')')
            ->salutation('AccountTaxNG Inventory Alerts');
    }
}
