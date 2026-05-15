@extends('layouts.app')
@section('page-title', 'Production Order ' . $productionOrder->order_number)

@section('content')
<div class="max-w-3xl space-y-5">

    <div class="flex items-center justify-between">
        <a href="{{ route('manufacturing.production.index') }}" class="text-sm text-green-600 hover:underline">← Back to Production Orders</a>
        @php
            $badge = match($productionOrder->status) {
                'draft'         => 'bg-gray-100 text-gray-600',
                'in_production' => 'bg-yellow-100 text-yellow-700',
                'completed'     => 'bg-green-100 text-green-700',
                'cancelled'     => 'bg-red-100 text-red-600',
                default         => 'bg-gray-100 text-gray-600',
            };
        @endphp
        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $badge }}">
            {{ ucfirst(str_replace('_', ' ', $productionOrder->status)) }}
        </span>
    </div>

    @if(session('success'))
    <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Header card --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ $productionOrder->order_number }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    BOM: {{ $productionOrder->bom->name }} v{{ $productionOrder->bom->version }}
                </p>
            </div>
            <div class="text-right text-sm text-gray-500">
                <p>Created {{ $productionOrder->created_at->format('d M Y') }}</p>
                @if($productionOrder->creator)
                    <p>by {{ $productionOrder->creator->name }}</p>
                @endif
            </div>
        </div>

        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 border-t pt-4">
            <div>
                <dt class="text-xs font-medium text-gray-500">Finished Item</dt>
                <dd class="mt-0.5 text-sm font-semibold text-gray-900">{{ $productionOrder->finishedItem->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">Planned Qty</dt>
                <dd class="mt-0.5 text-sm text-gray-900">{{ number_format($productionOrder->quantity_planned, 3) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">Produced Qty</dt>
                <dd class="mt-0.5 text-sm text-gray-900">{{ $productionOrder->quantity_produced ? number_format($productionOrder->quantity_produced, 3) : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">Additional Cost</dt>
                <dd class="mt-0.5 text-sm text-gray-900">₦{{ number_format($productionOrder->additional_cost, 2) }}</dd>
            </div>
            @if($productionOrder->started_at)
            <div>
                <dt class="text-xs font-medium text-gray-500">Started</dt>
                <dd class="mt-0.5 text-sm text-gray-900">{{ $productionOrder->started_at->format('d M Y H:i') }}</dd>
            </div>
            @endif
            @if($productionOrder->completed_at)
            <div>
                <dt class="text-xs font-medium text-gray-500">Completed</dt>
                <dd class="mt-0.5 text-sm text-gray-900">{{ $productionOrder->completed_at->format('d M Y H:i') }}</dd>
            </div>
            @endif
        </dl>

        @if($productionOrder->notes)
        <div class="mt-4 border-t pt-4">
            <p class="text-xs font-medium text-gray-500">Notes</p>
            <p class="mt-0.5 text-sm text-gray-700">{{ $productionOrder->notes }}</p>
        </div>
        @endif
    </div>

    {{-- Materials list --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-sm font-semibold text-gray-900">Raw Materials</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Material</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Required</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">In Stock</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Consumed</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Unit Cost</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Line Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($productionOrder->lines as $line)
                <tr>
                    <td class="px-4 py-3 text-gray-800">
                        {{ $line->rawMaterial->name }}
                        @if($line->rawMaterial->sku)
                            <span class="text-xs text-gray-400">({{ $line->rawMaterial->sku }})</span>
                        @endif
                        <span class="text-xs text-gray-400">— {{ $line->rawMaterial->unit }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">{{ number_format($line->quantity_required, 3) }}</td>
                    <td class="px-4 py-3 text-right">
                        @php $sufficient = (float)$line->rawMaterial->current_stock >= (float)$line->quantity_required - 0.0001; @endphp
                        <span class="{{ $line->quantity_consumed ? 'text-gray-400' : ($sufficient ? 'text-green-700' : 'text-red-600 font-semibold') }}">
                            {{ number_format($line->rawMaterial->current_stock, 3) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        {{ $line->quantity_consumed ? number_format($line->quantity_consumed, 3) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-500">
                        {{ $line->unit_cost_at_production ? '₦'.number_format($line->unit_cost_at_production, 2) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-700">
                        @if($line->quantity_consumed && $line->unit_cost_at_production)
                            ₦{{ number_format($line->quantity_consumed * $line->unit_cost_at_production, 2) }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Shortfall panel (only shown for active orders with insufficient stock) --}}
    @if($shortfalls->isNotEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-lg overflow-hidden">
        <div class="px-5 py-3 border-b border-amber-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="text-sm font-semibold text-amber-800">
                    {{ $shortfalls->count() }} material {{ Str::plural('shortfall', $shortfalls->count()) }} detected
                </h3>
            </div>

            @if($linkedRestocks->isNotEmpty())
            <span class="text-xs text-amber-700 bg-amber-100 border border-amber-200 rounded-full px-2.5 py-0.5">
                {{ $linkedRestocks->count() }} restock {{ Str::plural('request', $linkedRestocks->count()) }} already raised
            </span>
            @endif
        </div>

        <table class="w-full text-sm">
            <thead class="bg-amber-100/60">
                <tr>
                    <th class="px-5 py-2 text-left text-xs font-semibold text-amber-700">Material</th>
                    <th class="px-5 py-2 text-right text-xs font-semibold text-amber-700">Required</th>
                    <th class="px-5 py-2 text-right text-xs font-semibold text-amber-700">In Stock</th>
                    <th class="px-5 py-2 text-right text-xs font-semibold text-amber-700">Shortfall</th>
                    <th class="px-5 py-2 text-center text-xs font-semibold text-amber-700">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-amber-100">
                @foreach($shortfalls as $sf)
                @php
                    $existingRst = $linkedRestocks->firstWhere('item_id', $sf['item']->id);
                @endphp
                <tr>
                    <td class="px-5 py-2.5 font-medium text-gray-800">
                        {{ $sf['item']->name }}
                        @if($sf['item']->sku)
                            <span class="text-xs text-gray-400 ml-1">{{ $sf['item']->sku }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-2.5 text-right text-gray-700">{{ number_format($sf['required'], 3) }} {{ $sf['item']->unit }}</td>
                    <td class="px-5 py-2.5 text-right text-red-600 font-semibold">{{ number_format($sf['in_stock'], 3) }}</td>
                    <td class="px-5 py-2.5 text-right font-bold text-red-700">+{{ number_format($sf['gap'], 3) }}</td>
                    <td class="px-5 py-2.5 text-center">
                        @if($existingRst)
                            <a href="{{ route('inventory.restock.show', $existingRst) }}"
                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                                {{ $existingRst->request_number }}
                                <span class="capitalize">· {{ str_replace('_', ' ', $existingRst->status) }}</span>
                            </a>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">
                                No request yet
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @php
            $uncovered = $shortfalls->filter(fn($sf) =>
                $linkedRestocks->where('item_id', $sf['item']->id)->isEmpty()
            )->count();
        @endphp

        @if($uncovered > 0)
        <div class="px-5 py-3 border-t border-amber-200 flex items-center justify-between bg-amber-50">
            <p class="text-xs text-amber-700">
                {{ $uncovered }} {{ Str::plural('item', $uncovered) }} without a restock request.
                Suggested quantities equal the exact shortfall.
            </p>
            <form method="POST" action="{{ route('manufacturing.production.restock-shortfalls', $productionOrder) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-md hover:bg-amber-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Create Restock {{ Str::plural('Request', $uncovered) }} ({{ $uncovered }})
                </button>
            </form>
        </div>
        @else
        <div class="px-5 py-3 border-t border-amber-200 text-xs text-amber-700 bg-amber-50">
            All shortfall materials have active restock requests. Monitor their progress in
            <a href="{{ route('inventory.restock.index') }}" class="font-medium underline hover:text-amber-900">Restock Requests</a>.
        </div>
        @endif
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3">

        @can('start', $productionOrder)
        <form method="POST" action="{{ route('manufacturing.production.start', $productionOrder) }}">
            @csrf
            <button type="submit"
                    class="px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-md hover:bg-yellow-600">
                Start Production
            </button>
        </form>
        @endcan

        @can('complete', $productionOrder)
        <div x-data="{ open: {{ $errors->has('quantity_produced') ? 'true' : 'false' }} }">
            <button @click="open = true"
                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Mark Complete
            </button>

            <div x-show="open" x-cloak class="fixed inset-0 bg-gray-600/75 z-40 flex items-center justify-center p-4 overflow-y-auto">
                <div @click.stop class="bg-white rounded-lg shadow-xl w-full max-w-2xl my-auto">
                    <div class="p-6 border-b">
                        <h3 class="text-base font-semibold text-gray-900">Complete Production Order</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Adjust actual material quantities consumed if they differ from plan.</p>
                    </div>

                    @error('quantity_produced')
                    <div class="mx-6 mt-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">{{ $message }}</div>
                    @enderror
                    @error('lines')
                    <div class="mx-6 mt-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">{{ $message }}</div>
                    @enderror

                    <form method="POST" action="{{ route('manufacturing.production.complete', $productionOrder) }}">
                        @csrf

                        <div class="p-6 space-y-4">
                            {{-- Output & cost --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Quantity Produced <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="quantity_produced"
                                           value="{{ old('quantity_produced', $productionOrder->quantity_planned) }}"
                                           min="0.001" step="0.001" required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                    <p class="mt-1 text-xs text-gray-400">Planned: {{ number_format($productionOrder->quantity_planned, 3) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Additional Cost (₦)</label>
                                    <input type="number" name="additional_cost"
                                           value="{{ old('additional_cost', $productionOrder->additional_cost) }}"
                                           min="0" step="0.01"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                    <p class="mt-1 text-xs text-gray-400">Labour, energy, overheads.</p>
                                </div>
                            </div>

                            {{-- Per-material actual consumption --}}
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">
                                    Actual Materials Consumed
                                    <span class="text-xs font-normal text-gray-400 ml-1">— edit if actual differs from plan</span>
                                </h4>
                                <div class="border rounded-md overflow-hidden">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-50 border-b">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Material</th>
                                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500">In Stock</th>
                                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500">Planned</th>
                                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 w-36">Actual Used <span class="text-red-500">*</span></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($productionOrder->lines as $line)
                                            <tr>
                                                <td class="px-3 py-2 text-gray-800">
                                                    {{ $line->rawMaterial->name }}
                                                    <span class="text-xs text-gray-400">{{ $line->rawMaterial->unit }}</span>
                                                </td>
                                                <td class="px-3 py-2 text-right text-gray-500">
                                                    {{ number_format($line->rawMaterial->current_stock, 3) }}
                                                </td>
                                                <td class="px-3 py-2 text-right text-gray-500">
                                                    {{ number_format($line->quantity_required, 3) }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number"
                                                           name="lines[{{ $line->id }}][quantity_consumed]"
                                                           value="{{ old("lines.{$line->id}.quantity_consumed", number_format($line->quantity_required, 3, '.', '')) }}"
                                                           min="0" step="0.001" required
                                                           class="block w-full rounded border-gray-300 shadow-sm text-sm text-right focus:ring-green-500 focus:border-green-500">
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <p class="mt-1.5 text-xs text-gray-400">
                                    Pre-filled from the production plan. Zero is allowed (e.g. a material was not needed this run).
                                </p>
                            </div>

                            <p class="text-xs text-gray-500 bg-yellow-50 border border-yellow-200 rounded p-2">
                                This will deduct the actual quantities above from raw material stock, add finished goods, and post the GL entry (Dr 1202 / Cr 1201). This action cannot be undone.
                            </p>
                        </div>

                        <div class="px-6 py-4 border-t bg-gray-50 flex gap-2">
                            <button type="submit"
                                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                Confirm &amp; Complete
                            </button>
                            <button type="button" @click="open = false"
                                    class="px-4 py-2 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcan

        @can('cancel', $productionOrder)
        <form method="POST" action="{{ route('manufacturing.production.cancel', $productionOrder) }}"
              onsubmit="return confirm('Cancel this production order?')">
            @csrf
            <button type="submit"
                    class="px-4 py-2 border border-red-300 text-red-600 text-sm font-medium rounded-md hover:bg-red-50">
                Cancel Order
            </button>
        </form>
        @endcan

    </div>

</div>
@endsection
