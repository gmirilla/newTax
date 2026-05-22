<?php

namespace App\Mail;

use App\Models\SystemNotification;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SystemNotification $notification,
        public readonly Tenant $tenant,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AccountTaxNG] ' . $this->notification->title,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.system-notification');
    }
}
