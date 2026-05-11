<?php

namespace App\Notifications;

use App\Models\RestockRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestockRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly RestockRequest $restockRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rr      = $this->restockRequest;
        $showUrl = route('inventory.restock.show', $rr->id);

        return (new MailMessage)
            ->subject("Restock Request — {$rr->request_number}")
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("A restock request has been submitted and requires your approval.")
            ->line('')
            ->line('**Request Details**')
            ->line('Request No.: ' . $rr->request_number)
            ->line('Item: ' . ($rr->item->name ?? '—'))
            ->line('Quantity: ' . number_format((float) $rr->quantity_requested, 3) . ' ' . ($rr->item->unit ?? ''))
            ->line('Estimated Unit Cost: ₦' . number_format((float) $rr->unit_cost, 2))
            ->line('Total Cost: ₦' . number_format($rr->totalCost(), 2))
            ->line('Supplier: ' . ($rr->supplier_name ?: 'Not specified'))
            ->line('Requested by: ' . ($rr->requester->name ?? '—'))
            ->action('Review Request', $showUrl)
            ->salutation('AccountTaxNG Inventory');
    }
}
