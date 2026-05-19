@extends('layouts.app')

@section('title', 'Maintenance Dashboard')

@section('content')
<div class="p-6 space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Maintenance Dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5">Asset health and work order overview</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('maintenance.breakdowns.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Report Breakdown
            </a>
            <a href="{{ route('maintenance.work-orders.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Work Order
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Open Work Orders</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $openWorkOrders }}</p>
            <a href="{{ route('maintenance.work-orders.index') }}" class="mt-1 text-xs text-green-700 hover:underline">View all →</a>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 {{ $overdueCount > 0 ? 'border-yellow-400' : '' }}">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Overdue WOs</p>
            <p class="mt-2 text-3xl font-bold {{ $overdueCount > 0 ? 'text-yellow-600' : 'text-gray-900' }}">{{ $overdueCount }}</p>
            <span class="text-xs text-gray-400">Past scheduled date</span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 {{ $breakdownAssets > 0 ? 'border-red-400' : '' }}">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Assets in Breakdown</p>
            <p class="mt-2 text-3xl font-bold {{ $breakdownAssets > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $breakdownAssets }}</p>
            <a href="{{ route('maintenance.assets.index', ['status' => 'breakdown']) }}" class="text-xs text-red-600 hover:underline">View →</a>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">This Month's Cost</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">₦{{ number_format($monthCost, 0) }}</p>
            <span class="text-xs text-gray-400">Closed work orders</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recent Work Orders --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Recent Work Orders</h2>
                <a href="{{ route('maintenance.work-orders.index') }}" class="text-sm text-green-700 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentWorkOrders as $wo)
                <a href="{{ route('maintenance.work-orders.show', $wo) }}"
                   class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition-colors">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $wo->work_order_number }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $wo->title }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        @php $wc = \App\Models\MaintenanceWorkOrder::STATUS_COLORS[$wo->status] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs bg-{{ $wc }}-100 text-{{ $wc }}-800">
                            {{ ucwords(str_replace('_', ' ', $wo->status)) }}
                        </span>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $wo->asset->asset_name }}</p>
                    </div>
                </a>
                @empty
                <p class="px-5 py-8 text-sm text-center text-gray-400">No work orders yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Open Breakdowns & Top Assets --}}
        <div class="space-y-6">
            {{-- Open Breakdowns --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        Open Breakdowns
                    </h2>
                    <a href="{{ route('maintenance.breakdowns.index') }}" class="text-sm text-green-700 hover:underline">All</a>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($openBreakdowns as $bd)
                    <a href="{{ route('maintenance.breakdowns.show', $bd) }}"
                       class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $bd->asset->asset_name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ Str::limit($bd->issue_description, 60) }}</p>
                        </div>
                        <span class="text-xs font-medium px-1.5 py-0.5 rounded bg-red-100 text-red-700 flex-shrink-0">
                            {{ ucfirst($bd->severity) }}
                        </span>
                    </a>
                    @empty
                    <p class="px-5 py-6 text-sm text-center text-gray-400">No open breakdowns.</p>
                    @endforelse
                </div>
            </div>

            {{-- Top Assets by Cost --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Top Assets by Cost</h2>
                    <p class="text-xs text-gray-400">All-time maintenance spend</p>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($topAssets as $a)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $a->asset_name }}</p>
                            <p class="text-xs text-gray-400">{{ $a->asset_code }} · {{ $a->wo_count }} WOs</p>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 flex-shrink-0">
                            ₦{{ number_format($a->total_cost, 0) }}
                        </span>
                    </div>
                    @empty
                    <p class="px-5 py-6 text-sm text-center text-gray-400">No cost data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
