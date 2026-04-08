<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Imports\InvoicesImport;
use App\Jobs\FIRS\ProcessFirsInvoiceJob;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceFirsSubmission;
use App\Models\TenantFirsCredential;
use App\Repositories\InvoiceRepository;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

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

        $bankAccounts = Account::where('tenant_id', $invoice->tenant_id)
            ->where('type', 'asset')
            ->whereIn('sub_type', ['bank', 'cash'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('invoices.show', compact('invoice', 'bankAccounts'));
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
            'payment_date'       => 'required|date',
            'amount'             => 'required|numeric|min:0.01|max:' . $invoice->balance_due,
            'method'             => 'required|in:cash,bank_transfer,cheque,pos,online',
            'payment_account_id' => 'required|exists:accounts,id',
            'reference'          => 'nullable|string|max:100',
        ]);

        $this->invoiceService->recordPayment($invoice, $request->only([
            'payment_date', 'amount', 'method', 'payment_account_id', 'reference', 'notes',
        ]));

        return back()->with('success', 'Payment recorded and journal entry posted.');
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

        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent']);
        }

        // Post revenue recognition journal (DR AR / CR Revenue / CR VAT Payable)
        // Idempotent — skips if already posted or if required accounts are missing
        $invoice->load('tenant');
        $this->invoiceService->postRevenueJournal($invoice);

        // TODO: Dispatch SendInvoiceEmail job
        // SendInvoiceEmail::dispatch($invoice);

        return back()->with('success', "Invoice {$invoice->invoice_number} sent and revenue recognised in ledger.");
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

    /**
     * Submit an invoice to FIRS for e-Invoicing validation and signing.
     *
     * Only invoices with status 'sent' or 'paid' may be submitted.
     * If a previous submission failed the job is re-queued (retry flow).
     */
    public function submitToFirs(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (! in_array($invoice->status, ['sent', 'paid'], true)) {
            return back()->with('error', 'Only sent or paid invoices can be submitted to FIRS.');
        }

        if ($invoice->firs_status === 'signed') {
            return back()->with('error', 'This invoice has already been signed by FIRS.');
        }

        // Ensure the tenant has active FIRS credentials configured
        $hasCredentials = TenantFirsCredential::where('tenant_id', $invoice->tenant_id)
            ->where('is_active', true)
            ->exists();

        if (! $hasCredentials) {
            return back()->with('error', 'FIRS credentials not configured. Go to Settings → FIRS to set up your credentials.');
        }

        // Reset a previously failed submission so the job can retry
        $submission = InvoiceFirsSubmission::where('invoice_id', $invoice->id)->first();
        if ($submission && $submission->status === 'failed') {
            $submission->update(['status' => 'pending']);
        }

        $invoice->update(['firs_status' => 'pending']);

        ProcessFirsInvoiceJob::dispatch($invoice);

        return back()->with('success', 'Invoice queued for FIRS submission. You will be notified when signing is complete.');
    }

    public function importForm(): View
    {
        return view('invoices.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
        ]);

        $tenant  = $request->user()->tenant;
        $import  = new InvoicesImport($tenant, $this->invoiceService);

        Excel::import($import, $request->file('file'));

        $message = "{$import->imported} invoice(s) imported successfully.";
        if ($import->skipped > 0) {
            $message .= " {$import->skipped} row(s) skipped.";
        }

        if (!empty($import->errors)) {
            session()->flash('import_errors', $import->errors);
        }

        $type = $import->imported > 0 ? 'success' : 'error';
        return redirect()->route('invoices.import')
            ->with($type, $message);
    }

    public function downloadSample(): Response
    {
        $csv = implode("\n", [
            // Header row
            'invoice_number,customer_name,invoice_date,due_date,reference,vat_applicable,wht_applicable,wht_rate,discount_amount,notes,terms,item_description,item_quantity,item_unit_price,item_vat',
            // Invoice 1 — two line items (same invoice_number = same invoice)
            'INV-2026-0001,Acme Nigeria Ltd,2026-01-15,2026-02-14,PO-1001,yes,no,0,0,,Net 30,Web Design Services,1,500000,yes',
            'INV-2026-0001,Acme Nigeria Ltd,2026-01-15,2026-02-14,PO-1001,yes,no,0,0,,Net 30,Domain Registration (1yr),1,75000,yes',
            // Invoice 2 — single line, WHT deducted
            'INV-2026-0002,Beta Corp Ltd,2026-01-20,2026-02-19,PO-2002,yes,yes,5,0,Q1 consulting,Net 30,IT Consulting — January,10,100000,yes',
            // Invoice 3 — auto-number (leave invoice_number blank)
            ',Gamma Stores,2026-01-22,2026-02-21,,yes,no,0,5000,Discount applied,,Office Supplies,20,15000,no',
        ]);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="invoice_import_sample.csv"',
        ]);
    }
}
