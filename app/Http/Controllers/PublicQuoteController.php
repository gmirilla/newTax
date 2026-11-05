<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicQuoteController extends Controller
{
    public function show(string $token): View
    {
        $quote = Quote::withoutGlobalScope('tenant')
            ->with(['tenant', 'customer', 'items'])
            ->where('public_token', $token)
            ->firstOrFail();

        return view('quotes.public', compact('quote'));
    }

    public function downloadPdf(string $token): Response
    {
        $quote = Quote::withoutGlobalScope('tenant')
            ->with(['tenant', 'customer', 'items'])
            ->where('public_token', $token)
            ->firstOrFail();

        $accent     = $quote->tenant->accentColor();
        $accentText = $quote->tenant->accentTextColor();

        $pdf = Pdf::loadView('quotes.pdf', compact('quote', 'accent', 'accentText'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("ProformaInvoice-{$quote->quote_number}.pdf");
    }
}
