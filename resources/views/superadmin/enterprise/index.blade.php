@extends('superadmin.layout')

@section('page-title', 'Enterprise Billing')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Enterprise Billing</h1>
            <p class="text-sm text-gray-500 mt-0.5">Managed plans, negotiated pricing and platform invoices</p>
        </div>
    </div>

    @if($totalOverdue > 0)
    <div class="bg-red-50 border border-red-300 rounded-lg px-5 py-4 flex items-center gap-3">
        <span class="text-2xl">⚠️</span>
        <div>
            <p class="font-semibold text-red-800">{{ $totalOverdue }} overdue invoice{{ $totalOverdue !== 1 ? 's' : '' }}</p>
            <p class="text-sm text-red-700 mt-0.5">Review the companies below and record payment or follow up.</p>
        </div>
    </div>
    @endif

    @if($enterpriseTenants->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-10 text-center">
        <p class="text-gray-400 text-sm">No enterprise customers yet.</p>
        <p class="text-xs text-gray-400 mt-1">
            To set up a company on an enterprise plan, go to
            <a href="{{ route('superadmin.companies') }}" class="text-indigo-600 hover:underline">Companies</a>,
            open a company and click "Set Enterprise".
        </p>
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Company</th>
                    <th class="px-5 py-3 text-left">Plan</th>
                    <th class="px-5 py-3 text-right">Rate</th>
                    <th class="px-5 py-3 text-left">Cycle</th>
                    <th class="px-5 py-3 text-right">Invoices</th>
                    <th class="px-5 py-3 text-right">Overdue</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($enterpriseTenants as $t)
                @php $activeAgreement = $t->enterpriseAgreements->first(); @endphp
                <tr class="hover:bg-gray-50 {{ $t->overdue_invoices_count > 0 ? 'bg-red-50' : '' }}">
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-900">{{ $t->name }}</div>
                        <div class="text-xs text-gray-400">{{ $t->email }}</div>
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $t->plan?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-gray-900">
                        @if($activeAgreement)
                        ₦{{ number_format($activeAgreement->negotiated_price, 0) }}
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600 capitalize">
                        {{ $activeAgreement?->billing_cycle ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ $t->platform_invoices_count }}</td>
                    <td class="px-5 py-3 text-right">
                        @if($t->overdue_invoices_count > 0)
                        <span class="text-red-600 font-semibold">{{ $t->overdue_invoices_count }}</span>
                        @else
                        <span class="text-gray-400">0</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right space-x-3">
                        <a href="{{ route('superadmin.enterprises.invoices.index', $t) }}"
                           class="text-xs text-indigo-600 hover:underline">Invoices</a>
                        <a href="{{ route('superadmin.enterprises.agreements.index', $t) }}"
                           class="text-xs text-gray-500 hover:underline">Agreements</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <p class="text-xs text-gray-400">
        To add a new enterprise customer, go to
        <a href="{{ route('superadmin.companies') }}" class="text-indigo-600 hover:underline">Companies</a>,
        open the company and click "Set Enterprise".
    </p>
</div>
@endsection
