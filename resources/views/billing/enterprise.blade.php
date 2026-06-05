@extends('layouts.app')

@section('page-title', 'Billing & Plan')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 rounded-xl p-6 text-white">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-indigo-200 text-sm font-medium uppercase tracking-wider">Enterprise Plan</p>
                <h1 class="text-2xl font-bold mt-1">{{ $tenant->plan?->name ?? 'Enterprise' }}</h1>
                <p class="text-indigo-300 text-sm mt-1">
                    Your subscription is managed directly by our team.
                    Contact <a href="mailto:billing@accounttaxng.com" class="underline text-white">billing@accounttaxng.com</a> for any changes.
                </p>
                <p class="text-indigo-300 text-sm mt-1">
                    Self-hosting and custom module(s) development available.
                </p>
                
            </div>
            <div class="bg-indigo-600 bg-opacity-50 rounded-lg px-4 py-3 text-right">
                <p class="text-indigo-200 text-xs uppercase tracking-wider">Status</p>
                <p class="text-white font-bold text-lg capitalize">{{ $tenant->subscription_status ?? 'Active' }}</p>
                @if($tenant->subscription_expires_at)
                <p class="text-indigo-300 text-xs mt-0.5">
                    {{ $tenant->subscription_expires_at->isFuture() ? 'Renews' : 'Expired' }}
                    {{ $tenant->subscription_expires_at->format('d M Y') }}
                </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Agreement Details --}}
    @if($agreement)
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Agreement Details</h2>
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <dt class="text-xs text-gray-500">Monthly Rate</dt>
                <dd class="text-lg font-bold text-gray-900">₦{{ number_format($agreement->negotiated_price, 0) }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Billing Cycle</dt>
                <dd class="text-sm font-semibold text-gray-800 capitalize">{{ $agreement->billing_cycle }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Payment Terms</dt>
                <dd class="text-sm font-semibold text-gray-800">{{ $agreement->payment_terms_days }} days</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Agreement Start</dt>
                <dd class="text-sm font-semibold text-gray-800">{{ $agreement->start_date->format('d M Y') }}</dd>
            </div>
        </dl>
        @if($agreement->end_date)
        <p class="mt-3 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded px-3 py-2">
            This agreement expires on {{ $agreement->end_date->format('d M Y') }}.
            Contact us to renegotiate before expiry.
        </p>
        @endif
    </div>
    @endif

    {{-- Invoice History --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Invoice History</h2>
        </div>
        @if($platformInvoices->isEmpty())
        <p class="px-6 py-10 text-center text-gray-400 text-sm">No invoices yet.</p>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3 text-left">Invoice #</th>
                    <th class="px-6 py-3 text-left">Period</th>
                    <th class="px-6 py-3 text-right">Amount</th>
                    <th class="px-6 py-3 text-left">Due</th>
                    <th class="px-6 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($platformInvoices as $inv)
                @php $color = \App\Models\PlatformInvoice::STATUS_COLORS[$inv->status] ?? 'gray'; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-mono font-medium text-gray-900">{{ $inv->invoice_number }}</td>
                    <td class="px-6 py-3 text-gray-600">
                        {{ $inv->period_start->format('d M') }} – {{ $inv->period_end->format('d M Y') }}
                    </td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-900">₦{{ number_format($inv->amount, 0) }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ $inv->due_date->format('d M Y') }}</td>
                    <td class="px-6 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs bg-{{ $color }}-100 text-{{ $color }}-800 font-medium">
                            {{ ucfirst($inv->status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <p class="text-xs text-gray-400 text-center">
        For billing enquiries or contract renegotiation, contact
        <a href="mailto:billing@accounttaxng.com" class="text-indigo-600 hover:underline">billing@accounttaxng.com</a>.
    </p>
</div>
@endsection
