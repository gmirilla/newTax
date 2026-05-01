<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} — {{ $invoice->tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .shadow { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8 px-4">

<div class="max-w-3xl mx-auto">

    {{-- Top bar --}}
    <div class="no-print flex items-center justify-between mb-5">
        <div class="text-sm text-gray-500">
            Viewing invoice shared by <span class="font-medium text-gray-700">{{ $invoice->tenant->name }}</span>
        </div>
        <a href="{{ route('invoice.public.pdf', $invoice->public_token) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Download PDF
        </a>
    </div>

    {{-- Invoice card --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">

        {{-- Header --}}
        <div class="bg-green-700 px-8 py-6 text-white">
            <div class="flex justify-between items-start">
                <div>
                    @if($invoice->tenant->logo)
                        <img src="{{ Storage::url($invoice->tenant->logo) }}" alt="{{ $invoice->tenant->name }}"
                             class="h-12 mb-3 object-contain bg-white rounded px-2 py-1">
                    @else
                        <div class="text-2xl font-bold mb-1">{{ $invoice->tenant->name }}</div>
                    @endif
                    <div class="text-green-200 text-sm space-y-0.5">
                        @if($invoice->tenant->address)<div>{{ $invoice->tenant->address }}</div>@endif
                        @if($invoice->tenant->city)<div>{{ $invoice->tenant->city }}, {{ $invoice->tenant->state }}</div>@endif
                        @if($invoice->tenant->email)<div>{{ $invoice->tenant->email }}</div>@endif
                        @if($invoice->tenant->phone)<div>{{ $invoice->tenant->phone }}</div>@endif
                    </div>
                    @if($invoice->tenant->tin)
                        <div class="mt-2 inline-block bg-green-600 border border-green-400 text-xs font-bold px-2 py-0.5 rounded">
                            TIN: {{ $invoice->tenant->tin }}
                        </div>
                    @endif
                    @if($invoice->tenant->vat_registered && $invoice->tenant->vat_number)
                        <div class="inline-block bg-green-600 border border-green-400 text-xs font-bold px-2 py-0.5 rounded ml-1">
                            VAT: {{ $invoice->tenant->vat_number }}
                        </div>
                    @endif
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold tracking-wide">INVOICE</div>
                    <div class="mt-2 text-green-100 text-sm space-y-1">
                        <div><span class="text-green-300">No:</span> <strong>{{ $invoice->invoice_number }}</strong></div>
                        <div><span class="text-green-300">Date:</span> {{ $invoice->invoice_date->format('d M Y') }}</div>
                        <div><span class="text-green-300">Due:</span> {{ $invoice->due_date->format('d M Y') }}</div>
                        @if($invoice->reference)
                        <div><span class="text-green-300">Ref:</span> {{ $invoice->reference }}</div>
                        @endif
                    </div>
                    @php
                        $statusColour = match($invoice->status) {
                            'paid'    => 'bg-green-200 text-green-900',
                            'sent'    => 'bg-blue-100 text-blue-900',
                            'overdue' => 'bg-red-200 text-red-900',
                            'partial' => 'bg-yellow-200 text-yellow-900',
                            default   => 'bg-gray-200 text-gray-800',
                        };
                    @endphp
                    <div class="mt-3">
                        <span class="inline-block {{ $statusColour }} text-xs font-bold uppercase px-3 py-1 rounded-full">
                            {{ $invoice->status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bill To --}}
        <div class="px-8 py-5 border-b border-gray-100 grid grid-cols-2 gap-6">
            <div>
                <div class="text-xs font-bold uppercase text-gray-400 tracking-widest mb-2">From</div>
                <div class="font-semibold text-gray-800">{{ $invoice->tenant->name }}</div>
                <div class="text-sm text-gray-500">{{ $invoice->tenant->city }}, Nigeria</div>
            </div>
            <div>
                <div class="text-xs font-bold uppercase text-gray-400 tracking-widest mb-2">Bill To</div>
                <div class="font-semibold text-gray-800">{{ $invoice->customer->name }}</div>
                <div class="text-sm text-gray-500 space-y-0.5">
                    @if($invoice->customer->address)<div>{{ $invoice->customer->address }}</div>@endif
                    @if($invoice->customer->city)<div>{{ $invoice->customer->city }}, {{ $invoice->customer->state }}</div>@endif
                    @if($invoice->customer->email)<div>{{ $invoice->customer->email }}</div>@endif
                    @if($invoice->customer->tin)<div>TIN: {{ $invoice->customer->tin }}</div>@endif
                </div>
            </div>
        </div>

        {{-- WHT notice --}}
        @if($invoice->wht_applicable)
        <div class="mx-8 mt-4 bg-amber-50 border border-amber-200 rounded-md px-4 py-3 text-sm text-amber-800">
            <strong>Withholding Tax Notice:</strong> The sum of
            &#8358;{{ number_format($invoice->wht_amount, 2) }} ({{ $invoice->wht_rate }}% WHT) is deductible at source
            and must be remitted directly to FIRS on our behalf. A WHT credit note must be issued.
        </div>
        @endif

        {{-- Line items --}}
        <div class="px-8 pt-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-y border-gray-200">
                        <th class="py-2.5 px-3 text-left font-semibold text-gray-600 text-xs uppercase">Description</th>
                        <th class="py-2.5 px-3 text-right font-semibold text-gray-600 text-xs uppercase">Qty</th>
                        <th class="py-2.5 px-3 text-right font-semibold text-gray-600 text-xs uppercase">Unit Price</th>
                        <th class="py-2.5 px-3 text-right font-semibold text-gray-600 text-xs uppercase">Subtotal</th>
                        <th class="py-2.5 px-3 text-right font-semibold text-gray-600 text-xs uppercase">VAT</th>
                        <th class="py-2.5 px-3 text-right font-semibold text-gray-600 text-xs uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($invoice->items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-3 text-gray-800">{{ $item->description }}</td>
                        <td class="py-3 px-3 text-right text-gray-600">{{ number_format($item->quantity, 2) }}</td>
                        <td class="py-3 px-3 text-right text-gray-600">&#8358;{{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-3 px-3 text-right text-gray-600">&#8358;{{ number_format($item->subtotal, 2) }}</td>
                        <td class="py-3 px-3 text-right text-gray-500 text-xs">
                            @if($item->vat_applicable)
                                <span class="bg-green-100 text-green-700 px-1.5 py-0.5 rounded">7.5%</span>
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td class="py-3 px-3 text-right font-medium text-gray-800">&#8358;{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totals --}}
        <div class="px-8 py-5 flex justify-end">
            <div class="w-72 space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>&#8358;{{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if($invoice->vat_applicable)
                <div class="flex justify-between text-green-700">
                    <span>VAT (7.5%)</span>
                    <span>+ &#8358;{{ number_format($invoice->vat_amount, 2) }}</span>
                </div>
                @endif
                @if($invoice->discount_amount > 0)
                <div class="flex justify-between text-red-600">
                    <span>Discount</span>
                    <span>- &#8358;{{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
                @endif
                @if($invoice->wht_applicable)
                <div class="flex justify-between text-red-600">
                    <span>WHT ({{ $invoice->wht_rate }}%)</span>
                    <span>- &#8358;{{ number_format($invoice->wht_amount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-base bg-green-700 text-white px-3 py-2.5 rounded-md mt-2">
                    <span>Total Due</span>
                    <span>&#8358;{{ number_format($invoice->total_amount, 2) }}</span>
                </div>
                @if($invoice->amount_paid > 0)
                <div class="flex justify-between text-green-600 pt-1">
                    <span>Amount Paid</span>
                    <span>- &#8358;{{ number_format($invoice->amount_paid, 2) }}</span>
                </div>
                <div class="flex justify-between font-semibold text-amber-700 border-t pt-1.5">
                    <span>Balance Due</span>
                    <span>&#8358;{{ number_format($invoice->balance_due, 2) }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Notes & Terms --}}
        @if($invoice->notes || $invoice->terms)
        <div class="px-8 pb-5 border-t border-gray-100 pt-4 grid grid-cols-2 gap-6 text-sm text-gray-600">
            @if($invoice->notes)
            <div>
                <div class="text-xs font-bold uppercase text-gray-400 tracking-widest mb-1.5">Notes</div>
                <div class="whitespace-pre-line">{{ $invoice->notes }}</div>
            </div>
            @endif
            @if($invoice->terms)
            <div>
                <div class="text-xs font-bold uppercase text-gray-400 tracking-widest mb-1.5">Terms & Conditions</div>
                <div class="whitespace-pre-line">{{ $invoice->terms }}</div>
            </div>
            @endif
        </div>
        @endif

        {{-- Payment history --}}
        @if($invoice->payments->count())
        <div class="px-8 pb-6 border-t border-gray-100 pt-4">
            <div class="text-xs font-bold uppercase text-gray-400 tracking-widest mb-3">Payment History</div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 border-b">
                        <th class="pb-2 text-left font-medium">Date</th>
                        <th class="pb-2 text-left font-medium">Method</th>
                        <th class="pb-2 text-left font-medium">Reference</th>
                        <th class="pb-2 text-right font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($invoice->payments as $payment)
                    <tr>
                        <td class="py-2 text-gray-700">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                        <td class="py-2 text-gray-600">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                        <td class="py-2 text-gray-500">{{ $payment->reference ?: '—' }}</td>
                        <td class="py-2 text-right font-medium text-green-700">&#8358;{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Footer --}}
        <div class="bg-gray-50 border-t border-gray-200 px-8 py-4 text-center text-xs text-gray-400">
            <span class="font-semibold text-gray-500">{{ $invoice->tenant->name }}</span> &mdash;
            This invoice complies with Nigerian tax regulations (FIRS).
            @if($invoice->tenant->tin) TIN: {{ $invoice->tenant->tin }} &mdash; @endif
            Generated: {{ now()->format('d M Y') }}
        </div>

    </div>

    {{-- Powered by --}}
    <p class="no-print text-center text-xs text-gray-400 mt-4">
        Powered by <span class="font-medium text-gray-500">NaijaBooks</span>
    </p>

</div>
</body>
</html>
