<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

/**
 * Deliberately a plain Mailable, not a Notification, and deliberately does
 * NOT implement ShouldQueue. This alert exists to report that the queue
 * worker is down — if sending it were itself queued, it would never arrive
 * during the exact failure it's meant to report. Always send via
 * Mail::send(), never ->queue().
 */
class QueueWorkerStatusAlert extends Mailable
{
    public function __construct(
        public readonly bool $isDown,
        public readonly ?Carbon $lastHeartbeat,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isDown
                ? '⚠ AccountTaxNG queue worker appears down'
                : '✅ AccountTaxNG queue worker has recovered',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.queue-worker-alert');
    }
}
