@extends('layouts.app')

@section('title', $asset->asset_code . ' — ' . $asset->asset_name)

@section('content')
<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('maintenance.assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Assets</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $asset->asset_name }}</h1>
            <p class="text-sm text-gray-500">{{ $asset->asset_code }}
                @if($asset->serial_number) · S/N: {{ $asset->serial_number }}@endif
                @if($asset->category) · {{ $asset->category->name }}@endif
            </p>
        </div>
        @php $color = \App\Models\MaintenanceAsset::STATUS_COLORS[$asset->status] ?? 'gray'; @endphp
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                {{ \App\Models\MaintenanceAsset::STATUSES[$asset->status] }}
            </span>
            @can('update', $asset)
            <a href="{{ route('maintenance.assets.edit', $asset) }}" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Edit</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Asset Details --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h2 class="font-semibold text-gray-800 border-b border-gray-100 pb-2">Asset Details</h2>
            @foreach([
                'Manufacturer'  => $asset->manufacturer,
                'Model'         => $asset->model,
                'Location'      => $asset->location,
                'Operator'      => $asset->assignedOperator?->name,
                'Purchase Date' => $asset->purchase_date?->format('d M Y'),
                'Warranty Exp.' => $asset->warranty_expiry
                    ? ($asset->warranty_expiry->isPast()
                        ? '<span class="text-red-600">'.$asset->warranty_expiry->format('d M Y').' (Expired)</span>'
                        : $asset->warranty_expiry->format('d M Y'))
                    : null,
                'PM Interval'   => $asset->maintenance_interval_days ? $asset->maintenance_interval_days . ' days' : null,
            ] as $label => $value)
            @if($value)
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $label }}</span>
                <span class="font-medium text-gray-800 text-right">{!! $value !!}</span>
            </div>
            @endif
            @endforeach

            @if($asset->notes)
            <div class="pt-2 border-t border-gray-100">
                <p class="text-xs text-gray-500 mb-1">Notes</p>
                <p class="text-sm text-gray-700">{{ $asset->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Cost Summary --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h2 class="font-semibold text-gray-800 border-b border-gray-100 pb-2">Maintenance Costs</h2>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Labor</span>
                    <span class="font-medium">₦{{ number_format($costRecord?->labor ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Parts</span>
                    <span class="font-medium">₦{{ number_format($costRecord?->parts ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm font-semibold border-t border-gray-100 pt-2">
                    <span>Total</span>
                    <span>₦{{ number_format($costRecord?->total ?? 0, 2) }}</span>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 space-y-2">
                <a href="{{ route('maintenance.work-orders.create', ['asset_id' => $asset->id]) }}"
                   class="block w-full text-center px-3 py-2 bg-green-700 text-white text-sm rounded-lg hover:bg-green-800">
                    + New Work Order
                </a>
                <a href="{{ route('maintenance.breakdowns.create', ['asset_id' => $asset->id]) }}"
                   class="block w-full text-center px-3 py-2 border border-red-300 text-red-700 text-sm rounded-lg hover:bg-red-50">
                    Report Breakdown
                </a>
                <a href="{{ route('maintenance.schedules.create', ['asset_id' => $asset->id]) }}"
                   class="block w-full text-center px-3 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">
                    + PM Schedule
                </a>
            </div>
        </div>

        {{-- PM Schedules --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 border-b border-gray-100 pb-2 mb-3">PM Schedules</h2>
            @forelse($asset->schedules->where('is_active', true) as $sch)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $sch->name }}</p>
                    <p class="text-xs text-gray-400">Every {{ $sch->frequency_days }} days · Due {{ $sch->next_due_date->format('d M Y') }}</p>
                </div>
                @if($sch->isOverdue())
                <span class="text-xs font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded">Overdue</span>
                @endif
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-4">No schedules configured.</p>
            @endforelse
        </div>
    </div>

    {{-- Work Order History --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Work Order History</h2>
            <span class="text-sm text-gray-400">{{ $asset->workOrders->count() }} total</span>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">WO Number</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden sm:table-cell">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden md:table-cell">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($recentWorkOrders as $wo)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-mono">
                        <a href="{{ route('maintenance.work-orders.show', $wo) }}" class="text-green-700 hover:underline">{{ $wo->work_order_number }}</a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ Str::limit($wo->title, 50) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 hidden sm:table-cell">{{ ucfirst($wo->source_type) }}</td>
                    <td class="px-4 py-3 text-sm">
                        @php $wc = \App\Models\MaintenanceWorkOrder::STATUS_COLORS[$wo->status] ?? 'gray'; @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-{{ $wc }}-100 text-{{ $wc }}-800">
                            {{ ucwords(str_replace('_', ' ', $wo->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-400 hidden md:table-cell">{{ $wo->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">No work orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
