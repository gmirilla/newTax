<?php

namespace App\Mail;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionActivated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly Plan $plan,
        public readonly string $type = 'subscription', // subscription | upgrade_proration
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->type === 'upgrade_proration'
            ? "You've upgraded to {$this->plan->name} on AccountTaxNG"
            : "Welcome to {$this->plan->name} — AccountTaxNG";

        return new Envelope(
            to: [$this->tenant->email],
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription-activated');
    }
}
