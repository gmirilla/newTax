@extends('layouts.app')

@section('title', $breakdown->breakdown_number)

@section('content')
<div class="p-6 space-y-6" x-data="{ resolveOpen: false }">

    @if(session('success'))
    <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    @php $sc = \App\Models\MaintenanceBreakdown::SEVERITY_COLORS[$breakdown->severity] ?? 'gray'; @endphp

    <div class="flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('maintenance.breakdowns.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Breakdowns</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $breakdown->breakdown_number }}</h1>
            <p class="text-sm text-gray-500">Reported by {{ $breakdown->reporter->name }} at {{ $breakdown->downtime_start->format('d M Y H:i') }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="px-2.5 py-1 rounded text-xs font-semibold bg-{{ $sc }}-100 text-{{ $sc }}-800">{{ ucfirst($breakdown->severity) }}</span>
            <span class="px-2.5 py-1 rounded-full text-xs bg-gray-100 text-gray-700 capitalize">{{ str_replace('_', ' ', $breakdown->status) }}</span>
        </div>
    </div>

    {{-- Action buttons --}}
    @can('update', $breakdown)
    <div class="flex flex-wrap gap-2">
        @if($breakdown->isResolvable())
        <button @click="resolveOpen = true" class="px-4 py-2 bg-green-700 text-white text-sm rounded-lg hover:bg-green-800">
            Resolve Breakdown
        </button>
        @endif
        @if($breakdown->status === 'resolved')
        <form method="POST" action="{{ route('maintenance.breakdowns.close', $breakdown) }}">
            @csrf
            <button class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">Close Record</button>
        </form>
        @endif
    </div>
    @endcan

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Breakdown Details --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b border-gray-100 pb-2">Breakdown Details</h2>

            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Asset</p>
                <a href="{{ route('maintenance.assets.show', $breakdown->asset) }}" class="text-sm font-medium text-green-700 hover:underline">
                    {{ $breakdown->asset->asset_code }} — {{ $breakdown->asset->asset_name }}
                </a>
            </div>

            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Issue Description</p>
                <p class="text-sm text-gray-800 whitespace-pre-line">{{ $breakdown->issue_description }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-xs text-gray-500">Downtime Start</p>
                    <p class="font-medium">{{ $breakdown->downtime_start->format('d M Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Downtime End</p>
                    <p class="font-medium">{{ $breakdown->downtime_end?->format('d M Y H:i') ?? 'Ongoing' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Total Downtime</p>
                    <p class="font-semibold {{ $breakdown->downtime_hours > 8 ? 'text-red-600' : '' }}">
                        {{ $breakdown->downtime_hours
                            ? number_format($breakdown->downtime_hours, 1) . ' hrs'
                            : number_format($breakdown->calculateDowntimeHours(), 1) . ' hrs (ongoing)' }}
                    </p>
                </div>
            </div>

            @if($breakdown->root_cause || $breakdown->corrective_action)
            <div class="pt-3 border-t border-gray-100 space-y-3">
                @if($breakdown->root_cause)
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Root Cause</p>
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $breakdown->root_cause }}</p>
                </div>
                @endif
                @if($breakdown->corrective_action)
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Corrective Action</p>
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $breakdown->corrective_action }}</p>
                </div>
                @endif
            </div>
            @endif
        </div>

        {{-- Linked Work Order --}}
        <div class="space-y-4">
            @if($breakdown->workOrder)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 border-b border-gray-100 pb-2 mb-3">Corrective Work Order</h2>
                @php $wc = \App\Models\MaintenanceWorkOrder::STATUS_COLORS[$breakdown->workOrder->status] ?? 'gray'; @endphp
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">WO Number</span>
                        <a href="{{ route('maintenance.work-orders.show', $breakdown->workOrder) }}" class="font-medium text-green-700 hover:underline">
                            {{ $breakdown->workOrder->work_order_number }}
                        </a>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="px-2 py-0.5 rounded-full text-xs bg-{{ $wc }}-100 text-{{ $wc }}-800">
                            {{ ucwords(str_replace('_', ' ', $breakdown->workOrder->status)) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Assigned To</span>
                        <span class="font-medium">{{ $breakdown->workOrder->assignee?->name ?? 'Unassigned' }}</span>
                    </div>
                </div>
            </div>
            @else
            <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-4 text-sm text-yellow-800">
                No work order created for this breakdown.
                <a href="{{ route('maintenance.work-orders.create', ['asset_id' => $breakdown->asset_id]) }}"
                   class="font-medium underline ml-1">Create one now</a>
            </div>
            @endif
        </div>
    </div>

    {{-- Resolve Modal --}}
    <div x-show="resolveOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div @click.outside="resolveOpen = false" class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Resolve Breakdown</h3>
            <form method="POST" action="{{ route('maintenance.breakdowns.resolve', $breakdown) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Downtime End <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="downtime_end" value="{{ now()->format('Y-m-d\TH:i') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Root Cause <span class="text-red-500">*</span></label>
                    <textarea name="root_cause" rows="3" required maxlength="2000"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">{{ $breakdown->root_cause }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Corrective Action <span class="text-red-500">*</span></label>
                    <textarea name="corrective_action" rows="3" required maxlength="2000"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">{{ $breakdown->corrective_action }}</textarea>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="px-5 py-2 bg-green-700 text-white text-sm rounded-lg hover:bg-green-800">Mark Resolved</button>
                    <button type="button" @click="resolveOpen = false" class="px-5 py-2 border border-gray-300 text-sm rounded-lg">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
