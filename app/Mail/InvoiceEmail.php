<?php

namespace App\Mail;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}

    public function envelope(): Envelope
    {
        $subject = "Invoice {$this->invoice->invoice_number} from {$this->invoice->tenant->name}";

        return new Envelope(
            to:      [$this->invoice->customer->email],
            replyTo: [$this->invoice->tenant->email],
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice');
    }

    public function attachments(): array
    {
        $this->invoice->loadMissing(['customer', 'items', 'tenant']);

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $this->invoice])
            ->setPaper('a4', 'portrait')
            ->output();

        return [
            Attachment::fromData(
                fn () => $pdf,
                "Invoice-{$this->invoice->invoice_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
