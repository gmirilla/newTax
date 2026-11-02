@extends('superadmin.layout')

@section('page-title', 'Queue & Jobs')

@section('content')
<div class="space-y-5">

    @if(session('success'))
    <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Status strip --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Worker status</p>
            @if($status['worker_presumed_down'])
            <p class="text-lg font-bold text-red-600 mt-1">⚠ May be down</p>
            @else
            <p class="text-lg font-bold text-green-600 mt-1">● Healthy</p>
            @endif
            <p class="text-xs text-gray-400 mt-0.5">
                @if($status['worker_heartbeat_at'])
                    Last heartbeat {{ \Illuminate\Support\Carbon::parse($status['worker_heartbeat_at'])->diffForHumans() }}
                @else
                    No heartbeat recorded yet
                @endif
            </p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Pending jobs</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($status['pending_count']) }}</p>
            @if($status['oldest_pending_age_minutes'] !== null)
            <p class="text-xs text-gray-400 mt-0.5">Oldest: {{ $status['oldest_pending_age_minutes'] }} min</p>
            @endif
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Failed jobs</p>
            <p class="text-2xl font-bold {{ $status['failed_count'] > 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">
                {{ number_format($status['failed_count']) }}
            </p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 flex flex-col justify-between">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-2">Restart workers</p>
            <form method="POST" action="{{ route('superadmin.queue.restart-workers') }}"
                  onsubmit="return confirm('This signals running queue:work processes to exit after their current job. Your process supervisor (Forge/systemd) must bring them back up — it will NOT revive an already-crashed worker. Continue?')">
                @csrf
                <button type="submit" class="w-full px-3 py-1.5 bg-gray-800 text-white text-xs font-medium rounded hover:bg-gray-900">
                    Restart Workers
                </button>
            </form>
        </div>
    </div>

    {{-- Failed jobs --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-3 border-b flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Failed jobs</h3>
            @if($status['failed_count'] > 0)
            <form method="POST" action="{{ route('superadmin.queue.retry-all') }}"
                  onsubmit="return confirm('Retry all failed jobs?')">
                @csrf
                <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Retry All</button>
            </form>
            @endif
        </div>

        @if(collect($status['failed_jobs'])->isEmpty())
        <div class="px-5 py-12 text-center text-sm text-gray-400">No failed jobs. 🎉</div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 border-b">
                <tr>
                    <th class="px-4 py-2.5 text-left font-semibold w-36">Failed at</th>
                    <th class="px-4 py-2.5 text-left font-semibold w-28">Queue</th>
                    <th class="px-4 py-2.5 text-left font-semibold">Exception</th>
                    <th class="px-4 py-2.5 text-right font-semibold w-32">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($status['failed_jobs'] as $job)
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">
                        {{ \Illuminate\Support\Carbon::parse($job->failed_at)->format('d M H:i') }}
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-600">{{ $job->queue }}</td>
                    <td class="px-4 py-2.5 text-xs text-red-600 max-w-xl truncate" title="{{ $job->exception }}">
                        {{ Str::limit($job->exception, 150) }}
                    </td>
                    <td class="px-4 py-2.5 text-right whitespace-nowrap">
                        <form method="POST" action="{{ route('superadmin.queue.retry', $job->uuid) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium mr-3">Retry</button>
                        </form>
                        <form method="POST" action="{{ route('superadmin.queue.destroy', $job->uuid) }}" class="inline"
                              onsubmit="return confirm('Delete this failed job permanently?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>
@endsection
