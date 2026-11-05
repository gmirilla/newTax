<?php

namespace App\Notifications;

use App\Models\FirmClientLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FirmLinkRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly FirmClientLink $link,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->link->clientTenant->name} wants you to manage their books")
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("**{$this->link->clientTenant->name}** has requested that your firm manage their bookkeeping on AccountTaxNG.")
            ->line('Review and approve the request from your Clients page.')
            ->action('View Pending Requests', route('partners.clients.index'))
            ->salutation('AccountTaxNG');
    }
}
