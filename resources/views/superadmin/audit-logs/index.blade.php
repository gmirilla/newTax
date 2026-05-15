@extends('superadmin.layout')

@section('page-title', 'Audit Logs')

@section('content')
@php
    $eventColor = function(string $event): string {
        if (str_starts_with($event, 'superadmin.'))   return 'bg-orange-100 text-orange-700';
        if (str_contains($event, 'deleted'))           return 'bg-red-100 text-red-700';
        if (str_contains($event, 'created'))           return 'bg-green-100 text-green-700';
        if (str_contains($event, 'updated'))           return 'bg-blue-100 text-blue-700';
        if (str_contains($event, 'exported'))          return 'bg-purple-100 text-purple-700';
        if (str_contains($event, 'login'))             return 'bg-gray-100 text-gray-600';
        if (str_contains($event, 'impersonat'))        return 'bg-yellow-100 text-yellow-700';
        if (str_contains($event, 'tax'))               return 'bg-indigo-100 text-indigo-700';
        return 'bg-gray-100 text-gray-600';
    };
    $shortEvent = fn(string $e) => str_replace(['superadmin.', '_'], ['', ' '], $e);
    $shortType  = fn(string $t) => class_basename($t);
@endphp

<div class="space-y-5">

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Events</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Today</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($stats['today']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">SuperAdmin Actions Today</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($stats['superadmin']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('superadmin.audit-logs') }}" class="flex flex-wrap gap-3 items-end">

            <div class="flex-1 min-w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">Company</label>
                <input type="text" name="tenant" value="{{ request('tenant') }}"
                       placeholder="Name or email…"
                       class="w-full rounded border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="w-52">
                <label class="block text-xs font-medium text-gray-500 mb-1">Event</label>
                <select name="event" class="w-full rounded border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All events</option>
                    <optgroup label="SuperAdmin">
                        <option value="superadmin." {{ request('event') === 'superadmin.' ? 'selected' : '' }}>All superadmin</option>
                        <option value="superadmin.impersonation" {{ request('event') === 'superadmin.impersonation' ? 'selected' : '' }}>Impersonation</option>
                        <option value="superadmin.subscription" {{ request('event') === 'superadmin.subscription' ? 'selected' : '' }}>Subscription changes</option>
                        <option value="superadmin.plan" {{ request('event') === 'superadmin.plan' ? 'selected' : '' }}>Plan changes</option>
                    </optgroup>
                    <optgroup label="Tenant Activity">
                        <option value="created"  {{ request('event') === 'created'  ? 'selected' : '' }}>Created</option>
                        <option value="updated"  {{ request('event') === 'updated'  ? 'selected' : '' }}>Updated</option>
                        <option value="deleted"  {{ request('event') === 'deleted'  ? 'selected' : '' }}>Deleted</option>
                        <option value="payment"  {{ request('event') === 'payment'  ? 'selected' : '' }}>Payment</option>
                        <option value="tax"      {{ request('event') === 'tax'      ? 'selected' : '' }}>Tax filed</option>
                        <option value="data_exported" {{ request('event') === 'data_exported' ? 'selected' : '' }}>Exported</option>
                    </optgroup>
                </select>
            </div>

            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">Scope</label>
                <select name="scope" class="w-full rounded border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All</option>
                    <option value="superadmin" {{ request('scope') === 'superadmin' ? 'selected' : '' }}>SuperAdmin only</option>
                    <option value="tenant"     {{ request('scope') === 'tenant'     ? 'selected' : '' }}>Tenant only</option>
                </select>
            </div>

            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full rounded border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full rounded border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700">
                    Filter
                </button>
                @if(request()->hasAny(['tenant','event','scope','date_from','date_to']))
                <a href="{{ route('superadmin.audit-logs') }}"
                   class="px-4 py-2 border border-gray-300 text-sm rounded hover:bg-gray-50 text-gray-600">
                    Clear
                </a>
                @endif
            </div>

        </form>
    </div>

    {{-- Results --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-3 border-b flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">
                {{ number_format($logs->total()) }} {{ Str::plural('event', $logs->total()) }}
            </h3>
            <span class="text-xs text-gray-400">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }}</span>
        </div>

        @if($logs->isEmpty())
        <div class="px-5 py-12 text-center text-sm text-gray-400">No audit events match your filters.</div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 border-b">
                <tr>
                    <th class="px-4 py-2.5 text-left font-semibold w-36">When</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Event</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Company</th>
                    <th class="px-4 py-2.5 text-left font-semibold">User</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Resource</th>
                    <th class="px-4 py-2.5 text-left font-semibold w-28">IP</th>
                    <th class="px-4 py-2.5 text-center font-semibold w-16">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($logs as $log)
                <tr x-data="{ open: false }" class="hover:bg-gray-50 align-top">

                    {{-- Timestamp --}}
                    <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">
                        <span title="{{ $log->created_at->format('d M Y H:i:s') }}">
                            {{ $log->created_at->format('d M H:i') }}
                        </span>
                        <span class="block text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
                    </td>

                    {{-- Event badge --}}
                    <td class="px-4 py-2.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $eventColor($log->event) }}">
                            {{ $shortEvent($log->event) }}
                        </span>
                        @if($log->tags && $log->tags !== 'general')
                        <span class="ml-1 text-xs text-gray-400">{{ $log->tags }}</span>
                        @endif
                    </td>

                    {{-- Company --}}
                    <td class="px-4 py-2.5">
                        @if($log->tenant)
                        <a href="{{ route('superadmin.companies.show', $log->tenant) }}"
                           class="text-indigo-600 hover:underline font-medium text-xs">
                            {{ $log->tenant->name }}
                        </a>
                        @else
                        <span class="text-gray-400 text-xs">Platform</span>
                        @endif
                    </td>

                    {{-- User --}}
                    <td class="px-4 py-2.5 text-xs text-gray-600">
                        @if($log->user)
                            {{ $log->user->name }}
                            <span class="block text-gray-400">{{ $log->user->role }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>

                    {{-- Resource --}}
                    <td class="px-4 py-2.5 text-xs text-gray-500">
                        <span class="font-mono">{{ $shortType($log->auditable_type) }}</span>
                        @if($log->auditable_id)
                        <span class="text-gray-400">#{{ $log->auditable_id }}</span>
                        @endif
                    </td>

                    {{-- IP --}}
                    <td class="px-4 py-2.5 text-xs text-gray-400 font-mono">
                        {{ $log->ip_address ?? '—' }}
                    </td>

                    {{-- Expand toggle --}}
                    <td class="px-4 py-2.5 text-center">
                        @if($log->old_values || $log->new_values)
                        <button @click="open = !open"
                                :class="open ? 'text-indigo-700 bg-indigo-50' : 'text-gray-400 hover:text-indigo-600'"
                                class="p-1 rounded text-xs font-mono transition-colors">
                            <span x-text="open ? '▲' : '▼'"></span>
                        </button>
                        @endif
                    </td>
                </tr>

                {{-- Expandable detail row --}}
                @if($log->old_values || $log->new_values)
                <tr x-show="open" x-cloak style="display:none">
                    <td colspan="7" class="px-4 pb-3 bg-gray-50 border-b border-gray-200">
                        <div class="grid grid-cols-2 gap-4 pt-2">
                            @if($log->old_values)
                            <div>
                                <p class="text-xs font-semibold text-red-600 mb-1">Before</p>
                                <pre class="text-xs bg-red-50 border border-red-100 rounded p-2 overflow-x-auto text-gray-700 whitespace-pre-wrap">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                            @endif
                            @if($log->new_values)
                            <div @class(['col-span-2' => !$log->old_values])>
                                <p class="text-xs font-semibold text-green-600 mb-1">After</p>
                                <pre class="text-xs bg-green-50 border border-green-100 rounded p-2 overflow-x-auto text-gray-700 whitespace-pre-wrap">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                            @endif
                        </div>
                        @if($log->url)
                        <p class="mt-2 text-xs text-gray-400 font-mono truncate">{{ $log->url }}</p>
                        @endif
                    </td>
                </tr>
                @endif

                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div class="px-5 py-3 border-t bg-gray-50">
            {{ $logs->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
