@extends('superadmin.layout')

@section('page-title', 'Platform Invoices — ' . $tenant->name)

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('superadmin.companies.show', $tenant) }}" class="hover:underline">{{ $tenant->name }}</a>
                <span>/</span>
                <span>Platform Invoices</span>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Platform Invoices</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('superadmin.enterprises.agreements.index', $tenant) }}"
               class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">
                Agreements
            </a>
            <a href="{{ route('superadmin.enterprises.invoices.create', $tenant) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                + New Invoice
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-300 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-300 text-red-800 text-sm rounded-lg px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($invoices->isEmpty())
        <p class="px-6 py-10 text-center text-gray-400 text-sm">No invoices yet.</p>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Invoice #</th>
                    <th class="px-5 py-3 text-left">Period</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                    <th class="px-5 py-3 text-left">Due</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($invoices as $inv)
                @php $color = \App\Models\PlatformInvoice::STATUS_COLORS[$inv->status] ?? 'gray'; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono font-medium text-gray-900">
                        <a href="{{ route('superadmin.enterprises.invoices.show', [$tenant, $inv]) }}"
                           class="hover:text-indigo-600">{{ $inv->invoice_number }}</a>
                    </td>
                    <td class="px-5 py-3 text-gray-600">
                        {{ $inv->period_start->format('d M') }} – {{ $inv->period_end->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3 text-right font-semibold text-gray-900">₦{{ number_format($inv->amount, 0) }}</td>
                    <td class="px-5 py-3 text-gray-600 {{ $inv->due_date->isPast() && $inv->status !== 'paid' ? 'text-red-600 font-medium' : '' }}">
                        {{ $inv->due_date->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs bg-{{ $color }}-100 text-{{ $color }}-800 font-medium">
                            {{ ucfirst($inv->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right space-x-3">
                        <a href="{{ route('superadmin.enterprises.invoices.show', [$tenant, $inv]) }}"
                           class="text-xs text-indigo-600 hover:underline">View</a>
                        <a href="{{ route('superadmin.enterprises.invoices.pdf', [$tenant, $inv]) }}"
                           class="text-xs text-gray-500 hover:underline">PDF</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
