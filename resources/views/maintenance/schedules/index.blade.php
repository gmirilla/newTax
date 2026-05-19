@extends('layouts.app')

@section('title', 'PM Schedules')

@section('content')
<div class="p-6 space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Preventive Maintenance Schedules</h1>
            <p class="text-sm text-gray-500 mt-0.5">Work orders are auto-generated daily when due.</p>
        </div>
        @can('create', \App\Models\MaintenanceSchedule::class)
        <a href="{{ route('maintenance.schedules.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Schedule
        </a>
        @endcan
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <select name="asset_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            <option value="">All Assets</option>
            @foreach($assets as $a)
            <option value="{{ $a->id }}" @selected(request('asset_id') == $a->id)>{{ $a->asset_name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-1.5 text-sm text-gray-600 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
            <input type="checkbox" name="overdue" value="1" @checked(request()->boolean('overdue'))> Show Overdue Only
        </label>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg">Filter</button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Schedule Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Asset</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden sm:table-cell">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden sm:table-cell">Frequency</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Next Due</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Active</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($schedules as $sch)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $sch->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $sch->asset->asset_name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 hidden sm:table-cell capitalize">{{ $sch->maintenance_type }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 hidden sm:table-cell">
                        {{ ucfirst(str_replace('_', ' ', $sch->frequency_type)) }}
                        @if($sch->frequency_type === 'custom_interval') ({{ $sch->frequency_days }}d) @endif
                    </td>
                    <td class="px-4 py-3 text-sm {{ $sch->is_active && $sch->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                        {{ $sch->next_due_date->format('d M Y') }}
                        @if($sch->is_active && $sch->isOverdue())
                        <span class="text-xs font-bold ml-1">OVERDUE</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('maintenance.schedules.toggle', $sch) }}">
                            @csrf
                            <button type="submit" class="relative inline-flex h-5 w-9 rounded-full transition-colors {{ $sch->is_active ? 'bg-green-600' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 rounded-full bg-white shadow transform transition-transform mt-0.5 {{ $sch->is_active ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('maintenance.schedules.destroy', $sch) }}" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline"
                                    onclick="return confirm('Delete this schedule?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">
                        No schedules found. <a href="{{ route('maintenance.schedules.create') }}" class="text-green-700 hover:underline">Create one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $schedules->links() }}
</div>
@endsection
