<?php

namespace App\Jobs;

use App\Mail\InvoiceEmail;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 3;
    public int $backoff = 120;

    public function __construct(public readonly Invoice $invoice) {}

    public function handle(): void
    {
        $this->invoice->loadMissing(['customer', 'items', 'tenant']);

        if (empty($this->invoice->customer?->email)) {
            return;
        }

        Mail::send(new InvoiceEmail($this->invoice));
    }
}
