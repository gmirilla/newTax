@extends('superadmin.layout')

@section('page-title', 'System Notifications')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">System Notifications</h1>
            <p class="text-sm text-gray-500 mt-0.5">Broadcast messages to tenants in-app and by email (critical only).</p>
        </div>
        <a href="{{ route('superadmin.notifications.create') }}"
           class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + New Notification
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($notifications->isEmpty())
        <p class="px-6 py-12 text-center text-gray-400 text-sm">No notifications yet.</p>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Title</th>
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-left">Target</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-center">Dismissed</th>
                    <th class="px-5 py-3 text-left">Sent / Created</th>
                    <th class="px-5 py-3 text-left">Expires</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($notifications as $n)
                @php
                    $typeColors = [
                        'info'     => 'bg-blue-100 text-blue-800',
                        'warning'  => 'bg-amber-100 text-amber-800',
                        'critical' => 'bg-red-100 text-red-800',
                        'success'  => 'bg-green-100 text-green-800',
                    ];
                    $expired = $n->expires_at && $n->expires_at->isPast();
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-900 max-w-xs truncate">
                        <a href="{{ route('superadmin.notifications.show', $n) }}" class="hover:text-indigo-600">
                            {{ $n->title }}
                        </a>
                    </td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$n->type] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($n->type) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-600 capitalize">
                        {{ $n->target_type }}
                        @if($n->target_type !== 'all' && $n->target_ids)
                            <span class="text-gray-400">({{ count($n->target_ids) }})</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($n->status === 'sent' && $expired)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Expired</span>
                        @elseif($n->status === 'sent')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Live</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Draft</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ $n->reads_count }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs">
                        {{ $n->send_at ? $n->send_at->format('d M Y, H:i') : $n->created_at->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3 text-gray-500 text-xs">
                        {{ $n->expires_at ? $n->expires_at->format('d M Y') : '—' }}
                    </td>
                    <td class="px-5 py-3 text-right">
                        <form method="POST" action="{{ route('superadmin.notifications.destroy', $n) }}"
                              onsubmit="return confirm('Delete this notification?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:text-red-700">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $notifications->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
