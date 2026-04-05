<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Proforma Invoice {{ $quote->quote_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; background: #fff; }
        .container { max-width: 760px; margin: 0 auto; padding: 30px; }

        .header { display: table; width: 100%; margin-bottom: 30px; }
        .header-left { display: table-cell; vertical-align: top; width: 55%; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; }

        .company-name { font-size: 20px; font-weight: bold; color: #008751; margin-bottom: 4px; }
        .company-detail { font-size: 10px; color: #666; line-height: 1.6; }
        .tin-badge { display: inline-block; background: #f0fdf4; border: 1px solid #008751; color: #008751;
                     padding: 3px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; margin-top: 4px; }

        .doc-title { font-size: 24px; font-weight: bold; color: #b45309; letter-spacing: 1px; }
        .doc-subtitle { font-size: 11px; color: #92400e; margin-top: 2px; }
        .invoice-meta { margin-top: 10px; }
        .invoice-meta td { padding: 2px 0; }
        .invoice-meta .label { color: #666; width: 90px; font-size: 10px; }
        .invoice-meta .value { font-weight: 600; font-size: 11px; }

        .expiry-badge { display: inline-block; background: #fef3c7; border: 1px solid #fbbf24; color: #92400e;
                        padding: 4px 10px; border-radius: 3px; font-size: 10px; font-weight: bold; margin-top: 6px; }

        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 10px;
                        font-weight: bold; text-transform: uppercase; margin-top: 5px; }
        .status-draft    { background: #f3f4f6; color: #374151; }
        .status-sent     { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-declined { background: #fee2e2; color: #991b1b; }
        .status-expired  { background: #fef3c7; color: #92400e; }

        .parties { display: table; width: 100%; margin-bottom: 25px; }
        .party-from, .party-to { display: table-cell; width: 50%; vertical-align: top; }
        .party-to { padding-left: 20px; }
        .party-label { font-size: 9px; font-weight: bold; text-transform: uppercase;
                       color: #666; letter-spacing: 0.5px; margin-bottom: 6px; }
        .party-name { font-size: 13px; font-weight: bold; color: #111; margin-bottom: 3px; }
        .party-detail { font-size: 10px; color: #555; line-height: 1.7; }

        .divider       { border: none; border-top: 2px solid #008751; margin: 15px 0; }
        .divider-light { border: none; border-top: 1px solid #e5e7eb; margin: 10px 0; }

        /* Proforma watermark stripe */
        .proforma-notice { background: #fffbeb; border: 1px solid #fbbf24; border-radius: 4px;
                           padding: 8px 14px; margin-bottom: 20px; font-size: 10px; color: #92400e; text-align: center;
                           font-weight: bold; letter-spacing: 0.5px; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead tr { background: #92400e; color: #fff; }
        .items-table thead th { padding: 8px 10px; text-align: left; font-size: 10px;
                                 font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
        .items-table thead th.text-right { text-align: right; }
        .items-table tbody tr { border-bottom: 1px solid #f0f0f0; }
        .items-table tbody tr:nth-child(even) { background: #fffbeb; }
        .items-table tbody td { padding: 8px 10px; font-size: 11px; }
        .items-table tbody td.text-right { text-align: right; }

        .totals-table { width: 280px; margin-left: auto; margin-bottom: 20px; }
        .totals-table td { padding: 5px 10px; font-size: 11px; }
        .totals-table .total-row { background: #92400e; color: #fff; font-weight: bold; font-size: 13px; }
        .totals-table .label { color: #666; }
        .totals-table .amount { text-align: right; }

        .notes-section { margin-bottom: 12px; }
        .notes-label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #666;
                       letter-spacing: 0.5px; margin-bottom: 4px; }
        .notes-text { font-size: 10px; color: #555; line-height: 1.6; }

        .footer { border-top: 1px solid #e5e7eb; padding-top: 12px; text-align: center;
                  font-size: 9px; color: #888; }
        .vat-note { font-size: 9px; color: #008751; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            @if($quote->tenant->logo)
                <img src="{{ public_path('storage/' . $quote->tenant->logo) }}" height="50" alt="Logo">
            @else
                <div class="company-name">{{ $quote->tenant->name }}</div>
            @endif
            <div class="company-detail">
                @if($quote->tenant->address){{ $quote->tenant->address }}<br>@endif
                @if($quote->tenant->city){{ $quote->tenant->city }}, {{ $quote->tenant->state }}<br>@endif
                @if($quote->tenant->email)Email: {{ $quote->tenant->email }}<br>@endif
                @if($quote->tenant->phone)Tel: {{ $quote->tenant->phone }}@endif
            </div>
            @if($quote->tenant->tin)
                <div class="tin-badge">TIN: {{ $quote->tenant->tin }}</div>
            @endif
            @if($quote->tenant->rc_number)
                <div class="tin-badge" style="margin-left:4px;">RC: {{ $quote->tenant->rc_number }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="doc-title">PROFORMA INVOICE</div>
            <div class="doc-subtitle">Quote / Estimate</div>
            <table class="invoice-meta">
                <tr>
                    <td class="label">Quote No:</td>
                    <td class="value">{{ $quote->quote_number }}</td>
                </tr>
                <tr>
                    <td class="label">Date:</td>
                    <td class="value">{{ $quote->quote_date->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Valid Until:</td>
                    <td class="value" style="color:#b45309;">{{ $quote->expiry_date->format('d F Y') }}</td>
                </tr>
                @if($quote->reference)
                <tr>
                    <td class="label">Reference:</td>
                    <td class="value">{{ $quote->reference }}</td>
                </tr>
                @endif
            </table>
            @php
                $statusClass = 'status-' . $quote->status;
            @endphp
            <span class="status-badge {{ $statusClass }}">{{ strtoupper($quote->status) }}</span>
        </div>
    </div>

    <hr class="divider">

    {{-- Proforma notice --}}
    <div class="proforma-notice">
        ⚠ THIS IS A PROFORMA INVOICE / ESTIMATE — NOT A TAX INVOICE.
        No payment obligation arises until a formal invoice is issued upon acceptance.
    </div>

    {{-- Parties --}}
    <div class="parties">
        <div class="party-from">
            <div class="party-label">From</div>
            <div class="party-name">{{ $quote->tenant->name }}</div>
            <div class="party-detail">
                {{ $quote->tenant->address }}<br>
                {{ $quote->tenant->city }}, Nigeria
            </div>
        </div>
        <div class="party-to">
            <div class="party-label">Prepared For</div>
            <div class="party-name">{{ $quote->customer->name }}</div>
            <div class="party-detail">
                @if($quote->customer->address){{ $quote->customer->address }}<br>@endif
                @if($quote->customer->city){{ $quote->customer->city }}, {{ $quote->customer->state }}<br>@endif
                @if($quote->customer->email){{ $quote->customer->email }}<br>@endif
                @if($quote->customer->tin)TIN: {{ $quote->customer->tin }}@endif
            </div>
        </div>
    </div>

    {{-- Line items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:42%">Description</th>
                <th class="text-right" style="width:8%">Qty</th>
                <th class="text-right" style="width:16%">Unit Price (₦)</th>
                <th class="text-right" style="width:16%">Subtotal (₦)</th>
                <th class="text-right" style="width:8%">VAT</th>
                <th class="text-right" style="width:10%">Total (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                <td class="text-right">
                    @if($item->vat_applicable && $quote->vat_applicable)
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

    {{-- Totals --}}
    <table class="totals-table">
        <tr>
            <td class="label">Subtotal:</td>
            <td class="amount">₦{{ number_format($quote->subtotal, 2) }}</td>
        </tr>
        @if($quote->vat_applicable)
        <tr>
            <td class="label">VAT (7.5%):</td>
            <td class="amount" style="color:#008751; font-weight:bold;">+ ₦{{ number_format($quote->vat_amount, 2) }}</td>
        </tr>
        @endif
        @if($quote->discount_amount > 0)
        <tr>
            <td class="label">Discount:</td>
            <td class="amount" style="color:#dc2626;">- ₦{{ number_format($quote->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($quote->wht_applicable)
        <tr>
            <td class="label">WHT ({{ $quote->wht_rate }}%):</td>
            <td class="amount" style="color:#dc2626;">- ₦{{ number_format($quote->wht_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>INDICATIVE TOTAL:</td>
            <td class="amount">₦{{ number_format($quote->total_amount, 2) }}</td>
        </tr>
    </table>

    {{-- Notes & Terms --}}
    @if($quote->notes || $quote->terms)
    <hr class="divider-light">
    @if($quote->notes)
    <div class="notes-section">
        <div class="notes-label">Notes</div>
        <div class="notes-text">{{ $quote->notes }}</div>
    </div>
    @endif
    @if($quote->terms)
    <div class="notes-section">
        <div class="notes-label">Terms & Conditions</div>
        <div class="notes-text">{{ $quote->terms }}</div>
    </div>
    @endif
    @endif

    {{-- Footer --}}
    <hr class="divider-light">
    <div class="footer">
        <p>
            <span style="color:#008751; font-weight:bold;">{{ $quote->tenant->name }}</span> |
            This proforma invoice is indicative only and is not a demand for payment. |
            Valid until: {{ $quote->expiry_date->format('d M Y') }}
        </p>
        <p style="margin-top:4px; color:#bbb;">
            Generated: {{ now()->format('d M Y, H:i') }} |
            Contact: {{ $quote->tenant->email }} | {{ $quote->tenant->phone }}
        </p>
    </div>

</div>
</body>
</html>
