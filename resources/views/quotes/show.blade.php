@extends('layouts.app')

@section('page-title', 'Quote ' . $quote->quote_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    {{-- Actions bar --}}
    <div class="flex items-center justify-between flex-wrap gap-2">
        <a href="{{ route('quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Quotes</a>
        <div class="flex flex-wrap gap-2">

            {{-- Edit --}}
            @if($quote->isEditable())
            <a href="{{ route('quotes.edit', $quote) }}"
               class="px-4 py-1.5 border border-gray-300 text-sm rounded-md hover:bg-gray-50">Edit</a>
            @endif

            {{-- Mark Sent --}}
            @if($quote->status === 'draft')
            <form method="POST" action="{{ route('quotes.send', $quote) }}">
                @csrf
                <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Mark as Sent
                </button>
            </form>
            @endif

            {{-- Accept → convert to invoice --}}
            @if(in_array($quote->status, ['draft', 'sent']))
            <form method="POST" action="{{ route('quotes.accept', $quote) }}"
                  onsubmit="return confirm('Accept this quote and generate an invoice?')">
                @csrf
                <button type="submit" class="px-4 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                    Accept & Generate Invoice
                </button>
            </form>

            {{-- Decline --}}
            <form method="POST" action="{{ route('quotes.decline', $quote) }}"
                  onsubmit="return confirm('Mark this quote as declined?')">
                @csrf
                <button type="submit" class="px-4 py-1.5 bg-red-50 border border-red-300 text-red-700 text-sm rounded-md hover:bg-red-100">
                    Decline
                </button>
            </form>
            @endif

            {{-- Download PDF --}}
            <a href="{{ route('quotes.pdf', $quote) }}" target="_blank"
               class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md hover:bg-gray-800">
                Download PDF
            </a>

            {{-- Delete --}}
            @if($quote->status !== 'accepted')
            <form method="POST" action="{{ route('quotes.destroy', $quote) }}"
                  onsubmit="return confirm('Delete this quote? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-1.5 text-red-600 text-sm hover:text-red-800">Delete</button>
            </form>
            @endif
        </div>
    </div>

    {{-- Accepted banner --}}
    @if($quote->status === 'accepted' && $quote->convertedInvoice)
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center justify-between">
        <p class="text-sm text-green-800 font-medium">
            ✅ This quote was accepted and converted to an invoice.
        </p>
        <a href="{{ route('invoices.show', $quote->convertedInvoice) }}"
           class="px-4 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
            View Invoice {{ $quote->convertedInvoice->invoice_number }} →
        </a>
    </div>
    @endif

    {{-- Expired notice --}}
    @if($quote->isExpired())
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
        ⚠️ This quote expired on {{ $quote->expiry_date->format('d M Y') }}.
    </div>
    @endif

    {{-- Quote card --}}
    <div class="bg-white rounded-lg shadow">
        {{-- Header --}}
        <div class="p-6 border-b flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">PROFORMA INVOICE</h1>
                <p class="text-sm font-mono text-gray-500 mt-1">{{ $quote->quote_number }}</p>
                @if($quote->reference)
                    <p class="text-sm text-gray-400">Ref: {{ $quote->reference }}</p>
                @endif
            </div>
            <div class="text-right">
                @php
                    $colors = ['draft'=>'gray','sent'=>'blue','accepted'=>'green','declined'=>'red','expired'=>'yellow'];
                    $c = $colors[$quote->status] ?? 'gray';
                @endphp
                <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-{{ $c }}-100 text-{{ $c }}-700">
                    {{ ucfirst($quote->status) }}
                </span>
                <p class="text-xs text-gray-400 mt-2">Issued: {{ $quote->quote_date->format('d M Y') }}</p>
                <p class="text-xs {{ $quote->isExpired() ? 'text-red-500 font-semibold' : 'text-gray-400' }} mt-0.5">
                    Valid until: {{ $quote->expiry_date->format('d M Y') }}
                </p>
            </div>
        </div>

        {{-- Parties --}}
        <div class="p-6 grid grid-cols-2 gap-6 border-b text-sm">
            <div>
                <p class="text-xs text-gray-400 uppercase font-medium mb-1">From</p>
                <p class="font-semibold text-gray-900">{{ auth()->user()->tenant->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-medium mb-1">Prepared For</p>
                <p class="font-semibold text-gray-900">{{ $quote->customer->name }}</p>
                @if($quote->customer->email) <p class="text-gray-500">{{ $quote->customer->email }}</p> @endif
                @if($quote->customer->address) <p class="text-gray-500 mt-1">{{ $quote->customer->address }}</p> @endif
                @if($quote->customer->tin) <p class="text-xs text-gray-400 mt-1">TIN: {{ $quote->customer->tin }}</p> @endif
            </div>
        </div>

        {{-- Line items --}}
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Description</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Unit Price</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Subtotal</th>
                    @if($quote->vat_applicable)
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">VAT</th>
                    @endif
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($quote->items as $item)
                <tr>
                    <td class="px-6 py-3 text-gray-700">{{ $item->description }}</td>
                    <td class="px-6 py-3 text-right text-gray-600">{{ $item->quantity }}</td>
                    <td class="px-6 py-3 text-right text-gray-600">₦{{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-6 py-3 text-right">₦{{ number_format($item->subtotal, 2) }}</td>
                    @if($quote->vat_applicable)
                    <td class="px-6 py-3 text-right text-blue-600">
                        @if($item->vat_applicable) ₦{{ number_format($item->vat_amount, 2) }}
                        @else <span class="text-gray-300">—</span> @endif
                    </td>
                    @endif
                    <td class="px-6 py-3 text-right font-medium">₦{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="p-6 border-t">
            <div class="max-w-xs ml-auto space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Subtotal</span>
                    <span>₦{{ number_format($quote->subtotal, 2) }}</span>
                </div>
                @if($quote->vat_amount > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500">VAT (7.5%)</span>
                    <span class="text-blue-600">₦{{ number_format($quote->vat_amount, 2) }}</span>
                </div>
                @endif
                @if($quote->discount_amount > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500">Discount</span>
                    <span class="text-red-600">- ₦{{ number_format($quote->discount_amount, 2) }}</span>
                </div>
                @endif
                @if($quote->wht_amount > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500">WHT ({{ $quote->wht_rate }}%)</span>
                    <span class="text-yellow-600">- ₦{{ number_format($quote->wht_amount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between border-t pt-2 font-bold text-base">
                    <span>Total (Indicative)</span>
                    <span>₦{{ number_format($quote->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        @if($quote->notes || $quote->terms)
        <div class="px-6 pb-6 border-t pt-4 text-sm text-gray-500 space-y-2">
            @if($quote->notes)
                <p class="font-medium text-gray-700">Notes</p>
                <p>{{ $quote->notes }}</p>
            @endif
            @if($quote->terms)
                <p class="font-medium text-gray-700 mt-2">Terms</p>
                <p>{{ $quote->terms }}</p>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
