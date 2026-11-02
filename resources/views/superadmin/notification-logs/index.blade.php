@extends('superadmin.layout')

@section('page-title', 'Notification Logs')

@section('content')
@php
    $shortClass = fn(string $c) => class_basename($c);
@endphp

<div class="space-y-5">

    @if(session('success'))
    <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full rounded border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All</option>
                    <option value="sent"   {{ request('status') === 'sent'   ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div class="w-64">
                <label class="block text-xs font-medium text-gray-500 mb-1">Notification type</label>
                <select name="notification_class" class="w-full rounded border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All</option>
                    @foreach($notificationClasses as $class)
                    <option value="{{ $class }}" {{ request('notification_class') === $class ? 'selected' : '' }}>
                        {{ $shortClass($class) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700">Filter</button>
                @if(request()->hasAny(['status', 'notification_class']))
                <a href="{{ route('superadmin.notification-logs.index') }}" class="px-4 py-2 border border-gray-300 text-sm rounded hover:bg-gray-50 text-gray-600">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Results --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-3 border-b flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">
                {{ number_format($logs->total()) }} {{ Str::plural('notification', $logs->total()) }}
            </h3>
            <span class="text-xs text-gray-400">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }}</span>
        </div>

        @if($logs->isEmpty())
        <div class="px-5 py-12 text-center text-sm text-gray-400">No notifications match your filters.</div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 border-b">
                <tr>
                    <th class="px-4 py-2.5 text-left font-semibold w-36">When</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Recipient</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Notification</th>
                    <th class="px-4 py-2.5 text-left font-semibold w-20">Channel</th>
                    <th class="px-4 py-2.5 text-left font-semibold w-20">Status</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Error</th>
                    <th class="px-4 py-2.5 text-right font-semibold w-24">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($logs as $log)
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">
                        <span title="{{ $log->created_at->format('d M Y H:i:s') }}">{{ $log->created_at->format('d M H:i') }}</span>
                        <span class="block text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-600">
                        @if($log->notifiable)
                            {{ $log->notifiable->name ?? $log->notifiable->email ?? '#' . $log->notifiable_id }}
                        @else
                            <span class="text-gray-400">Deleted</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-xs font-mono text-gray-600">{{ $shortClass($log->notification_class) }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-500">{{ $log->channel }}</td>
                    <td class="px-4 py-2.5">
                        @if($log->status === 'sent')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Sent</span>
                        @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Failed</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-xs text-red-600 max-w-xs truncate" title="{{ $log->error_message }}">
                        {{ $log->error_message }}
                    </td>
                    <td class="px-4 py-2.5 text-right">
                        <form method="POST" action="{{ route('superadmin.notification-logs.resend', $log) }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('Resend this notification now?')"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                Resend
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($logs->hasPages())
        <div class="px-5 py-3 border-t bg-gray-50">{{ $logs->links() }}</div>
        @endif
        @endif
    </div>

</div>
@endsection
