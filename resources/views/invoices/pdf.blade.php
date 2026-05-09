<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; background: #fff; }

        .container { max-width: 760px; margin: 0 auto; padding: 30px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 30px; }
        .header-left { display: table-cell; vertical-align: top; width: 55%; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; }

        .company-name { font-size: 20px; font-weight: bold; color: #008751; margin-bottom: 4px; }
        .company-detail { font-size: 10px; color: #666; line-height: 1.6; }
        .tin-badge { display: inline-block; background: #f0fdf4; border: 1px solid #008751; color: #008751;
                     padding: 3px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; margin-top: 4px; }

        .invoice-title { font-size: 28px; font-weight: bold; color: #008751; }
        .invoice-meta { margin-top: 8px; }
        .invoice-meta td { padding: 2px 0; }
        .invoice-meta .label { color: #666; width: 90px; font-size: 10px; }
        .invoice-meta .value { font-weight: 600; font-size: 11px; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 10px;
                        font-weight: bold; text-transform: uppercase; margin-top: 5px; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-draft { background: #f3f4f6; color: #374151; }

        /* Bill To */
        .parties { display: table; width: 100%; margin-bottom: 25px; }
        .party-from, .party-to { display: table-cell; width: 50%; vertical-align: top; }
        .party-to { padding-left: 20px; }
        .party-label { font-size: 9px; font-weight: bold; text-transform: uppercase;
                       color: #666; letter-spacing: 0.5px; margin-bottom: 6px; }
        .party-name { font-size: 13px; font-weight: bold; color: #111; margin-bottom: 3px; }
        .party-detail { font-size: 10px; color: #555; line-height: 1.7; }

        /* Divider */
        .divider { border: none; border-top: 2px solid #008751; margin: 15px 0; }
        .divider-light { border: none; border-top: 1px solid #e5e7eb; margin: 10px 0; }

        /* Items table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead tr { background: #008751; color: #fff; }
        .items-table thead th { padding: 8px 10px; text-align: left; font-size: 10px;
                                 font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
        .items-table thead th.text-right { text-align: right; }
        .items-table tbody tr { border-bottom: 1px solid #f0f0f0; }
        .items-table tbody tr:nth-child(even) { background: #f9fafb; }
        .items-table tbody td { padding: 8px 10px; font-size: 11px; }
        .items-table tbody td.text-right { text-align: right; }
        .items-table tfoot tr { background: #f0fdf4; }
        .items-table tfoot td { padding: 6px 10px; font-size: 11px; }

        /* Totals */
        .totals-table { width: 280px; margin-left: auto; margin-bottom: 20px; }
        .totals-table td { padding: 5px 10px; font-size: 11px; }
        .totals-table .total-row { background: #008751; color: #fff; font-weight: bold; font-size: 13px; }
        .totals-table .label { color: #666; }
        .totals-table .amount { text-align: right; }
        .vat-note { font-size: 9px; color: #008751; font-weight: bold; }

        /* Tax Box */
        .tax-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px;
                   padding: 10px 14px; margin-bottom: 20px; font-size: 10px; }
        .tax-box-title { font-weight: bold; color: #065f46; margin-bottom: 6px; font-size: 11px; }
        .tax-box table { width: 100%; }
        .tax-box td { padding: 2px 0; color: #374151; }
        .tax-box .amount { text-align: right; font-weight: 600; }

        /* Notes */
        .notes-section { margin-bottom: 20px; }
        .notes-label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #666;
                       letter-spacing: 0.5px; margin-bottom: 4px; }
        .notes-text { font-size: 10px; color: #555; line-height: 1.6; }

        /* Footer */
        .footer { border-top: 1px solid #e5e7eb; padding-top: 12px; text-align: center;
                  font-size: 9px; color: #888; }
        .footer-green { color: #008751; font-weight: bold; }

        /* WHT notice */
        .wht-notice { background: #fef3c7; border: 1px solid #fbbf24; border-radius: 4px;
                      padding: 8px 12px; margin-bottom: 15px; font-size: 10px; color: #92400e; }
    </style>
</head>
<body>
<div class="container">

    {{-- ─── Header ──────────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-left">
            @if($invoice->tenant->logo)
                <img src="{{ public_path('storage/' . $invoice->tenant->logo) }}" height="50" alt="Logo">
            @else
                <div class="company-name">{{ $invoice->tenant->name }}</div>
            @endif
            <div class="company-detail">
                @if($invoice->tenant->address){{ $invoice->tenant->address }}<br>@endif
                @if($invoice->tenant->city){{ $invoice->tenant->city }}, {{ $invoice->tenant->state }}<br>@endif
                @if($invoice->tenant->email)Email: {{ $invoice->tenant->email }}<br>@endif
                @if($invoice->tenant->phone)Tel: {{ $invoice->tenant->phone }}@endif
            </div>
            @if($invoice->tenant->tin)
                <div class="tin-badge">TIN: {{ $invoice->tenant->tin }}</div>
            @endif
            @if($invoice->tenant->vat_registered && $invoice->tenant->vat_number)
                <div class="tin-badge" style="margin-left:4px;">VAT No: {{ $invoice->tenant->vat_number }}</div>
            @endif
            @if($invoice->tenant->rc_number)
                <div class="tin-badge" style="margin-left:4px;">RC: {{ $invoice->tenant->rc_number }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="invoice-title">INVOICE</div>
            <table class="invoice-meta">
                <tr>
                    <td class="label">Invoice No:</td>
                    <td class="value">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td class="label">Date:</td>
                    <td class="value">{{ $invoice->invoice_date->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Due Date:</td>
                    <td class="value">{{ $invoice->due_date->format('d F Y') }}</td>
                </tr>
                @if($invoice->reference)
                <tr>
                    <td class="label">Reference:</td>
                    <td class="value">{{ $invoice->reference }}</td>
                </tr>
                @endif
            </table>
            <div>
                @php
                    $statusClass = match($invoice->status) {
                        'paid'    => 'status-paid',
                        'sent'    => 'status-sent',
                        'overdue' => 'status-overdue',
                        default   => 'status-draft',
                    };
                @endphp
                <span class="status-badge {{ $statusClass }}">{{ strtoupper($invoice->status) }}</span>
            </div>
        </div>
    </div>

    <hr class="divider">

    {{-- ─── Bill To ─────────────────────────────────────────────────────────── --}}
    <div class="parties">
        <div class="party-from">
            <div class="party-label">From</div>
            <div class="party-name">{{ $invoice->tenant->name }}</div>
            <div class="party-detail">
                {{ $invoice->tenant->address }}<br>
                {{ $invoice->tenant->city }}, Nigeria
            </div>
        </div>
        <div class="party-to">
            <div class="party-label">Bill To</div>
            <div class="party-name">{{ $invoice->customer->name }}</div>
            <div class="party-detail">
                @if($invoice->customer->address){{ $invoice->customer->address }}<br>@endif
                @if($invoice->customer->city){{ $invoice->customer->city }}, {{ $invoice->customer->state }}<br>@endif
                @if($invoice->customer->email){{ $invoice->customer->email }}<br>@endif
                @if($invoice->customer->tin)TIN: {{ $invoice->customer->tin }}@endif
            </div>
        </div>
    </div>

    {{-- ─── WHT Notice ─────────────────────────────────────────────────────── --}}
    @if($invoice->wht_applicable)
    <div class="wht-notice">
        ⚠️ <strong>Withholding Tax Notice:</strong> As per NRS regulations, the sum of
        ₦{{ number_format($invoice->wht_amount, 2) }} ({{ $invoice->wht_rate }}% WHT) is deductible
        at source by the customer and should be remitted directly to NRS on our behalf.
        A WHT credit note must be issued.
    </div>
    @endif

    {{-- ─── Line Items ─────────────────────────────────────────────────────── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:40%">Description</th>
                <th class="text-right" style="width:8%">Qty</th>
                <th class="text-right" style="width:15%">Unit Price (₦)</th>
                <th class="text-right" style="width:15%">Subtotal (₦)</th>
                <th class="text-right" style="width:10%">VAT</th>
                <th class="text-right" style="width:12%">Total (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                <td class="text-right">
                    @if($item->vat_applicable)
                        <span class="vat-note">7.5%</span>
                    @else
                        <span style="color:#999">—</span>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ─── Tax Breakdown ───────────────────────────────────────────────────── --}}
    @if($invoice->vat_applicable || $invoice->wht_applicable)
    <div class="tax-box">
        <div class="tax-box-title">🏛️ Nigerian Tax Summary</div>
        <table>
            @if($invoice->vat_applicable)
            <tr>
                <td>Output VAT @ 7.5% (Finance Act 2019 – effective Feb 2020)</td>
                <td class="amount">₦{{ number_format($invoice->vat_amount, 2) }}</td>
            </tr>
            @endif
            @if($invoice->wht_applicable)
            <tr>
                <td>WHT @ {{ $invoice->wht_rate }}% (deducted by customer per CITA/PITA)</td>
                <td class="amount" style="color:#dc2626;">- ₦{{ number_format($invoice->wht_amount, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- ─── Totals ──────────────────────────────────────────────────────────── --}}
    <table class="totals-table">
        <tr>
            <td class="label">Subtotal:</td>
            <td class="amount">₦{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->vat_applicable)
        <tr>
            <td class="label">VAT (7.5%):</td>
            <td class="amount" style="color:#008751; font-weight:bold;">+ ₦{{ number_format($invoice->vat_amount, 2) }}</td>
        </tr>
        @endif
        @if($invoice->discount_amount > 0)
        <tr>
            <td class="label">Discount:</td>
            <td class="amount" style="color:#dc2626;">- ₦{{ number_format($invoice->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($invoice->wht_applicable)
        <tr>
            <td class="label">WHT Deduction:</td>
            <td class="amount" style="color:#dc2626;">- ₦{{ number_format($invoice->wht_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>TOTAL DUE:</td>
            <td class="amount">₦{{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
        @if($invoice->amount_paid > 0)
        <tr style="background:#f9fafb;">
            <td class="label">Amount Paid:</td>
            <td class="amount" style="color:#16a34a;">- ₦{{ number_format($invoice->amount_paid, 2) }}</td>
        </tr>
        <tr style="background:#fef3c7; font-weight:bold;">
            <td>Balance Due:</td>
            <td class="amount">₦{{ number_format($invoice->balance_due, 2) }}</td>
        </tr>
        @endif
    </table>

    {{-- ─── Notes & Terms ───────────────────────────────────────────────────── --}}
    @if($invoice->notes || $invoice->terms)
    <hr class="divider-light">
    @if($invoice->notes)
    <div class="notes-section">
        <div class="notes-label">Notes</div>
        <div class="notes-text">{{ $invoice->notes }}</div>
    </div>
    @endif
    @if($invoice->terms)
    <div class="notes-section">
        <div class="notes-label">Terms & Conditions</div>
        <div class="notes-text">{{ $invoice->terms }}</div>
    </div>
    @endif
    @endif

    {{-- ─── Footer ──────────────────────────────────────────────────────────── --}}
    <hr class="divider-light">
    <div class="footer">
        <p>
            <span class="footer-green">{{ $invoice->tenant->name }}</span> |
            This invoice is valid and complies with Nigerian tax regulations (FIRS). |
            @if($invoice->tenant->tin) TIN: {{ $invoice->tenant->tin }} | @endif
            Generated: {{ now()->format('d M Y, H:i') }}
        </p>
        <p style="margin-top:4px; color:#bbb;">
            In case of query, contact: {{ $invoice->tenant->email }} | {{ $invoice->tenant->phone }}
        </p>
    </div>

</div>
</body>
</html>
