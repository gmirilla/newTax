<?php

namespace App\Events\FIRS;

use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever an invoice's NRS status changes.
 * Broadcast on the tenant's private channel so the UI can update in real time.
 */
class InvoiceStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string  $previousStatus,
    ) {}

    /**
     * Broadcast on: private-tenant.{tenant_id}
     * Client subscribes once per session and receives all NRS updates.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->invoice->tenant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'firs.invoice.status-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'invoice_id'      => $this->invoice->id,
            'invoice_number'  => $this->invoice->invoice_number,
            'firs_status'     => $this->invoice->firs_status,
            'previous_status' => $this->previousStatus,
        ];
    }
}
