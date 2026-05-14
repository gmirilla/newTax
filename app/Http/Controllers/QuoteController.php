<?php

namespace App\Http\Controllers;

use App\Jobs\SendQuoteEmail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;

        $quotes = Quote::where('tenant_id', $tenant->id)
            ->with(['customer', 'creator'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('quote_number', 'like', "%{$v}%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$v}%"));
            }))
            ->orderBy('quote_date', 'desc')
            ->paginate(20);

        $summary = [
            'draft'    => Quote::where('tenant_id', $tenant->id)->where('status', 'draft')->count(),
            'sent'     => Quote::where('tenant_id', $tenant->id)->where('status', 'sent')->count(),
            'accepted' => Quote::where('tenant_id', $tenant->id)->where('status', 'accepted')->count(),
            'declined' => Quote::where('tenant_id', $tenant->id)->where('status', 'declined')->count(),
        ];

        return view('quotes.index', compact('quotes', 'summary'));
    }

    public function create(Request $request): View
    {
        $tenant    = $request->user()->tenant;
        $customers = Customer::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('quotes.create', compact('customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id'   => 'required|exists:customers,id',
            'quote_date'    => 'required|date',
            'expiry_date'   => 'required|date|after_or_equal:quote_date',
            'reference'     => 'nullable|string|max:100',
            'vat_applicable'=> 'boolean',
            'wht_applicable'=> 'boolean',
            'wht_rate'      => 'nullable|numeric|in:5,10',
            'discount_amount'=> 'nullable|numeric|min:0',
            'notes'         => 'nullable|string',
            'terms'         => 'nullable|string',
            'items'         => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $tenant = $request->user()->tenant;

        $quote = Quote::create([
            'tenant_id'       => $tenant->id,
            'customer_id'     => $request->customer_id,
            'quote_number'    => $this->generateQuoteNumber($tenant->id),
            'reference'       => $request->reference,
            'quote_date'      => $request->quote_date,
            'expiry_date'     => $request->expiry_date,
            'vat_applicable'  => $request->boolean('vat_applicable'),
            'wht_applicable'  => $request->boolean('wht_applicable'),
            'wht_rate'        => $request->boolean('wht_applicable') ? ($request->wht_rate ?? 5) : 0,
            'discount_amount' => $request->discount_amount ?? 0,
            'notes'           => $request->notes,
            'terms'           => $request->terms,
            'status'          => $request->input('status', 'draft'),
            'created_by'      => Auth::id(),
        ]);

        foreach ($request->input('items', []) as $index => $itemData) {
            $item = new QuoteItem(array_merge($itemData, [
                'quote_id'       => $quote->id,
                'vat_applicable' => $request->boolean('vat_applicable'),
                'vat_rate'       => Invoice::VAT_RATE,
                'sort_order'     => $index + 1,
            ]));
            $item->calculateTotals();
            $item->save();
        }

        $quote->recalculateTotals();

        return redirect()->route('quotes.show', $quote)
            ->with('success', "Quote {$quote->quote_number} created successfully.");
    }

    public function show(Quote $quote): View
    {
        $quote->load(['customer', 'items', 'creator', 'tenant', 'convertedInvoice']);
        return view('quotes.show', compact('quote'));
    }

    public function edit(Quote $quote): View
    {
        if (!$quote->isEditable()) {
            return redirect()->route('quotes.show', $quote)
                ->with('error', 'Only draft or sent quotes can be edited.');
        }

        $customers = Customer::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $quote->load('items');
        return view('quotes.edit', compact('quote', 'customers'));
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        if (!$quote->isEditable()) {
            return back()->with('error', 'Only draft or sent quotes can be edited.');
        }

        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'quote_date'     => 'required|date',
            'expiry_date'    => 'required|date|after_or_equal:quote_date',
            'reference'      => 'nullable|string|max:100',
            'vat_applicable' => 'boolean',
            'wht_applicable' => 'boolean',
            'wht_rate'       => 'nullable|numeric|in:5,10',
            'discount_amount'=> 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
            'terms'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $quote->update([
            'customer_id'     => $request->customer_id,
            'quote_date'      => $request->quote_date,
            'expiry_date'     => $request->expiry_date,
            'reference'       => $request->reference,
            'vat_applicable'  => $request->boolean('vat_applicable'),
            'wht_applicable'  => $request->boolean('wht_applicable'),
            'wht_rate'        => $request->boolean('wht_applicable') ? ($request->wht_rate ?? 5) : 0,
            'discount_amount' => $request->discount_amount ?? 0,
            'notes'           => $request->notes,
            'terms'           => $request->terms,
        ]);

        $quote->items()->delete();
        foreach ($request->input('items', []) as $index => $itemData) {
            $item = new QuoteItem(array_merge($itemData, [
                'quote_id'       => $quote->id,
                'vat_applicable' => $request->boolean('vat_applicable'),
                'vat_rate'       => Invoice::VAT_RATE,
                'sort_order'     => $index + 1,
            ]));
            $item->calculateTotals();
            $item->save();
        }

        $quote->recalculateTotals();

        return redirect()->route('quotes.show', $quote)
            ->with('success', 'Quote updated successfully.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        if ($quote->status === 'accepted') {
            return back()->with('error', 'Cannot delete an accepted quote.');
        }
        $quote->delete();
        return redirect()->route('quotes.index')->with('success', 'Quote deleted.');
    }

    public function send(Quote $quote): RedirectResponse
    {
        if (!in_array($quote->status, ['draft', 'sent'])) {
            return back()->with('error', 'Only draft quotes can be marked as sent.');
        }

        $quote->update(['status' => 'sent']);
        $quote->loadMissing(['customer', 'items', 'tenant']);

        $emailSent = false;
        if ($quote->customer?->email) {
            SendQuoteEmail::dispatch($quote);
            $emailSent = true;
        }

        $message = "Quote {$quote->quote_number} marked as sent.";
        if ($emailSent) {
            $message .= " A copy has been emailed to {$quote->customer->email}.";
        } elseif ($quote->customer && !$quote->customer->email) {
            $message .= " No email on file for this customer — email not sent.";
        }

        return back()->with('success', $message);
    }

    public function decline(Quote $quote): RedirectResponse
    {
        if (!in_array($quote->status, ['sent', 'draft'])) {
            return back()->with('error', 'Only active quotes can be declined.');
        }
        $quote->update(['status' => 'declined']);
        return back()->with('success', 'Quote marked as declined.');
    }

    /**
     * Accept a quote and convert it into a real invoice.
     */
    public function accept(Quote $quote): RedirectResponse
    {
        if ($quote->status === 'accepted') {
            return redirect()->route('invoices.show', $quote->converted_invoice_id)
                ->with('error', 'This quote has already been converted to an invoice.');
        }

        if (!in_array($quote->status, ['sent', 'draft'])) {
            return back()->with('error', 'Only active quotes can be accepted.');
        }

        $quote->load('items');
        $tenant = Auth::user()->tenant;

        // Build invoice number in the same format as InvoiceService.
        // lockForUpdate() cannot be combined with COUNT(*) on PostgreSQL, so we
        // rely on the unique(tenant_id, invoice_number) DB constraint as the
        // collision guard instead — works on both pgsql and mysql.
        $month         = now()->format('Ym');
        $lastInv       = Invoice::where('tenant_id', $tenant->id)
            ->where('invoice_number', 'like', "INV-{$month}-%")
            ->count();
        $invoiceNumber = 'INV-' . $month . '-' . str_pad($lastInv + 1, 4, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'tenant_id'       => $tenant->id,
            'customer_id'     => $quote->customer_id,
            'invoice_number'  => $invoiceNumber,
            'reference'       => $quote->reference,
            'invoice_date'    => now()->toDateString(),
            'due_date'        => now()->addDays(30)->toDateString(),
            'vat_applicable'  => $quote->vat_applicable,
            'wht_applicable'  => $quote->wht_applicable,
            'wht_rate'        => $quote->wht_rate,
            'discount_amount' => $quote->discount_amount,
            'subtotal'        => 0,
            'vat_amount'      => 0,
            'wht_amount'      => 0,
            'total_amount'    => 0,
            'amount_paid'     => 0,
            'balance_due'     => 0,
            'notes'           => $quote->notes,
            'terms'           => $quote->terms ?? 'Payment due within 30 days. Please quote invoice number on remittance.',
            'currency'        => $quote->currency,
            'status'          => 'draft',
            'created_by'      => Auth::id(),
        ]);

        foreach ($quote->items as $index => $quoteItem) {
            $item = new InvoiceItem([
                'invoice_id'     => $invoice->id,
                'description'    => $quoteItem->description,
                'quantity'       => $quoteItem->quantity,
                'unit_price'     => $quoteItem->unit_price,
                'vat_applicable' => $quoteItem->vat_applicable,
                'vat_rate'       => $quoteItem->vat_rate,
                'sort_order'     => $index + 1,
            ]);
            $item->calculateTotals();
            $item->save();
        }

        $invoice->load('items');
        $invoice->recalculateTotals();

        $quote->update([
            'status'               => 'accepted',
            'converted_invoice_id' => $invoice->id,
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Quote {$quote->quote_number} accepted — Invoice {$invoice->invoice_number} created.");
    }

    public function preview(Request $request): JsonResponse
    {
        $tenant   = $request->user()->tenant;
        $customer = Customer::where('tenant_id', $tenant->id)
            ->find($request->input('customer_id'));

        $items = collect($request->input('items', []))->map(function ($item) use ($request) {
            $subtotal  = round(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 2);
            $vatAmount = ($request->boolean('vat_applicable') && ($item['vat_applicable'] ?? true))
                ? round($subtotal * 7.5 / 100, 2) : 0;
            return (object) [
                'description'    => $item['description'] ?? '',
                'quantity'       => (float) ($item['quantity'] ?? 0),
                'unit_price'     => (float) ($item['unit_price'] ?? 0),
                'subtotal'       => $subtotal,
                'vat_applicable' => (bool) ($item['vat_applicable'] ?? true),
                'vat_amount'     => $vatAmount,
                'total'          => $subtotal + $vatAmount,
            ];
        });

        $subtotal  = $items->sum('subtotal');
        $vatAmount = $request->boolean('vat_applicable') ? $items->sum('vat_amount') : 0;
        $whtRate   = (float) ($request->input('wht_rate', 5));
        $whtAmount = $request->boolean('wht_applicable') ? round($subtotal * $whtRate / 100, 2) : 0;
        $discount  = (float) ($request->input('discount_amount', 0));
        $total     = $subtotal + $vatAmount - $whtAmount - $discount;

        $quote = (object) [
            'tenant'          => $tenant,
            'customer'        => $customer ?? (object)['name'=>'—','address'=>'','city'=>'','state'=>'','email'=>'','tin'=>''],
            'quote_number'    => 'PREVIEW',
            'quote_date'      => Carbon::parse($request->input('quote_date', today())),
            'expiry_date'     => Carbon::parse($request->input('expiry_date', today()->addDays(30))),
            'reference'       => $request->input('reference'),
            'status'          => 'draft',
            'items'           => $items,
            'vat_applicable'  => $request->boolean('vat_applicable'),
            'wht_applicable'  => $request->boolean('wht_applicable'),
            'wht_rate'        => $whtRate,
            'subtotal'        => $subtotal,
            'vat_amount'      => $vatAmount,
            'wht_amount'      => $whtAmount,
            'discount_amount' => $discount,
            'total_amount'    => $total,
            'notes'           => $request->input('notes'),
            'terms'           => $request->input('terms'),
        ];

        $html = view('quotes.pdf', compact('quote'))->render();

        return response()->json(['html' => $html]);
    }

    public function downloadPdf(Quote $quote)
    {
        $quote->load(['customer', 'items', 'tenant']);

        $pdf = Pdf::loadView('quotes.pdf', compact('quote'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("ProformaInvoice-{$quote->quote_number}.pdf");
    }

    private function generateQuoteNumber(int $tenantId): string
    {
        $month = now()->format('Ym');
        $count = Quote::where('tenant_id', $tenantId)
            ->where('quote_number', 'like', "QUO-{$month}-%")
            ->count();
        return 'QUO-' . $month . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
