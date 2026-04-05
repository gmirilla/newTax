<?php

namespace App\Jobs\FIRS;

use App\Events\FIRS\InvoiceStatusUpdated;
use App\Models\Invoice;
use App\Models\InvoiceFirsSubmission;
use App\Models\TenantFirsCredential;
use App\Services\FIRS\FirsApiClient;
use App\Services\FIRS\IrnService;
use App\Services\FIRS\UblPayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Drives the full FIRS e-Invoicing pipeline for a single invoice.
 *
 * State machine on invoice.firs_status:
 *   pending → validating → signing → signed
 *                        ↘ failed (any step)
 *
 * The job is idempotent: if the invoice already has a submission record with
 * a terminal status (signed/failed), the job exits immediately.
 */
class ProcessFirsInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly Invoice $invoice
    ) {}

    public function handle(
        IrnService       $irnService,
        UblPayloadBuilder $ublBuilder,
    ): void {
        $invoice    = $this->invoice->fresh(['tenant', 'customer', 'items']);
        $credential = TenantFirsCredential::forTenant($invoice->tenant_id);
        $client     = new FirsApiClient($credential);

        // ── Get or create submission record ───────────────────────────────────
        $submission = InvoiceFirsSubmission::firstOrCreate(
            ['invoice_id' => $invoice->id],
            ['tenant_id'  => $invoice->tenant_id, 'status' => 'pending'],
        );

        // Skip if already in a terminal state
        if (in_array($submission->status, ['signed', 'failed'], true)) {
            return;
        }

        $submission->incrementAttempts();

        // ── Step 1: Generate / reuse IRN ──────────────────────────────────────
        $irn = $submission->irn ?? $irnService->generate($invoice);
        if (! $submission->irn) {
            $submission->update(['irn' => $irn]);
        }

        // ── Step 2: Build UBL payload ─────────────────────────────────────────
        $payload = $ublBuilder->build($invoice, $irn);

        // ── Step 3: Validate ──────────────────────────────────────────────────
        $this->transitionInvoice($invoice, 'validating');
        $submission->transitionTo('validating');

        $client->validateInvoice($payload);

        $submission->transitionTo('validated');

        // ── Step 4: Sign ──────────────────────────────────────────────────────
        $this->transitionInvoice($invoice, 'signing');
        $submission->transitionTo('signing');

        $signResponse = $client->signInvoice($payload);

        // ── Step 5: Persist FIRS-issued values ────────────────────────────────
        $submission->update([
            'status'        => 'signed',
            'csid'          => $signResponse['csid']   ?? null,
            'qr_code_data'  => $signResponse['qrCode'] ?? null,
            'firs_response' => $signResponse,
        ]);

        $previousStatus = $invoice->firs_status;
        $invoice->update(['firs_status' => 'signed']);

        InvoiceStatusUpdated::dispatch($invoice->fresh(), $previousStatus);
    }

    /**
     * Called by Laravel when all retries are exhausted.
     * Marks both the invoice and submission as failed.
     */
    public function failed(Throwable $exception): void
    {
        $invoice    = $this->invoice->fresh();
        $submission = InvoiceFirsSubmission::where('invoice_id', $invoice->id)->first();

        if ($submission && ! in_array($submission->status, ['signed'], true)) {
            $submission->update(['status' => 'failed']);
        }

        $previousStatus = $invoice->firs_status;

        if ($invoice->firs_status !== 'signed') {
            $invoice->update(['firs_status' => 'failed']);
            InvoiceStatusUpdated::dispatch($invoice->fresh(), $previousStatus);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Update invoice.firs_status with a forward-only guard.
     */
    private function transitionInvoice(Invoice $invoice, string $newStatus): void
    {
        $order   = ['draft', 'pending', 'validating', 'signing', 'signed', 'failed'];
        $current = array_search($invoice->firs_status, $order, true);
        $target  = array_search($newStatus, $order, true);

        if ($target !== false && $target > $current) {
            $invoice->update(['firs_status' => $newStatus]);
        }
    }
}
