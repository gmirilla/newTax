<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $expiryDate,
        public readonly string $nextPlanName = 'Free',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->tenant->email],
            subject: 'Subscription cancelled — AccountTaxNG',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription-cancelled');
    }
}
