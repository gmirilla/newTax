<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt – {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 4mm;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            line-height: 1.45;
            color: #000;
            background: #fff;
            width: 72mm;
        }

        .center { text-align: center; }
        .right  { text-align: right; }
        .bold   { font-weight: bold; }
        .lg     { font-size: 13px; }
        .sm     { font-size: 8.5px; }

        hr.dashed { border: none; border-top: 1px dashed #000; margin: 4px 0; }
        hr.solid  { border: none; border-top: 1.5px solid #000; margin: 4px 0; }

        .logo { max-width: 100%; max-height: 18mm; display: block; margin: 0 auto 3px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1px 0; vertical-align: top; }
        .r { text-align: right; white-space: nowrap; padding-left: 4px; }
        .wrap { max-width: 44mm; word-break: break-word; }

        @media screen {
            body {
                margin: 12mm auto;
                padding: 8mm;
                border: 1px dashed #aaa;
                box-shadow: 0 2px 10px rgba(0,0,0,.12);
            }
            .print-controls {
                margin-top: 10mm;
                text-align: center;
            }
            .print-controls button {
                padding: 5px 18px;
                font-size: 11px;
                cursor: pointer;
                margin: 0 3px;
            }
        }

        @media print {
            body { width: auto; }
            .print-controls { display: none; }
        }
    </style>
</head>
<body>

{{-- ── Company Header ───────────────────────────────────────────── --}}
<div class="center">
    @if($invoice->tenant->logo)
        <img class="logo" src="{{ asset('storage/' . $invoice->tenant->logo) }}" alt="Logo">
        @if($invoice->tenant->show_name_with_logo)
            <p class="bold">{{ $invoice->tenant->name }}</p>
        @endif
    @else
        <p class="bold lg">{{ $invoice->tenant->name }}</p>
    @endif
    @if($invoice->tenant->address)
        <p class="sm">{{ $invoice->tenant->address }}</p>
    @endif
    @if($invoice->tenant->phone)
        <p class="sm">Tel: {{ $invoice->tenant->phone }}</p>
    @endif
    @if($invoice->tenant->email)
        <p class="sm">{{ $invoice->tenant->email }}</p>
    @endif
    @if($invoice->tenant->tin)
        <p class="sm">TIN: {{ $invoice->tenant->tin }}</p>
    @endif
    @if($invoice->tenant->vat_number)
        <p class="sm">VAT Reg: {{ $invoice->tenant->vat_number }}</p>
    @endif
</div>

<hr class="dashed">

<p class="center bold lg">OFFICIAL RECEIPT</p>

<hr class="dashed">

{{-- ── Receipt / Invoice Details ───────────────────────────────── --}}
<table>
    <tr>
        <td>Receipt No:</td>
        <td class="r">{{ $receiptNumber }}</td>
    </tr>
    <tr>
        <td>Invoice No:</td>
        <td class="r">{{ $invoice->invoice_number }}</td>
    </tr>
    <tr>
        <td>Date:</td>
        <td class="r">{{ $payment->payment_date->format('d M Y') }}</td>
    </tr>
    @if($invoice->customer)
    <tr>
        <td>Customer:</td>
        <td class="r">{{ $invoice->customer->name }}</td>
    </tr>
    @if($invoice->customer->address)
    <tr>
        <td></td>
        <td class="r sm">{{ $invoice->customer->address }}</td>
    </tr>
    @endif
    @if($invoice->customer->tin)
    <tr>
        <td>Cust. TIN:</td>
        <td class="r">{{ $invoice->customer->tin }}</td>
    </tr>
    @endif
    @endif
</table>

<hr class="dashed">

{{-- ── Line Items ───────────────────────────────────────────────── --}}
<table>
    <thead>
        <tr>
            <th class="wrap">Description</th>
            <th class="r">Qty</th>
            <th class="r">Unit</th>
            <th class="r">Amt</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $item)
        <tr>
            <td class="wrap">{{ $item->description }}</td>
            <td class="r">{{ $item->quantity }}</td>
            <td class="r">{{ number_format($item->unit_price, 2) }}</td>
            <td class="r">{{ number_format($item->amount, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<hr class="dashed">

{{-- ── Invoice Totals ───────────────────────────────────────────── --}}
<table>
    <tr>
        <td>Subtotal</td>
        <td class="r">₦{{ number_format($invoice->subtotal, 2) }}</td>
    </tr>
    @if($invoice->discount_amount > 0)
    <tr>
        <td>Discount</td>
        <td class="r">-₦{{ number_format($invoice->discount_amount, 2) }}</td>
    </tr>
    @endif
    @if($invoice->vat_amount > 0)
    <tr>
        <td>VAT (7.5%)</td>
        <td class="r">₦{{ number_format($invoice->vat_amount, 2) }}</td>
    </tr>
    @endif
    @if($invoice->wht_amount > 0)
    <tr>
        <td>WHT ({{ $invoice->wht_rate }}%)</td>
        <td class="r">-₦{{ number_format($invoice->wht_amount, 2) }}</td>
    </tr>
    @endif
    <tr class="bold">
        <td style="padding-top:3px;">TOTAL</td>
        <td class="r" style="padding-top:3px;">₦{{ number_format($invoice->total_amount, 2) }}</td>
    </tr>
</table>

<hr class="solid">

{{-- ── This Payment ─────────────────────────────────────────────── --}}
<table>
    <tr class="bold">
        <td>AMOUNT RECEIVED</td>
        <td class="r">₦{{ number_format($payment->amount, 2) }}</td>
    </tr>
    <tr>
        <td>Method</td>
        <td class="r">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
    </tr>
    @if($payment->reference)
    <tr>
        <td>Reference</td>
        <td class="r">{{ $payment->reference }}</td>
    </tr>
    @endif
    @if($balanceAfterPayment > 0.005)
    <tr>
        <td style="padding-top:3px;">Balance Due</td>
        <td class="r" style="padding-top:3px;">₦{{ number_format($balanceAfterPayment, 2) }}</td>
    </tr>
    @else
    <tr>
        <td colspan="2" class="center bold" style="padding-top:4px;">*** FULLY PAID ***</td>
    </tr>
    @endif
</table>

@if($payment->notes)
<hr class="dashed">
<p class="sm center">{{ $payment->notes }}</p>
@endif

<hr class="dashed">

{{-- ── Footer ──────────────────────────────────────────────────── --}}
<p class="center sm" style="margin-top:2px;">Thank you for your business!</p>
<p class="center sm" style="margin-top:6px; color:#666;">Powered by NaijaBooks</p>

{{-- Screen controls — hidden on print --}}
<div class="print-controls">
    <button onclick="window.print()">&#128438; Print</button>
    <button onclick="window.close()">Close</button>
</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>

</body>
</html>
