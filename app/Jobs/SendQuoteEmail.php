<?php

namespace App\Jobs;

use App\Mail\QuoteEmail;
use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendQuoteEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 3;
    public int $backoff = 120;

    public function __construct(public readonly Quote $quote) {}

    public function handle(): void
    {
        $this->quote->loadMissing(['customer', 'items', 'tenant']);

        if (empty($this->quote->customer?->email)) {
            return;
        }

        Mail::send(new QuoteEmail($this->quote));
    }
}
