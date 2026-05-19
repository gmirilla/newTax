@extends('layouts.app')

@section('title', 'Breakdown Reports')

@section('content')
<div class="p-6 space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Breakdown Reports</h1>
        @can('create', \App\Models\MaintenanceBreakdown::class)
        <a href="{{ route('maintenance.breakdowns.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Report Breakdown
        </a>
        @endcan
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            <option value="">All Statuses</option>
            @foreach($statuses as $val => $label)
            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="asset_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            <option value="">All Assets</option>
            @foreach($assets as $a)
            <option value="{{ $a->id }}" @selected(request('asset_id') == $a->id)>{{ $a->asset_name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg">Filter</button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Breakdown #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Asset</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Severity</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Downtime Start</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden md:table-cell">Downtime Hours</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($breakdowns as $bd)
                @php $sc = \App\Models\MaintenanceBreakdown::SEVERITY_COLORS[$bd->severity] ?? 'gray'; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-mono">
                        <a href="{{ route('maintenance.breakdowns.show', $bd) }}" class="text-green-700 hover:underline">{{ $bd->breakdown_number }}</a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-800">{{ $bd->asset->asset_name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-{{ $sc }}-100 text-{{ $sc }}-800">
                            {{ ucfirst($bd->severity) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $bd->downtime_start->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 hidden md:table-cell">
                        {{ $bd->downtime_hours ? number_format($bd->downtime_hours, 1) . ' hrs' : $bd->calculateDowntimeHours() . ' hrs (ongoing)' }}
                    </td>
                    <td class="px-4 py-3 text-sm capitalize">{{ str_replace('_', ' ', $bd->status) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">No breakdowns reported.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $breakdowns->links() }}
</div>
@endsection
