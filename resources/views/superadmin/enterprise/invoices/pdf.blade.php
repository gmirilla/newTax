<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1f2937; margin: 0; padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 32px; }
        .brand { font-size: 22px; font-weight: bold; color: #4338ca; }
        .brand-sub { font-size: 11px; color: #6b7280; }
        .invoice-title { font-size: 28px; font-weight: bold; text-align: right; }
        .invoice-meta { text-align: right; font-size: 11px; color: #6b7280; }
        .section-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin-bottom: 4px; }
        .parties { display: flex; gap: 40px; margin-bottom: 32px; }
        .party { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; padding: 8px 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 15px; border-top: 2px solid #e5e7eb; border-bottom: none; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .paid { background: #d1fae5; color: #065f46; }
        .sent { background: #dbeafe; color: #1e40af; }
        .overdue { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 40px; font-size: 11px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 16px; }
        .notes { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; font-size: 12px; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">AccountTaxNG.com</div>
            <div class="brand-sub">Enterprise AgreementInvoice</div>
        </div>
        <div>
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-meta">
                <div>{{ $invoice->invoice_number }}</div>
                <div>Issued: {{ now()->format('d M Y') }}</div>
                <div>
                    <span class="status-badge {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="parties">
        <div class="party">
            <div class="section-title">From</div>
            <strong>AccountTaxNG.com</strong><br>
            billing@accounttaxng.com
        </div>
        <div class="party">
            <div class="section-title">Billed To</div>
            <strong>{{ $tenant->name }}</strong><br>
            {{ $tenant->email }}
        </div>
        <div class="party">
            <div class="section-title">Invoice Details</div>
            Due Date: <strong>{{ $invoice->due_date->format('d M Y') }}</strong><br>
            Period: {{ $invoice->period_start->format('d M Y') }} – {{ $invoice->period_end->format('d M Y') }}<br>
            Terms: {{ $invoice->agreement->payment_terms_days }} days
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->agreement->plan?->name }} Subscription<br>
                    <small style="color:#6b7280">{{ $invoice->period_start->format('d M Y') }} – {{ $invoice->period_end->format('d M Y') }}</small>
                </td>
                <td class="text-right">{{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Due</td>
                <td class="text-right">₦{{ number_format($invoice->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if($invoice->notes)
    <div class="notes">{{ $invoice->notes }}</div>
    @endif

    @if($invoice->paid_at)
    <p style="color:#065f46;background:#d1fae5;padding:8px 12px;border-radius:6px;font-size:12px;">
        Payment received {{ \Carbon\Carbon::parse($invoice->paid_at)->format('d M Y') }}
        via {{ str_replace('_', ' ', ucfirst($invoice->payment_method ?? '')) }}
        @if($invoice->payment_reference) · Ref: {{ $invoice->payment_reference }} @endif
    </p>
    @endif

    <div class="footer">
        Payment via bank transfer. Please quote invoice number {{ $invoice->invoice_number }} as payment reference.<br>
        Questions? Contact billing@accounttaxng.com
    </div>
</body>
</html>
