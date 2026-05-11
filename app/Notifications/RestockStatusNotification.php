<?php

namespace App\Notifications;

use App\Models\RestockRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestockStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly RestockRequest $restockRequest,
        public readonly string         $event, // 'approved' | 'rejected' | 'received'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rr      = $this->restockRequest;
        $showUrl = route('inventory.restock.show', $rr->id);

        [$subject, $headline] = match ($this->event) {
            'approved' => [
                "Restock Request Approved — {$rr->request_number}",
                "Your restock request **{$rr->request_number}** has been **approved**.",
            ],
            'rejected' => [
                "Restock Request Rejected — {$rr->request_number}",
                "Your restock request **{$rr->request_number}** has been **rejected**.",
            ],
            'received' => [
                "Stock Received — {$rr->request_number}",
                "Goods for restock request **{$rr->request_number}** have been **received and stock updated**.",
            ],
            default => ["Restock Update — {$rr->request_number}", ''],
        };

        $msg = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($headline)
            ->line('')
            ->line('**Request Details**')
            ->line('Request No.: ' . $rr->request_number)
            ->line('Item: ' . ($rr->item->name ?? '—'))
            ->line('Quantity: ' . number_format((float) $rr->quantity_requested, 3) . ' ' . ($rr->item->unit ?? ''));

        if ($this->event === 'rejected' && $rr->rejection_reason) {
            $msg->line('Reason: ' . $rr->rejection_reason);
        }

        if ($this->event === 'received') {
            $msg->line('New Stock Level: ' . number_format((float) $rr->item->current_stock, 3) . ' ' . ($rr->item->unit ?? ''));
        }

        return $msg
            ->action('View Request', $showUrl)
            ->salutation('AccountTaxNG Inventory');
    }
}
