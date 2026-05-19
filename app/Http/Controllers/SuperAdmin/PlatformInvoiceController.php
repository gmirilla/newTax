<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EnterpriseAgreement;
use App\Models\PlatformInvoice;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PlatformInvoiceController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $invoices = PlatformInvoice::where('tenant_id', $tenant->id)
            ->with('agreement.plan')
            ->orderByDesc('created_at')
            ->get();

        // Mark any sent invoices past due_date as overdue
        $invoices->where('status', PlatformInvoice::STATUS_SENT)
            ->where('due_date', '<', now()->toDateString())
            ->each(fn($inv) => $inv->update(['status' => PlatformInvoice::STATUS_OVERDUE]));

        return view('superadmin.enterprise.invoices.index', compact('tenant', 'invoices'));
    }

    public function create(Tenant $tenant): View
    {
        $agreement = EnterpriseAgreement::where('tenant_id', $tenant->id)
            ->where('status', EnterpriseAgreement::STATUS_ACTIVE)
            ->with('plan')
            ->latest()
            ->firstOrFail();

        return view('superadmin.enterprise.invoices.create', compact('tenant', 'agreement'));
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'agreement_id' => 'required|exists:enterprise_agreements,id',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after:period_start',
            'amount'       => 'required|numeric|min:0',
            'due_date'     => 'required|date',
            'notes'        => 'nullable|string|max:2000',
        ]);

        $agreement = EnterpriseAgreement::where('id', $validated['agreement_id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $invoice = PlatformInvoice::create([
            ...$validated,
            'invoice_number' => PlatformInvoice::nextNumber(),
            'tenant_id'      => $tenant->id,
            'status'         => PlatformInvoice::STATUS_DRAFT,
            'created_by'     => auth()->id(),
        ]);

        AuditLog::create([
            'tenant_id'      => $tenant->id,
            'user_id'        => auth()->id(),
            'event'          => 'superadmin.platform_invoice_created',
            'auditable_type' => PlatformInvoice::class,
            'auditable_id'   => $invoice->id,
            'new_values'     => ['invoice_number' => $invoice->invoice_number, 'amount' => $invoice->amount],
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'superadmin,enterprise',
        ]);

        return redirect()->route('superadmin.enterprises.invoices.show', [$tenant, $invoice])
            ->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Tenant $tenant, PlatformInvoice $invoice): View
    {
        abort_unless($invoice->tenant_id === $tenant->id, 404);
        $invoice->load('agreement.plan', 'tenant');
        return view('superadmin.enterprise.invoices.show', compact('tenant', 'invoice'));
    }

    public function send(Tenant $tenant, PlatformInvoice $invoice): RedirectResponse
    {
        abort_unless($invoice->tenant_id === $tenant->id, 404);
        abort_unless($invoice->status === PlatformInvoice::STATUS_DRAFT, 422);

        $invoice->update(['status' => PlatformInvoice::STATUS_SENT]);

        // TODO: dispatch SendPlatformInvoiceEmail job when mail templates are ready

        return redirect()->route('superadmin.enterprises.invoices.show', [$tenant, $invoice])
            ->with('success', 'Invoice marked as sent.');
    }

    public function markPaid(Request $request, Tenant $tenant, PlatformInvoice $invoice): RedirectResponse
    {
        abort_unless($invoice->tenant_id === $tenant->id, 404);
        abort_unless(in_array($invoice->status, [PlatformInvoice::STATUS_SENT, PlatformInvoice::STATUS_OVERDUE]), 422);

        $validated = $request->validate([
            'payment_method'    => 'required|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
            'paid_at'           => 'required|date',
        ]);

        $invoice->update([
            ...$validated,
            'status'  => PlatformInvoice::STATUS_PAID,
            'paid_at' => $validated['paid_at'],
        ]);

        AuditLog::create([
            'tenant_id'      => $tenant->id,
            'user_id'        => auth()->id(),
            'event'          => 'superadmin.platform_invoice_paid',
            'auditable_type' => PlatformInvoice::class,
            'auditable_id'   => $invoice->id,
            'new_values'     => $validated,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'superadmin,enterprise',
        ]);

        return redirect()->route('superadmin.enterprises.invoices.show', [$tenant, $invoice])
            ->with('success', "Invoice {$invoice->invoice_number} marked as paid.");
    }

    public function void(Tenant $tenant, PlatformInvoice $invoice): RedirectResponse
    {
        abort_unless($invoice->tenant_id === $tenant->id, 404);
        abort_unless($invoice->status !== PlatformInvoice::STATUS_PAID, 422);

        $invoice->update(['status' => PlatformInvoice::STATUS_VOID]);

        return redirect()->route('superadmin.enterprises.invoices.index', $tenant)
            ->with('success', "Invoice {$invoice->invoice_number} voided.");
    }

    public function pdf(Tenant $tenant, PlatformInvoice $invoice): Response
    {
        abort_unless($invoice->tenant_id === $tenant->id, 404);
        $invoice->load('agreement.plan', 'tenant');

        $pdf = Pdf::loadView('superadmin.enterprise.invoices.pdf', compact('invoice', 'tenant'));

        return $pdf->download($invoice->invoice_number . '.pdf');
    }
}
