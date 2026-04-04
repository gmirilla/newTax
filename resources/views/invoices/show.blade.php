@extends('layouts.app')

@section('page-title', 'Invoice #' . $invoice->invoice_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-4" x-data="{ showPayment: false }">

    {{-- Actions bar --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Invoices</a>
        <div class="flex gap-2">
            @if($invoice->status === 'draft')
            <form method="POST" action="{{ route('invoices.send', $invoice) }}">
                @csrf
                <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Send Invoice
                </button>
            </form>
            <a href="{{ route('invoices.edit', $invoice) }}"
               class="px-4 py-1.5 border border-gray-300 text-sm rounded-md hover:bg-gray-50">Edit</a>
            @endif
            @if(in_array($invoice->status, ['sent', 'partial']))
            <button type="button" @click="showPayment = true"
                    class="px-4 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                Record Payment
            </button>
            @endif
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank"
               class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md hover:bg-gray-800">Download PDF</a>
        </div>
    </div>

    {{-- Record Payment Modal --}}
    <div x-show="showPayment" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
         @click.self="showPayment = false" @keydown.escape.window="showPayment = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-semibold">Record Payment</h3>
                <button type="button" @click="showPayment = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <form method="POST" action="{{ route('invoices.payment', $invoice) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Payment Date *</label>
                        <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Amount (₦) *</label>
                        <input type="number" name="amount" step="0.01" min="0.01"
                               max="{{ $invoice->balance_due }}"
                               value="{{ $invoice->balance_due }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        <p class="text-xs text-gray-400 mt-0.5">Balance due: ₦{{ number_format($invoice->balance_due, 2) }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Payment Method *</label>
                        <select name="method" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="pos">POS</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Reference</label>
                        <input type="text" name="reference" placeholder="Bank ref / cheque no."
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showPayment = false"
                            class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                            class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                        Save Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Invoice Card --}}
    <div class="bg-white rounded-lg shadow">
        {{-- Header --}}
        <div class="p-6 border-b flex justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Invoice #{{ $invoice->invoice_number }}</h1>
                <p class="text-sm text-gray-500 mt-1">Issued: {{ $invoice->invoice_date->format('d M Y') }}</p>
                <p class="text-sm text-gray-500">Due: {{ $invoice->due_date->format('d M Y') }}</p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 rounded-full text-sm font-medium
                    @if($invoice->status === 'paid') bg-green-100 text-green-700
                    @elseif($invoice->status === 'overdue') bg-red-100 text-red-700
                    @elseif($invoice->status === 'sent') bg-blue-100 text-blue-700
                    @elseif($invoice->status === 'partial') bg-yellow-100 text-yellow-700
                    @else bg-gray-100 text-gray-600 @endif">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>
        </div>

        <div class="p-6 grid grid-cols-2 gap-6 border-b text-sm">
            <div>
                <p class="text-xs text-gray-400 uppercase font-medium mb-1">Bill To</p>
                <p class="font-semibold text-gray-900">{{ $invoice->customer->name }}</p>
                @if($invoice->customer->email)
                    <p class="text-gray-500">{{ $invoice->customer->email }}</p>
                @endif
                @if($invoice->customer->address)
                    <p class="text-gray-500 mt-1">{{ $invoice->customer->address }}</p>
                @endif
                @if($invoice->customer->tin)
                    <p class="text-xs text-gray-400 mt-1">TIN: {{ $invoice->customer->tin }}</p>
                @endif
            </div>
            <div class="text-right">
                @if($invoice->wht_applicable)
                <p class="text-xs bg-yellow-50 border border-yellow-200 rounded px-2 py-1 text-yellow-700 inline-block">
                    WHT {{ $invoice->wht_rate }}% applicable
                </p>
                @endif
            </div>
        </div>

        {{-- Line Items --}}
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Description</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Unit Price</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($invoice->items as $item)
                <tr>
                    <td class="px-6 py-3 text-gray-700">
                        {{ $item->description }}
                        @if($item->item_code)
                            <span class="text-xs text-gray-400 block">{{ $item->item_code }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right text-gray-600">{{ $item->quantity }}</td>
                    <td class="px-6 py-3 text-right text-gray-600">₦{{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-6 py-3 text-right font-medium">₦{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="p-6 border-t">
            <div class="max-w-xs ml-auto space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Subtotal</span>
                    <span>₦{{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if($invoice->discount_amount > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500">Discount</span>
                    <span class="text-red-600">- ₦{{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
                @endif
                @if($invoice->vat_amount > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500">VAT (7.5%)</span>
                    <span class="text-blue-600">₦{{ number_format($invoice->vat_amount, 2) }}</span>
                </div>
                @endif
                @if($invoice->wht_amount > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500">WHT ({{ $invoice->wht_rate }}%)</span>
                    <span class="text-yellow-600">- ₦{{ number_format($invoice->wht_amount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between border-t pt-2 font-bold text-base">
                    <span>Total</span>
                    <span>₦{{ number_format($invoice->total_amount, 2) }}</span>
                </div>
                @if($invoice->amount_paid > 0)
                <div class="flex justify-between text-green-700">
                    <span>Amount Paid</span>
                    <span>₦{{ number_format($invoice->amount_paid, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-orange-700 border-t pt-2">
                    <span>Balance Due</span>
                    <span>₦{{ number_format($invoice->balance_due, 2) }}</span>
                </div>
                @endif
            </div>
        </div>

        @if($invoice->notes)
        <div class="px-6 pb-6 text-sm text-gray-500 border-t pt-4">
            <p class="font-medium text-gray-700 mb-1">Notes</p>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif
    </div>

    {{-- Payment History --}}
    @if($invoice->payments->count() > 0)
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-sm font-semibold">Payment History</h3>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                    <th class="px-6 py-2 text-left text-xs font-medium text-gray-500">Reference</th>
                    <th class="px-6 py-2 text-left text-xs font-medium text-gray-500">Method</th>
                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-500">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($invoice->payments as $payment)
                <tr>
                    <td class="px-6 py-2">{{ $payment->payment_date->format('d M Y') }}</td>
                    <td class="px-6 py-2 font-mono text-xs">{{ $payment->reference }}</td>
                    <td class="px-6 py-2">{{ ucfirst($payment->payment_method) }}</td>
                    <td class="px-6 py-2 text-right text-green-700 font-medium">₦{{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
