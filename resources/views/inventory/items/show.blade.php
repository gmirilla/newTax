@extends('layouts.app')
@section('page-title', $inventoryItem->name)

@section('content')
<div x-data="{ adjustOpen: false }" class="space-y-5">

    {{-- Back + Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('inventory.items.index') }}" class="text-sm text-green-600 hover:underline">← Back to Items</a>
        <div class="flex gap-2">
            @can('create', App\Models\RestockRequest::class)
            <a href="{{ route('inventory.restock.create', ['item_id' => $inventoryItem->id]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                📦 Request Restock
            </a>
            @endcan
            @can('adjust', $inventoryItem)
            <button @click="adjustOpen = true"
                    class="inline-flex items-center px-4 py-2 border border-yellow-400 text-sm font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100">
                ⚖️ Adjust Stock
            </button>
            @endcan
            @can('update', $inventoryItem)
            <a href="{{ route('inventory.items.edit', $inventoryItem) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Edit Item
            </a>
            @endcan
        </div>
    </div>

    {{-- Item detail + Stats row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Item Card --}}
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $inventoryItem->name }}</h1>
                    @if($inventoryItem->sku)
                        <p class="text-sm text-gray-400 mt-0.5">SKU: {{ $inventoryItem->sku }}</p>
                    @endif
                    <p class="text-sm text-gray-500 mt-0.5">{{ $inventoryItem->category?->name ?? 'Uncategorised' }} · {{ $inventoryItem->unit }}</p>
                </div>
                @php
                    $stockNum = (float)$inventoryItem->current_stock;
                    $levelNum = (float)$inventoryItem->restock_level;
                    if ($stockNum <= 0) { $stockColor = 'red'; $stockLabel = 'Out of Stock'; }
                    elseif ($levelNum > 0 && $stockNum <= $levelNum) { $stockColor = 'yellow'; $stockLabel = 'Low Stock'; }
                    else { $stockColor = 'green'; $stockLabel = 'In Stock'; }
                @endphp
                <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold
                    bg-{{ $stockColor }}-100 text-{{ $stockColor }}-800">
                    {{ $stockLabel }}
                </span>
            </div>

            @if($inventoryItem->description)
                <p class="mt-3 text-sm text-gray-600">{{ $inventoryItem->description }}</p>
            @endif

            <dl class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 rounded-md p-3">
                    <dt class="text-xs text-gray-500 uppercase">In Stock</dt>
                    <dd class="mt-1 text-lg font-bold text-gray-900">{{ number_format($stockNum, 3) }}</dd>
                </div>
                <div class="bg-gray-50 rounded-md p-3">
                    <dt class="text-xs text-gray-500 uppercase">Restock Level</dt>
                    <dd class="mt-1 text-lg font-bold text-gray-900">{{ number_format($levelNum, 3) }}</dd>
                </div>
                <div class="bg-gray-50 rounded-md p-3">
                    <dt class="text-xs text-gray-500 uppercase">Selling Price</dt>
                    <dd class="mt-1 text-lg font-bold text-gray-900">₦{{ number_format($inventoryItem->selling_price, 2) }}</dd>
                </div>
                <div class="bg-gray-50 rounded-md p-3">
                    <dt class="text-xs text-gray-500 uppercase">Avg Cost</dt>
                    <dd class="mt-1 text-lg font-bold text-gray-900">₦{{ number_format($inventoryItem->avg_cost, 4) }}</dd>
                </div>
            </dl>

            <dl class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Reference Cost Price</dt>
                    <dd class="font-medium text-gray-900">₦{{ number_format($inventoryItem->cost_price, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Stock Value (at avg cost)</dt>
                    <dd class="font-medium text-gray-900">₦{{ number_format($stockNum * (float)$inventoryItem->avg_cost, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Potential Revenue</dt>
                    <dd class="font-medium text-gray-900">₦{{ number_format($stockNum * (float)$inventoryItem->selling_price, 2) }}</dd>
                </div>
            </dl>
        </div>

        {{-- Pending Restocks --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Pending Restocks</h3>
            @forelse($pendingRestocks as $rr)
            <div class="border rounded-md p-3 mb-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-900">{{ $rr->request_number }}</span>
                    <span class="inline-flex rounded-full px-2 text-xs font-semibold
                        {{ $rr->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ ucfirst($rr->status) }}
                    </span>
                </div>
                <p class="text-gray-500 mt-1">Qty: {{ number_format($rr->quantity_requested, 3) }} · ₦{{ number_format($rr->unit_cost, 2) }}/unit</p>
                <p class="text-gray-400 text-xs mt-0.5">By {{ $rr->requester->name }}</p>
            </div>
            @empty
            <p class="text-sm text-gray-400">No pending restock requests.</p>
            @endforelse
        </div>
    </div>

    {{-- Stock Movement History --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-base font-semibold text-gray-900">Stock Movement History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty In</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty Out</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost (₦)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @php
                        $typeColors = [
                            'sale'           => 'red',
                            'restock'        => 'green',
                            'adjustment_in'  => 'blue',
                            'adjustment_out' => 'orange',
                            'opening'        => 'gray',
                            'production_in'  => 'green',
                            'production_out' => 'red',
                        ];
                        $typeLabels = [
                            'sale'           => 'Sale',
                            'restock'        => 'Restock',
                            'adjustment_in'  => 'Adj In',
                            'adjustment_out' => 'Adj Out',
                            'opening'        => 'Opening',
                            'production_in'  => 'Prod In',
                            'production_out' => 'Prod Out',
                        ];
                        $outTypes = ['sale', 'adjustment_out', 'production_out'];
                    @endphp
                    @forelse($movements as $mv)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $mv->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @php $color = $typeColors[$mv->type] ?? 'gray'; @endphp
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ $typeLabels[$mv->type] ?? $mv->type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm {{ !in_array($mv->type, $outTypes) ? 'text-green-700 font-medium' : 'text-gray-300' }}">
                            {{ !in_array($mv->type, $outTypes) ? '+'.number_format($mv->quantity, 3) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm {{ in_array($mv->type, $outTypes) ? 'text-red-700 font-medium' : 'text-gray-300' }}">
                            {{ in_array($mv->type, $outTypes) ? '-'.number_format($mv->quantity, 3) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                            {{ number_format($mv->running_balance, 3) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-600">
                            {{ number_format($mv->unit_cost, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ $mv->notes ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $mv->creator->name }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400">No movements recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
        <div class="px-6 py-4 border-t">{{ $movements->links() }}</div>
        @endif
    </div>

</div>

{{-- Stock Adjustment Modal --}}
@can('adjust', $inventoryItem)
<template x-teleport="body">
    <div x-show="adjustOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="adjustOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Adjust Stock — {{ $inventoryItem->name }}</h3>
            <p class="text-sm text-gray-500 mb-4">
                Current stock: <strong>{{ number_format($inventoryItem->current_stock, 3) }} {{ $inventoryItem->unit }}</strong>
            </p>

            <form method="POST" action="{{ route('inventory.items.adjust', $inventoryItem) }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Adjustment Type</label>
                        <select name="type" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            <option value="adjustment_in">Add Stock (Adjustment In)</option>
                            <option value="adjustment_out">Remove Stock (Adjustment Out)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Quantity <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity" required min="0.001" step="0.001"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                               placeholder="0.000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reason / Notes</label>
                        <textarea name="notes" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                                  placeholder="e.g. Damaged goods, stock count correction…"></textarea>
                    </div>
                </div>
                <div class="mt-5 flex gap-3">
                    <button type="submit"
                            class="flex-1 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                        Apply Adjustment
                    </button>
                    <button type="button" @click="adjustOpen = false"
                            class="flex-1 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
@endcan

@endsection
