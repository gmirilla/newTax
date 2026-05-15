@extends('layouts.app')
@section('page-title', 'Restock Request ' . $restockRequest->request_number)

@section('content')
<div class="space-y-5" x-data="{ rejectOpen: false, receiveOpen: false }">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold text-gray-900">{{ $restockRequest->request_number }}</h1>
                @php
                    $statusColor = match($restockRequest->status) {
                        'pending'   => 'yellow',
                        'approved'  => 'blue',
                        'received'  => 'green',
                        'rejected'  => 'red',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    };
                @endphp
                <span class="inline-flex rounded-full px-3 py-0.5 text-sm font-semibold
                    bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                    {{ ucfirst($restockRequest->status) }}
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">
                Submitted {{ $restockRequest->created_at->format('d M Y H:i') }}
                by {{ $restockRequest->requester?->name ?? '—' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            {{-- Approve --}}
            @can('approve', $restockRequest)
            <form method="POST" action="{{ route('inventory.restock.approve', $restockRequest) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Approve this restock request?')"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Approve
                </button>
            </form>
            @endcan

            {{-- Reject --}}
            @can('reject', $restockRequest)
            <button type="button" @click="rejectOpen = true"
                    class="inline-flex items-center px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-600 hover:bg-red-50">
                Reject
            </button>
            @endcan

            {{-- Receive goods --}}
            @can('receive', $restockRequest)
            <button type="button" @click="receiveOpen = true"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Mark as Received
            </button>
            @endcan

            {{-- Cancel --}}
            @can('cancel', $restockRequest)
            <form method="POST" action="{{ route('inventory.restock.cancel', $restockRequest) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Cancel this request?')"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-50">
                    Cancel
                </button>
            </form>
            @endcan

            <a href="{{ route('inventory.restock.index') }}"
               class="inline-flex items-center px-3 py-2 border border-gray-200 text-sm font-medium rounded-md text-gray-500 hover:bg-gray-50">
                ← All Requests
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
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-md px-4 py-3">
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Rejection reason banner --}}
    @if($restockRequest->status === 'rejected' && $restockRequest->rejection_reason)
    <div class="bg-red-50 border border-red-200 rounded-md px-4 py-3">
        <p class="text-sm font-semibold text-red-800">Rejection Reason</p>
        <p class="text-sm text-red-700 mt-0.5">{{ $restockRequest->rejection_reason }}</p>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Main content --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Item Details --}}
            <div class="bg-white rounded-lg shadow p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Item</h2>

                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-base font-semibold text-gray-900">{{ $restockRequest->item->name ?? '—' }}</p>
                        @if($restockRequest->item?->sku)
                            <p class="text-sm text-gray-500">SKU: {{ $restockRequest->item->sku }}</p>
                        @endif
                        @if($restockRequest->item?->category)
                            <p class="text-sm text-gray-500">Category: {{ $restockRequest->item->category->name }}</p>
                        @endif
                    </div>
                    <a href="{{ route('inventory.items.show', $restockRequest->item) }}"
                       class="text-sm text-green-600 hover:underline">View Item →</a>
                </div>

                @if($restockRequest->item)
                <div class="grid grid-cols-3 gap-3 bg-gray-50 rounded-md p-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Current Stock</p>
                        @php
                            $stock = (float) $restockRequest->item->current_stock;
                            $level = (float) $restockRequest->item->restock_level;
                            $stockClass = $stock <= 0 ? 'text-red-600 font-bold' : ($stock <= $level ? 'text-yellow-600 font-semibold' : 'text-green-700 font-semibold');
                        @endphp
                        <p class="{{ $stockClass }}">
                            {{ number_format($stock, 3) }} {{ $restockRequest->item->unit }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Restock Level</p>
                        <p class="font-medium text-gray-700">{{ number_format($level, 3) }} {{ $restockRequest->item->unit }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Avg Cost</p>
                        <p class="font-medium text-gray-700">₦{{ number_format($restockRequest->item->avg_cost, 2) }}</p>
                    </div>
                </div>
                @endif
            </div>

            {{-- Request Details --}}
            <div class="bg-white rounded-lg shadow p-5 space-y-3">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Request Details</h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Quantity Requested</p>
                        <p class="font-semibold text-gray-900">
                            {{ number_format($restockRequest->quantity_requested, 3) }}
                            {{ $restockRequest->item?->unit }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Unit Cost</p>
                        <p class="font-semibold text-gray-900">₦{{ number_format($restockRequest->unit_cost, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Estimated Total</p>
                        <p class="font-semibold text-gray-900">₦{{ number_format($restockRequest->totalCost(), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Supplier</p>
                        <p class="font-medium text-gray-700">{{ $restockRequest->supplier_name ?: '—' }}</p>
                    </div>
                    @if($restockRequest->supplier_invoice_no)
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Supplier Invoice #</p>
                        <p class="font-medium text-gray-700">{{ $restockRequest->supplier_invoice_no }}</p>
                    </div>
                    @endif
                </div>

                @if($restockRequest->notes)
                <div class="border-t pt-3">
                    <p class="text-xs text-gray-500 mb-1">Notes</p>
                    <p class="text-sm text-gray-700">{{ $restockRequest->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Right Panel --}}
        <div class="space-y-5">

            {{-- Workflow timeline --}}
            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Timeline</h2>
                <ol class="space-y-4 text-sm">
                    <li class="flex gap-3">
                        <span class="flex-none w-5 h-5 rounded-full bg-green-500 flex items-center justify-center text-white text-xs mt-0.5">✓</span>
                        <div>
                            <p class="font-medium text-gray-900">Submitted</p>
                            <p class="text-xs text-gray-500">
                                {{ $restockRequest->created_at->format('d M Y H:i') }}
                                — {{ $restockRequest->requester?->name ?? '—' }}
                            </p>
                        </div>
                    </li>

                    @if($restockRequest->approved_at)
                    <li class="flex gap-3">
                        <span class="flex-none w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs mt-0.5">✓</span>
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ $restockRequest->status === 'rejected' ? 'Rejected' : 'Approved' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $restockRequest->approved_at->format('d M Y H:i') }}
                                — {{ $restockRequest->approver?->name ?? '—' }}
                            </p>
                        </div>
                    </li>
                    @elseif(in_array($restockRequest->status, ['pending']))
                    <li class="flex gap-3">
                        <span class="flex-none w-5 h-5 rounded-full bg-gray-300 flex items-center justify-center text-white text-xs mt-0.5">2</span>
                        <div>
                            <p class="font-medium text-gray-400">Awaiting Approval</p>
                        </div>
                    </li>
                    @endif

                    @if($restockRequest->received_at)
                    <li class="flex gap-3">
                        <span class="flex-none w-5 h-5 rounded-full bg-green-600 flex items-center justify-center text-white text-xs mt-0.5">✓</span>
                        <div>
                            <p class="font-medium text-gray-900">Goods Received</p>
                            <p class="text-xs text-gray-500">
                                {{ $restockRequest->received_at->format('d M Y H:i') }}
                            </p>
                        </div>
                    </li>
                    @elseif($restockRequest->status === 'approved')
                    <li class="flex gap-3">
                        <span class="flex-none w-5 h-5 rounded-full bg-gray-300 flex items-center justify-center text-white text-xs mt-0.5">3</span>
                        <div>
                            <p class="font-medium text-gray-400">Awaiting Receipt</p>
                        </div>
                    </li>
                    @endif
                </ol>
            </div>

            {{-- Linked Production Order --}}
            @if($restockRequest->productionOrder)
            <div class="bg-purple-50 border border-purple-200 rounded-lg shadow p-5">
                <h2 class="text-sm font-semibold text-purple-800 uppercase tracking-wide mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    From Production Order
                </h2>
                <a href="{{ route('manufacturing.production.show', $restockRequest->productionOrder) }}"
                   class="text-sm font-semibold text-purple-700 hover:underline">
                    {{ $restockRequest->productionOrder->order_number }}
                </a>
                @if($restockRequest->productionOrder->finishedItem)
                <p class="text-xs text-purple-600 mt-1">
                    Producing: {{ $restockRequest->productionOrder->finishedItem->name }}
                </p>
                @endif
                <p class="text-xs text-purple-600 mt-0.5">
                    Qty planned: {{ number_format($restockRequest->productionOrder->quantity_planned, 3) }}
                </p>
                @php
                    $prodBadge = match($restockRequest->productionOrder->status) {
                        'draft'         => 'bg-gray-100 text-gray-600',
                        'in_production' => 'bg-yellow-100 text-yellow-700',
                        'completed'     => 'bg-green-100 text-green-700',
                        'cancelled'     => 'bg-red-100 text-red-600',
                        default         => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $prodBadge }}">
                    {{ ucfirst(str_replace('_', ' ', $restockRequest->productionOrder->status)) }}
                </span>
            </div>
            @endif

            {{-- Linked Documents --}}
            @if($restockRequest->invoice)
            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Supplier Bill</h2>
                <p class="text-sm font-medium text-green-700">
                    <a href="{{ route('invoices.show', $restockRequest->invoice) }}" class="hover:underline">
                        {{ $restockRequest->invoice->invoice_number }}
                    </a>
                </p>
                <p class="text-sm text-gray-600 mt-1">₦{{ number_format($restockRequest->invoice->total_amount, 2) }}</p>
                <span class="inline-flex rounded-full px-2 text-xs font-semibold bg-yellow-100 text-yellow-800 mt-1">
                    {{ ucfirst($restockRequest->invoice->status) }}
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- Reject Modal --}}
    @can('reject', $restockRequest)
    <div x-show="rejectOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40"
         x-transition>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 space-y-4"
             @click.outside="rejectOpen = false">
            <h3 class="text-base font-semibold text-gray-900">Reject Restock Request</h3>
            <form method="POST" action="{{ route('inventory.restock.reject', $restockRequest) }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Reason for Rejection <span class="text-red-500">*</span>
                        </label>
                        <textarea name="rejection_reason" rows="3" required
                                  class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                                  placeholder="Explain why this request is being rejected…"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="rejectOpen = false"
                                class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                            Reject Request
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- Receive Goods Modal --}}
    @can('receive', $restockRequest)
    <div x-show="receiveOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40"
         x-transition>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-4"
             @click.outside="receiveOpen = false">
            <h3 class="text-base font-semibold text-gray-900">Confirm Goods Received</h3>
            <p class="text-sm text-gray-500">
                Enter the actual quantities and costs. Stock will be updated and a supplier bill generated.
            </p>
            <form method="POST" action="{{ route('inventory.restock.receive', $restockRequest) }}">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Quantity Received <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="quantity_received"
                                   value="{{ $restockRequest->quantity_requested }}"
                                   min="0.001" step="0.001" required
                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <p class="text-xs text-gray-400 mt-0.5">
                                Requested: {{ number_format($restockRequest->quantity_requested, 3) }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Actual Unit Cost (₦) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit_cost"
                                   value="{{ $restockRequest->unit_cost }}"
                                   min="0" step="0.01" required
                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <p class="text-xs text-gray-400 mt-0.5">
                                Estimated: ₦{{ number_format($restockRequest->unit_cost, 2) }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Supplier Invoice No. <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <input type="text" name="supplier_invoice_no"
                               value="{{ $restockRequest->supplier_invoice_no }}"
                               placeholder="e.g. INV-2026-4521"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 text-xs text-yellow-800 space-y-1">
                        <p class="font-semibold">This action will:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>Add stock to <strong>{{ $restockRequest->item->name }}</strong></li>
                            <li>Recalculate weighted average cost</li>
                            <li>Generate a supplier bill (AP entry)</li>
                            <li>Post GL entries: Dr Inventory / Cr Accounts Payable</li>
                        </ul>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="receiveOpen = false"
                                class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                            Confirm Receipt
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endcan

</div>
@endsection
