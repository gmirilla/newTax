<?php

namespace App\Services\FIRS;

use App\Models\Invoice;

/**
 * Generates Invoice Reference Numbers (IRN) locally before submission to FIRS.
 *
 * Format: {TIN}-{InvoiceNumber}-{DateStamp}
 *
 * The IRN is deterministic and idempotent for the same invoice — re-calling
 * generate() on the same invoice always returns the same IRN, so it is safe
 * to call multiple times (e.g. on job retries).
 */
class IrnService
{
    /**
     * Generate the IRN for an invoice.
     *
     * @param  Invoice  $invoice  Must have tenant loaded (eager or lazy).
     * @return string             e.g. "1234567890-INV-2026-0001-20260115"
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing('tenant');

        $tin          = $this->normaliseTin($invoice->tenant->tax_id ?? 'NOTFOUND');
        $invoiceNum   = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $invoice->invoice_number));
        $dateStamp    = $invoice->invoice_date->format('Ymd');

        return "{$tin}-{$invoiceNum}-{$dateStamp}";
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function normaliseTin(string $tin): string
    {
        // Strip spaces and hyphens; FIRS TINs are 10 digits
        return preg_replace('/[\s\-]/', '', $tin);
    }
}
