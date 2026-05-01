<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicInvoiceController extends Controller
{
    public function show(string $token): View
    {
        $invoice = Invoice::with(['tenant', 'customer', 'items', 'payments'])
            ->where('public_token', $token)
            ->whereNotIn('status', ['void', 'cancelled'])
            ->firstOrFail();

        return view('invoices.public', compact('invoice'));
    }

    public function downloadPdf(string $token): Response
    {
        $invoice = Invoice::with(['tenant', 'customer', 'items', 'payments'])
            ->where('public_token', $token)
            ->whereNotIn('status', ['void', 'cancelled'])
            ->firstOrFail();

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }
}
