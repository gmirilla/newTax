@extends('layouts.app')

@section('title', 'Work Orders')

@section('content')
<div class="p-6 space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Maintenance Work Orders</h1>
        @can('create', \App\Models\MaintenanceWorkOrder::class)
        <a href="{{ route('maintenance.work-orders.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Work Order
        </a>
        @endcan
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="WO number or title…"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 w-52">
        <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            <option value="">All Statuses</option>
            @foreach($statuses as $val => $label)
            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="priority" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            <option value="">All Priorities</option>
            <option value="critical" @selected(request('priority') === 'critical')>Critical</option>
            <option value="high" @selected(request('priority') === 'high')>High</option>
            <option value="medium" @selected(request('priority') === 'medium')>Medium</option>
            <option value="low" @selected(request('priority') === 'low')>Low</option>
        </select>
        <select name="asset_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            <option value="">All Assets</option>
            @foreach($assets as $a)
            <option value="{{ $a->id }}" @selected(request('asset_id') == $a->id)>{{ $a->asset_name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filter</button>
        @if(request()->hasAny(['search','status','priority','asset_id']))
        <a href="{{ route('maintenance.work-orders.index') }}" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Clear</a>
        @endif
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">WO #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden sm:table-cell">Asset</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden md:table-cell">Assigned</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden lg:table-cell">Scheduled</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($workOrders as $wo)
                @php
                    $pc = \App\Models\MaintenanceWorkOrder::PRIORITY_COLORS[$wo->priority] ?? 'gray';
                    $sc = \App\Models\MaintenanceWorkOrder::STATUS_COLORS[$wo->status] ?? 'gray';
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-mono">
                        <a href="{{ route('maintenance.work-orders.show', $wo) }}" class="text-green-700 hover:underline">{{ $wo->work_order_number }}</a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-800 max-w-xs">
                        <a href="{{ route('maintenance.work-orders.show', $wo) }}" class="hover:text-green-700">
                            {{ Str::limit($wo->title, 60) }}
                        </a>
                        <p class="text-xs text-gray-400">{{ ucfirst($wo->source_type) }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 hidden sm:table-cell">{{ $wo->asset->asset_name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-{{ $pc }}-100 text-{{ $pc }}-800">
                            {{ ucfirst($wo->priority) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-{{ $sc }}-100 text-{{ $sc }}-800">
                            {{ ucwords(str_replace('_', ' ', $wo->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 hidden md:table-cell">{{ $wo->assignee?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-400 hidden lg:table-cell">
                        {{ $wo->scheduled_date?->format('d M Y') ?? '—' }}
                        @if($wo->scheduled_date?->isPast() && !in_array($wo->status, ['completed','closed']))
                        <span class="ml-1 text-xs text-red-600 font-medium">Overdue</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">No work orders found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $workOrders->links() }}
</div>
@endsection
