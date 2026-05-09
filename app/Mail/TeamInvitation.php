<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\UserInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly UserInvite $invite,
        public readonly Tenant $tenant,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->invite->email],
            subject: "You've been invited to join {$this->tenant->name} on AccountTaxNG",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.team-invitation');
    }
}
