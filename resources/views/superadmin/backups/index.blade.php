@extends('superadmin.layout')

@section('page-title', 'Backups')

@section('content')
<div class="space-y-6" x-data="{ running: false }">

    {{-- Header actions --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">
            Backups run daily at 02:00 to local disk and Namecheap SFTP.
            Configure credentials in <code class="bg-gray-100 px-1 rounded">.env</code> (BACKUP_SFTP_* variables).
        </p>
        <form method="POST" action="{{ route('superadmin.backups.run') }}" x-on:submit="running = true">
            @csrf
            <button
                type="submit"
                :disabled="running"
                :class="running ? 'opacity-60 cursor-not-allowed' : 'hover:bg-indigo-700 cursor-pointer'"
                class="bg-indigo-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors"
            >
                <span x-show="!running">Run Backup Now</span>
                <span x-show="running" x-cloak>Running…</span>
            </button>
        </form>
    </div>

    @forelse($statuses as $status)
    <div class="bg-white rounded-lg shadow">

        {{-- Disk header --}}
        <div class="px-5 py-4 border-b flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="font-mono font-semibold text-gray-700">{{ $status['disk'] }}</span>
                @if($status['healthy'])
                    <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-medium">Healthy</span>
                @else
                    <span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full font-medium">Unhealthy</span>
                @endif
            </div>
            <div class="flex items-center gap-6 text-sm text-gray-500">
                <span>{{ $status['backup_count'] }} backups</span>
                <span>{{ $status['total_size'] }} used</span>
                @if($status['newest_backup'])
                    <span>Last: {{ $status['newest_backup']['date'] }} ({{ $status['newest_backup']['age_hours'] }}h ago)</span>
                @else
                    <span class="text-red-500">No backups found</span>
                @endif
            </div>
        </div>

        {{-- Health failure reason --}}
        @if(!$status['healthy'] && $status['health_message'])
        <div class="px-5 py-3 bg-red-50 border-b border-red-100 text-sm text-red-700">
            {{ $status['health_message'] }}
        </div>
        @endif

        {{-- Backup file list --}}
        @if(count($status['files']) > 0)
        <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
            @foreach($status['files'] as $file)
            <div class="flex items-center justify-between px-5 py-2.5 text-sm">
                <span class="font-mono text-gray-600 text-xs">{{ $file['name'] }}</span>
                <div class="flex items-center gap-6 text-gray-400 text-xs">
                    <span>{{ $file['size_human'] }}</span>
                    <span>{{ $file['date'] }}</span>
                    @if($file['age_hours'] <= 26)
                        <span class="bg-green-100 text-green-600 px-1.5 py-0.5 rounded">fresh</span>
                    @elseif($file['age_hours'] <= 50)
                        <span class="bg-yellow-100 text-yellow-600 px-1.5 py-0.5 rounded">1 day old</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">{{ round($file['age_hours'] / 24) }}d old</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="px-5 py-6 text-center text-sm text-gray-400">
            No backups yet on this disk.
        </div>
        @endif

    </div>
    @empty
    <div class="bg-white rounded-lg shadow px-5 py-10 text-center text-sm text-gray-400">
        Backup status unavailable. Check that SFTP credentials are configured and the backup package is set up correctly.
    </div>
    @endforelse

    {{-- Setup note --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 text-sm text-gray-600 space-y-2">
        <p class="font-semibold text-gray-700">Before backups will work on a VPS:</p>
        <ol class="list-decimal list-inside space-y-1">
            <li>Ensure <code class="bg-white px-1 rounded border">pg_dump</code> is installed and on the server PATH</li>
            <li>Set <code class="bg-white px-1 rounded border">BACKUP_SFTP_*</code> variables in <code class="bg-white px-1 rounded border">.env</code> with Namecheap SFTP credentials</li>
            <li>Test connectivity: <code class="bg-white px-1 rounded border">php artisan backup:run --only-db</code></li>
            <li>Add a cron entry to run the Laravel scheduler: <code class="bg-white px-1 rounded border">* * * * * php /path/to/artisan schedule:run</code></li>
        </ol>
    </div>

</div>
@endsection
