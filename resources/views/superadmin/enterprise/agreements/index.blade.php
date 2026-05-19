@extends('superadmin.layout')

@section('page-title', 'Enterprise Agreements — ' . $tenant->name)

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('superadmin.companies.show', $tenant) }}" class="hover:underline">{{ $tenant->name }}</a>
                <span>/</span>
                <span>Enterprise Agreements</span>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Enterprise Agreements</h1>
        </div>
        <a href="{{ route('superadmin.enterprises.agreements.create', $tenant) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + New Agreement
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-300 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($agreements->isEmpty())
        <p class="px-6 py-10 text-center text-gray-400 text-sm">No agreements yet. Create one to set up enterprise billing.</p>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3 text-left">Plan</th>
                    <th class="px-6 py-3 text-right">Price</th>
                    <th class="px-6 py-3 text-left">Cycle</th>
                    <th class="px-6 py-3 text-left">Period</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($agreements as $ag)
                @php
                    $statusColor = match($ag->status) {
                        'active'     => 'green',
                        'expired'    => 'yellow',
                        'terminated' => 'red',
                        default      => 'gray',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $ag->plan?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-900">₦{{ number_format($ag->negotiated_price, 0) }}</td>
                    <td class="px-6 py-3 text-gray-600 capitalize">{{ $ag->billing_cycle }}</td>
                    <td class="px-6 py-3 text-gray-600">
                        {{ $ag->start_date->format('d M Y') }}
                        @if($ag->end_date) – {{ $ag->end_date->format('d M Y') }} @endif
                    </td>
                    <td class="px-6 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 font-medium">
                            {{ ucfirst($ag->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('superadmin.enterprises.agreements.edit', [$tenant, $ag]) }}"
                           class="text-indigo-600 hover:underline text-xs">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div class="flex gap-3">
        <a href="{{ route('superadmin.enterprises.invoices.index', $tenant) }}"
           class="text-sm text-indigo-600 hover:underline">View Invoices →</a>
    </div>
</div>
@endsection
