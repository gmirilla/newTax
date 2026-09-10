<?php

namespace App\Mail;

use App\Models\StorefrontOrder;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewStorefrontOrder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly StorefrontOrder $order,
        public readonly Tenant $tenant,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->tenant->email],
            subject: "New storefront order {$this->order->order_number} — {$this->tenant->name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-storefront-order');
    }
}
