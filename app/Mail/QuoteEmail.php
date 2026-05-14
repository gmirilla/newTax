<?php

namespace App\Mail;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Quote $quote) {}

    public function envelope(): Envelope
    {
        $subject = "Quotation {$this->quote->quote_number} from {$this->quote->tenant->name}";

        return new Envelope(
            to:      [$this->quote->customer->email],
            replyTo: [$this->quote->tenant->email],
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quote');
    }

    public function attachments(): array
    {
        $this->quote->loadMissing(['customer', 'items', 'tenant']);

        $pdf = Pdf::loadView('quotes.pdf', ['quote' => $this->quote])
            ->setPaper('a4', 'portrait')
            ->output();

        return [
            Attachment::fromData(
                fn () => $pdf,
                "Quote-{$this->quote->quote_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
