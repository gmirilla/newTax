@extends('layouts.app')

@section('title', $workOrder->work_order_number)

@section('content')
<div class="p-6 space-y-6" x-data="{ addPartOpen: false, logLaborOpen: false, completeOpen: false }">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    @php
        $sc = \App\Models\MaintenanceWorkOrder::STATUS_COLORS[$workOrder->status] ?? 'gray';
        $pc = \App\Models\MaintenanceWorkOrder::PRIORITY_COLORS[$workOrder->priority] ?? 'gray';
    @endphp
    <div class="flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('maintenance.work-orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Work Orders</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $workOrder->work_order_number }}</h1>
            <p class="text-sm text-gray-600 mt-0.5">{{ $workOrder->title }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-{{ $pc }}-100 text-{{ $pc }}-800">{{ ucfirst($workOrder->priority) }}</span>
            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $sc }}-100 text-{{ $sc }}-800">
                {{ ucwords(str_replace('_', ' ', $workOrder->status)) }}
            </span>
        </div>
    </div>

    {{-- Action Bar --}}
    @can('update', $workOrder)
    <div class="flex flex-wrap gap-2">
        @if($workOrder->canStart())
        <form method="POST" action="{{ route('maintenance.work-orders.start', $workOrder) }}">
            @csrf
            <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Start Work</button>
        </form>
        @endif

        @if($workOrder->canComplete())
        <button @click="completeOpen = true" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
            Mark Completed
        </button>
        @endif

        @can('close', $workOrder)
        @if($workOrder->canClose())
        <form method="POST" action="{{ route('maintenance.work-orders.close', $workOrder) }}">
            @csrf
            <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900"
                    onclick="return confirm('Close this work order? Inventory will be deducted and GL entries posted.')">
                Close & Post GL
            </button>
        </form>
        @endif
        @endcan

        @if(!$workOrder->isClosed())
        <button @click="addPartOpen = true" class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">
            + Add Part
        </button>
        <button @click="logLaborOpen = true" class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">
            + Log Labor
        </button>
        @endif
    </div>
    @endcan

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Details --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Details Card --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-4">Work Order Details</h2>
                <div class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm">
                    <div><span class="text-gray-500">Asset</span><br>
                        <a href="{{ route('maintenance.assets.show', $workOrder->asset) }}" class="font-medium text-green-700 hover:underline">
                            {{ $workOrder->asset->asset_code }} — {{ $workOrder->asset->asset_name }}
                        </a>
                    </div>
                    <div><span class="text-gray-500">Type</span><br>
                        <span class="font-medium">{{ ucfirst($workOrder->source_type) }}</span>
                    </div>
                    <div><span class="text-gray-500">Assigned To</span><br>
                        <span class="font-medium">{{ $workOrder->assignee?->name ?? 'Unassigned' }}</span>
                    </div>
                    <div><span class="text-gray-500">Scheduled</span><br>
                        <span class="font-medium {{ $workOrder->scheduled_date?->isPast() && !$workOrder->isClosed() ? 'text-red-600' : '' }}">
                            {{ $workOrder->scheduled_date?->format('d M Y') ?? '—' }}
                        </span>
                    </div>
                    <div><span class="text-gray-500">Est. Hours</span><br>
                        <span class="font-medium">{{ $workOrder->estimated_hours ?: '—' }}</span>
                    </div>
                    <div><span class="text-gray-500">Actual Hours</span><br>
                        <span class="font-medium">{{ $workOrder->actual_hours ?: '—' }}</span>
                    </div>
                    @if($workOrder->started_at)
                    <div><span class="text-gray-500">Started</span><br>
                        <span class="font-medium">{{ $workOrder->started_at->format('d M Y H:i') }}</span>
                    </div>
                    @endif
                    @if($workOrder->completed_at)
                    <div><span class="text-gray-500">Completed</span><br>
                        <span class="font-medium">{{ $workOrder->completed_at->format('d M Y H:i') }}</span>
                    </div>
                    @endif
                </div>
                @if($workOrder->description)
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Description</p>
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $workOrder->description }}</p>
                </div>
                @endif
                @if($workOrder->remarks)
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Remarks</p>
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $workOrder->remarks }}</p>
                </div>
                @endif
            </div>

            {{-- Spare Parts --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Spare Parts / Consumables</h2>
                    <span class="text-sm font-semibold text-gray-700">₦{{ number_format($workOrder->totalPartsCost(), 2) }}</span>
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Item</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Unit Cost</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Subtotal</th>
                            @if(!$workOrder->isClosed())<th class="px-4 py-2"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($workOrder->parts as $part)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $part->inventoryItem->name }}
                                <span class="text-xs text-gray-400">{{ $part->inventoryItem->unit }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-right">{{ $part->quantity_requested }}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600">₦{{ number_format($part->unit_cost, 2) }}</td>
                            <td class="px-4 py-2 text-sm text-right font-medium">₦{{ number_format($part->subtotal, 2) }}</td>
                            @if(!$workOrder->isClosed())
                            <td class="px-4 py-2 text-right">
                                <form method="POST" action="{{ route('maintenance.work-orders.parts.remove', [$workOrder, $part]) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No parts added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Labor Logs --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Labor Log</h2>
                    <span class="text-sm font-semibold text-gray-700">
                        {{ number_format($workOrder->laborLogs->sum('hours_worked'), 1) }} hrs ·
                        ₦{{ number_format($workOrder->totalLaborCost(), 2) }}
                    </span>
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Technician</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Hours</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($workOrder->laborLogs as $log)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $log->technician->name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $log->work_date->format('d M Y') }}</td>
                            <td class="px-4 py-2 text-sm text-right">{{ $log->hours_worked }}</td>
                            <td class="px-4 py-2 text-sm text-right font-medium">₦{{ number_format($log->labor_cost, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-400">No labor logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            {{-- Cost Summary --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Cost Summary</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Parts</span><span>₦{{ number_format($workOrder->totalPartsCost(), 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Labor</span><span>₦{{ number_format($workOrder->totalLaborCost(), 2) }}</span></div>
                    <div class="flex justify-between font-semibold border-t border-gray-100 pt-2">
                        <span>Total</span>
                        <span>₦{{ number_format($workOrder->totalPartsCost() + $workOrder->totalLaborCost(), 2) }}</span>
                    </div>
                </div>
                @if($workOrder->cost?->isPosted())
                <div class="mt-3 p-2 bg-green-50 rounded text-xs text-green-700">
                    GL posted · {{ $workOrder->cost->posted_at->format('d M Y') }}
                </div>
                @endif
            </div>

            {{-- Linked Breakdown --}}
            @if($workOrder->breakdown)
            <div class="bg-red-50 rounded-xl border border-red-200 p-4">
                <h2 class="font-semibold text-red-800 mb-2 text-sm">Linked Breakdown</h2>
                <a href="{{ route('maintenance.breakdowns.show', $workOrder->breakdown) }}"
                   class="text-sm text-red-700 hover:underline">{{ $workOrder->breakdown->breakdown_number }}</a>
                <p class="text-xs text-red-600 mt-1">{{ Str::limit($workOrder->breakdown->issue_description, 80) }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Add Part Modal --}}
    <div x-show="addPartOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div @click.outside="addPartOpen = false" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Spare Part</h3>
            <form method="POST" action="{{ route('maintenance.work-orders.parts.add', $workOrder) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inventory Item <span class="text-red-500">*</span></label>
                    <select name="inventory_item_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <option value="">— Select Item —</option>
                        @foreach($availableItems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->sku }}) — Stock: {{ $item->current_stock }} {{ $item->unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity_requested" min="0.001" step="0.001" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <input type="text" name="notes" maxlength="500"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="px-5 py-2 bg-green-700 text-white text-sm rounded-lg hover:bg-green-800">Add Part</button>
                    <button type="button" @click="addPartOpen = false" class="px-5 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Log Labor Modal --}}
    <div x-show="logLaborOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div @click.outside="logLaborOpen = false" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Log Labor Hours</h3>
            <form method="POST" action="{{ route('maintenance.work-orders.labor', $workOrder) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="work_date" value="{{ now()->toDateString() }}" required max="{{ now()->toDateString() }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hours Worked <span class="text-red-500">*</span></label>
                    <input type="number" name="hours_worked" min="0.5" max="24" step="0.5" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hourly Rate (₦)</label>
                    <input type="number" name="hourly_rate" min="0" step="0.01"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" name="description" maxlength="500"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="px-5 py-2 bg-green-700 text-white text-sm rounded-lg hover:bg-green-800">Save</button>
                    <button type="button" @click="logLaborOpen = false" class="px-5 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Complete Modal --}}
    <div x-show="completeOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div @click.outside="completeOpen = false" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Mark as Completed</h3>
            <form method="POST" action="{{ route('maintenance.work-orders.complete', $workOrder) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Completion Remarks</label>
                    <textarea name="remarks" rows="4"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">{{ $workOrder->remarks }}</textarea>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="px-5 py-2 bg-green-700 text-white text-sm rounded-lg hover:bg-green-800">Mark Completed</button>
                    <button type="button" @click="completeOpen = false" class="px-5 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
