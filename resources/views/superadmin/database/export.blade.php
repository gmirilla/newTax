@extends('superadmin.layout')

@section('page-title', 'Database Export')

@section('content')
@php
    $totalRows    = array_sum($rowCounts);
    $storageMb    = round($storageBytes / 1024 / 1024, 2);
@endphp

<div class="space-y-6" x-data="{ confirmed: false, typed: '' }">

    {{-- Data sensitivity warning --}}
    <div class="bg-red-50 border border-red-300 rounded-lg p-4 flex gap-3">
        <span class="text-red-500 text-xl flex-shrink-0">⚠</span>
        <div class="text-sm text-red-700">
            <p class="font-semibold">This export contains all tenants' financial and personal data.</p>
            <p class="mt-0.5">Store the downloaded file securely. Delete it immediately after use. Never share it or leave it in an unsecured location.</p>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Tables</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ count($tables) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Rows</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalRows) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Uploaded Files</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $storageMb }} MB</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">

        {{-- Table breakdown --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-5 py-3 border-b">
                <h3 class="text-sm font-semibold text-gray-700">Tables to be exported</h3>
            </div>
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @foreach($tables as $table)
                <div class="flex justify-between items-center px-5 py-2 text-sm">
                    <span class="font-mono text-gray-700">{{ $table }}</span>
                    <span class="text-gray-400 text-xs">{{ number_format($rowCounts[$table] ?? 0) }} rows</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Export action + migration guide --}}
        <div class="space-y-4">

            {{-- Download form --}}
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Download Export</h3>

                <p class="text-xs text-gray-500 mb-4">
                    Type <span class="font-mono font-semibold text-gray-700">EXPORT</span> to confirm you understand the data sensitivity, then click Download.
                </p>

                <input
                    type="text"
                    x-model="typed"
                    placeholder="Type EXPORT to confirm"
                    class="w-full rounded border-gray-300 text-sm mb-3 focus:ring-red-500 focus:border-red-500"
                >

                <form method="POST" action="{{ route('superadmin.database.export.download') }}">
                    @csrf
                    <button
                        type="submit"
                        :disabled="typed !== 'EXPORT'"
                        :class="typed === 'EXPORT'
                            ? 'bg-red-600 hover:bg-red-700 text-white cursor-pointer'
                            : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                        class="w-full px-4 py-2 rounded text-sm font-medium transition-colors"
                    >
                        Download Export ZIP
                    </button>
                </form>

                <p class="text-xs text-gray-400 mt-3">
                    The download may take 10–60 seconds depending on database size. Do not close the tab.
                </p>
            </div>

            {{-- Migration guide --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Migration Instructions</h3>
                <ol class="text-xs text-gray-600 space-y-2 list-decimal list-inside">
                    <li>Set up your new server and configure <code class="bg-white px-1 rounded border">.env</code></li>
                    <li>Run <code class="bg-white px-1 rounded border">php artisan migrate</code> on the new server</li>
                    <li>Copy the downloaded ZIP to the new server</li>
                    <li>Dry-run first:
                        <code class="block bg-white px-2 py-1 rounded border mt-1 font-mono">
                            php artisan db:import backup.zip --dry-run
                        </code>
                    </li>
                    <li>Run the actual import:
                        <code class="block bg-white px-2 py-1 rounded border mt-1 font-mono">
                            php artisan db:import backup.zip --force
                        </code>
                    </li>
                    <li>Run <code class="bg-white px-1 rounded border">php artisan storage:link</code> on the new server</li>
                    <li>Verify, then delete the ZIP from both servers</li>
                </ol>
            </div>

        </div>
    </div>

</div>
@endsection
