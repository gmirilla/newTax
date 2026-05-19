@extends('layouts.app')

@section('title', 'Asset Register')

@section('content')
<div class="p-6 space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Asset Register</h1>
        @can('create', \App\Models\MaintenanceAsset::class)
        <a href="{{ route('maintenance.assets.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Asset
        </a>
        @endcan
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search name, code, serial…"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent w-56">
        <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            <option value="">All Statuses</option>
            @foreach($statuses as $val => $label)
            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="category_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filter</button>
        @if(request()->hasAny(['search','status','category_id']))
        <a href="{{ route('maintenance.assets.index') }}" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Asset Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden sm:table-cell">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden md:table-cell">Location</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($assets as $asset)
                @php
                    $colors = \App\Models\MaintenanceAsset::STATUS_COLORS;
                    $color  = $colors[$asset->status] ?? 'gray';
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-mono text-gray-700">{{ $asset->asset_code }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('maintenance.assets.show', $asset) }}"
                           class="text-sm font-medium text-gray-900 hover:text-green-700">
                            {{ $asset->asset_name }}
                        </a>
                        @if($asset->manufacturer)
                        <p class="text-xs text-gray-400">{{ $asset->manufacturer }} {{ $asset->model }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 hidden sm:table-cell">{{ $asset->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 hidden md:table-cell">{{ $asset->location ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            bg-{{ $color }}-100 text-{{ $color }}-800">
                            {{ \App\Models\MaintenanceAsset::STATUSES[$asset->status] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('maintenance.assets.show', $asset) }}"
                           class="text-sm text-green-700 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">
                        No assets found. <a href="{{ route('maintenance.assets.create') }}" class="text-green-700 hover:underline">Add your first asset</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $assets->links() }}
</div>
@endsection
