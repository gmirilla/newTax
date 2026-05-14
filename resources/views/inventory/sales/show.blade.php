@extends('layouts.app')
@section('page-title', 'Sales Order ' . $salesOrder->order_number)

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold text-gray-900">{{ $salesOrder->order_number }}</h1>
                @php
                    $statusColor = match($salesOrder->status) {
                        'confirmed' => 'green',
                        'draft'     => 'yellow',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    };
                @endphp
                <span class="inline-flex rounded-full px-3 py-0.5 text-sm font-semibold
                    bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                    {{ ucfirst($salesOrder->status) }}
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ \Carbon\Carbon::parse($salesOrder->sale_date)->format('d F Y') }}
                &middot; Created by {{ $salesOrder->creator?->name ?? '—' }}
            </p>
        </div>

        <div class="flex gap-2 relative">
            @if($salesOrder->status === 'draft')
                @can('update', $salesOrder)
                <a href="{{ route('inventory.sales.edit', $salesOrder) }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                    Edit
                </a>
                @endcan
                @can('confirm', $salesOrder)
                <div x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                        Confirm &amp; Invoice
                    </button>
                    <div x-show="open" x-cloak class="absolute z-10 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 p-4">
                        <p class="text-sm font-medium text-gray-900 mb-3">Confirm Order</p>
                        <form method="POST" action="{{ route('inventory.sales.confirm', $salesOrder) }}">
                            @csrf
                            @if($bankAccounts->isNotEmpty())
                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Deposit to Bank Account</label>
                                <select name="bank_account_id"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="">— Use default GL account —</option>
                                    @foreach($bankAccounts as $ba)
                                        <option value="{{ $ba->id }}" {{ $ba->is_default ? 'selected' : '' }}>
                                            {{ $ba->name }}{{ $ba->bank_name ? ' — '.$ba->bank_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="flex gap-2">
                                <button type="submit"
                                        class="flex-1 px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                    Confirm
                                </button>
                                <button type="button" @click="open = false"
                                        class="px-3 py-2 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endcan
            @endif

            @can('cancel', $salesOrder)
                @if(in_array($salesOrder->status, ['draft', 'confirmed']))
                <form method="POST" action="{{ route('inventory.sales.cancel', $salesOrder) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Cancel {{ addslashes($salesOrder->order_number) }}? {{ $salesOrder->status === 'confirmed' ? 'This will reverse stock movements and void the invoice.' : '' }}')"
                            class="inline-flex items-center px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-600 hover:bg-red-50">
                        Cancel Order
                    </button>
                </form>
                @endif
            @endcan

            <a href="{{ route('inventory.sales.index') }}"
               class="inline-flex items-center px-3 py-2 border border-gray-200 text-sm font-medium rounded-md text-gray-500 hover:bg-gray-50">
                ← All Orders
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-md px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-md px-4 py-3 text-sm text-red-800">
        {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Left / Line Items --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Line Items --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-5 py-4 border-b">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Items Sold</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price (₦)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">VAT</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total (₦)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($salesOrder->items->sortBy('sort_order') as $line)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $line->description }}</p>
                                    @if($line->item)
                                        <p class="text-xs text-gray-400">{{ $line->item->name }}
                                            @if($line->item->sku) &middot; {{ $line->item->sku }} @endif
                                            &middot; {{ $line->item->unit }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">
                                    {{ number_format($line->quantity, 3) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">
                                    {{ number_format($line->unit_price, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-500">
                                    @if($line->vat_applicable)
                                        {{ number_format($line->vat_amount, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                                    {{ number_format($line->total, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Subtotal</td>
                                <td class="px-4 py-2 text-right text-sm font-medium text-gray-900">₦{{ number_format($salesOrder->subtotal, 2) }}</td>
                            </tr>
                            @if((float) $salesOrder->vat_amount > 0)
                            <tr>
                                <td colspan="4" class="px-4 py-2 text-right text-xs text-gray-500 uppercase">VAT</td>
                                <td class="px-4 py-2 text-right text-sm text-gray-700">₦{{ number_format($salesOrder->vat_amount, 2) }}</td>
                            </tr>
                            @endif
                            @if((float) $salesOrder->discount_amount > 0)
                            <tr>
                                <td colspan="4" class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Discount</td>
                                <td class="px-4 py-2 text-right text-sm text-red-600">−₦{{ number_format($salesOrder->discount_amount, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="border-t border-gray-300">
                                <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-800 uppercase">Total</td>
                                <td class="px-4 py-3 text-right text-base font-bold text-gray-900">₦{{ number_format($salesOrder->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Notes --}}
            @if($salesOrder->notes)
            <div class="bg-white rounded-lg shadow px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-2">Notes</h2>
                <p class="text-sm text-gray-600">{{ $salesOrder->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Right / Info Panel --}}
        <div class="space-y-5">

            {{-- Customer --}}
            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Customer</h2>
                @if($salesOrder->customer)
                    <p class="text-sm font-medium text-gray-900">{{ $salesOrder->customer->name }}</p>
                    @if($salesOrder->customer->email)
                        <p class="text-sm text-gray-500">{{ $salesOrder->customer->email }}</p>
                    @endif
                @elseif($salesOrder->customer_name)
                    <p class="text-sm font-medium text-gray-900">{{ $salesOrder->customer_name }}</p>
                    <p class="text-xs text-gray-400">Walk-in customer</p>
                @else
                    <p class="text-sm text-gray-400">Walk-in / No customer</p>
                @endif
            </div>

            {{-- Payment --}}
            <div class="bg-white rounded-lg shadow p-5 space-y-2">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Payment</h2>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Method</span>
                    <span class="font-medium capitalize">{{ str_replace('_', ' ', $salesOrder->payment_method) }}</span>
                </div>
                @if($salesOrder->payment_reference)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Reference</span>
                    <span class="font-medium text-gray-900">{{ $salesOrder->payment_reference }}</span>
                </div>
                @endif
                @if($salesOrder->status === 'confirmed')
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Status</span>
                    <span class="font-medium text-green-700">Paid in full</span>
                </div>
                @endif
            </div>

            {{-- Linked Documents --}}
            @if($salesOrder->status === 'confirmed')
            <div class="bg-white rounded-lg shadow p-5 space-y-3">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Linked Documents</h2>

                @if($salesOrder->invoice)
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Invoice</p>
                    <a href="{{ route('invoices.show', $salesOrder->invoice) }}"
                       class="text-sm font-medium text-green-700 hover:underline">
                        {{ $salesOrder->invoice->invoice_number }}
                    </a>
                    <span class="ml-2 text-xs text-gray-400">{{ ucfirst($salesOrder->invoice->status) }}</span>
                </div>
                @endif

                @if($salesOrder->transaction)
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">GL Transaction</p>
                    <p class="text-sm font-medium text-gray-900">{{ $salesOrder->transaction->reference }}</p>
                    <p class="text-xs text-gray-400">{{ $salesOrder->transaction->description }}</p>
                </div>
                @endif
            </div>
            @endif

            {{-- COGS summary --}}
            @if($salesOrder->status === 'confirmed' && $salesOrder->items->isNotEmpty())
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Cost of Goods</h2>
                @php
                    $totalCogs = $salesOrder->items->sum(fn($l) => (float)$l->quantity * (float)$l->cost_price_at_sale);
                    $grossProfit = (float)$salesOrder->subtotal - (float)$salesOrder->discount_amount - $totalCogs;
                @endphp
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">COGS</span>
                        <span class="text-gray-700">₦{{ number_format($totalCogs, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Net Revenue</span>
                        <span class="text-gray-700">₦{{ number_format((float)$salesOrder->subtotal - (float)$salesOrder->discount_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-1.5 font-medium">
                        <span class="{{ $grossProfit >= 0 ? 'text-green-700' : 'text-red-600' }}">Gross Profit</span>
                        <span class="{{ $grossProfit >= 0 ? 'text-green-700' : 'text-red-600' }}">
                            ₦{{ number_format($grossProfit, 2) }}
                        </span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
