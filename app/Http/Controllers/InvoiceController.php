<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService    $invoiceService,
        private readonly InvoiceRepository $invoiceRepository
    ) {}

    public function index(Request $request): View
    {
        $tenant   = $request->user()->tenant;
        $invoices = $this->invoiceRepository->paginate($tenant, $request->only([
            'status', 'customer_id', 'date_from', 'date_to', 'search',
        ]));

        $summary = $this->invoiceService->getDashboardSummary($tenant);

        return view('invoices.index', compact('invoices', 'summary'));
    }

    public function create(Request $request): View
    {
        $tenant    = $request->user()->tenant;
        $customers = Customer::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('invoices.create', compact('customers'));
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $tenant  = $request->user()->tenant;
        $invoice = $this->invoiceService->create(
            $tenant,
            $request->validated(),
            $request->input('items', [])
        );

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created successfully.");
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items', 'payments', 'creator', 'tenant']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        $customers = Customer::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get();

        $invoice->load(['items']);

        return view('invoices.edit', compact('invoice', 'customers'));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        // Prevent editing paid invoices
        if (in_array($invoice->status, ['paid', 'void'])) {
            return back()->with('error', 'Cannot edit a paid or voided invoice.');
        }

        $invoice->update($request->only([
            'customer_id', 'reference', 'invoice_date', 'due_date',
            'vat_applicable', 'wht_applicable', 'wht_rate',
            'discount_amount', 'notes', 'terms',
        ]));

        // Rebuild line items
        $invoice->items()->delete();
        foreach ($request->input('items', []) as $index => $itemData) {
            $item = new \App\Models\InvoiceItem(array_merge($itemData, [
                'invoice_id' => $invoice->id,
                'vat_rate'   => Invoice::VAT_RATE,
                'sort_order' => $index + 1,
            ]));
            $item->calculateTotals();
            $item->save();
        }

        $invoice->load('items');
        $invoice->recalculateTotals();

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot delete a paid invoice. Void it instead.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted.');
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'payment_date' => 'required|date',
            'amount'       => 'required|numeric|min:0.01|max:' . $invoice->balance_due,
            'method'       => 'required|in:cash,bank_transfer,cheque,pos,online',
            'reference'    => 'nullable|string|max:100',
        ]);

        $this->invoiceService->recordPayment($invoice, $request->all());

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items', 'tenant']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }

    public function sendEmail(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        // Mark as sent
        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent']);
        }

        // TODO: Dispatch SendInvoiceEmail job
        // SendInvoiceEmail::dispatch($invoice);

        return back()->with('success', "Invoice sent to {$invoice->customer->email}.");
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->amount_paid > 0) {
            return back()->with('error', 'Cannot void an invoice with recorded payments.');
        }

        $invoice->update(['status' => 'void']);

        return back()->with('success', 'Invoice voided.');
    }
}
