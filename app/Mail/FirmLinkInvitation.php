<?php

namespace App\Mail;

use App\Models\FirmClientLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FirmLinkInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly FirmClientLink $link,
        public readonly string $recipientEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->recipientEmail],
            subject: "{$this->link->firmTenant->name} would like to manage your books on AccountTaxNG",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.firm-link-invitation');
    }
}
