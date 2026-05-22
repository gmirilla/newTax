@extends('superadmin.layout')

@section('page-title', 'Notification Detail')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center gap-4">
        <a href="{{ route('superadmin.notifications.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Notifications</a>
    </div>

    @php
        $typeColors = [
            'info'     => ['ring' => 'ring-blue-200',  'bg' => 'bg-blue-50',  'text' => 'text-blue-800',  'badge' => 'bg-blue-100 text-blue-800'],
            'warning'  => ['ring' => 'ring-amber-200', 'bg' => 'bg-amber-50', 'text' => 'text-amber-800', 'badge' => 'bg-amber-100 text-amber-800'],
            'critical' => ['ring' => 'ring-red-200',   'bg' => 'bg-red-50',   'text' => 'text-red-800',   'badge' => 'bg-red-100 text-red-800'],
            'success'  => ['ring' => 'ring-green-200', 'bg' => 'bg-green-50', 'text' => 'text-green-800', 'badge' => 'bg-green-100 text-green-800'],
        ];
        $c = $typeColors[$notification->type] ?? $typeColors['info'];
        $expired = $notification->expires_at && $notification->expires_at->isPast();
    @endphp

    {{-- Header card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $c['badge'] }}">{{ ucfirst($notification->type) }}</span>
                <h1 class="text-xl font-bold text-gray-900 mt-2">{{ $notification->title }}</h1>
                <p class="text-xs text-gray-400 mt-1">
                    Created by {{ $notification->createdBy?->name ?? 'Superadmin' }}
                    · {{ $notification->created_at->format('d M Y, H:i') }}
                </p>
            </div>
            <div class="text-right shrink-0">
                @if($notification->status === 'sent' && $expired)
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Expired</span>
                @elseif($notification->status === 'sent')
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Live</span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Draft</span>
                @endif
            </div>
        </div>

        <div class="{{ $c['bg'] }} {{ $c['ring'] }} ring-1 rounded-lg px-4 py-3 text-sm {{ $c['text'] }} whitespace-pre-line leading-relaxed">
            {{ $notification->message }}
        </div>

        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Target</dt>
                <dd class="font-medium text-gray-800 capitalize">
                    {{ $notification->target_type }}
                    @if($notification->target_type !== 'all' && $notification->target_ids)
                        ({{ count($notification->target_ids) }})
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Sent at</dt>
                <dd class="font-medium text-gray-800">{{ $notification->send_at?->format('d M Y, H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Expires</dt>
                <dd class="font-medium text-gray-800">{{ $notification->expires_at?->format('d M Y') ?? 'Never' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Dismissed by</dt>
                <dd class="font-medium text-gray-800">{{ $notification->reads_count }} user(s)</dd>
            </div>
        </dl>
    </div>

    {{-- Recent dismissals --}}
    @if($recentReads->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Recent Dismissals</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">User</th>
                    <th class="px-5 py-3 text-left">Company</th>
                    <th class="px-5 py-3 text-left">Dismissed at</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($recentReads as $read)
                <tr>
                    <td class="px-5 py-3 text-gray-800">{{ $read->user?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $read->user?->tenant?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $read->read_at->format('d M Y, H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Delete --}}
    <form method="POST" action="{{ route('superadmin.notifications.destroy', $notification) }}"
          onsubmit="return confirm('Permanently delete this notification?')">
        @csrf @method('DELETE')
        <button class="text-sm text-red-500 hover:text-red-700">Delete notification</button>
    </form>
</div>
@endsection
