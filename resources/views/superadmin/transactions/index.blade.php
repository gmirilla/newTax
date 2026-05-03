@extends('superadmin.layout')

@section('page-title', 'Subscription Transactions')

@section('content')
<div class="space-y-4">

    {{-- Summary stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₦{{ number_format($stats['total_revenue'], 0) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">all successful payments</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">This Month</p>
            <p class="text-2xl font-bold text-green-700 mt-1">₦{{ number_format($stats['revenue_this_month'], 0) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ now()->format('F Y') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Successful</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['success_count']) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">of {{ number_format($stats['total_count']) }} total</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Failed</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($stats['failed_count']) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">transactions</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Company</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Name or email…"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 w-52">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Plan</label>
                <select name="plan" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" {{ request('plan') == $plan->id ? 'selected' : '' }}>
                        {{ $plan->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All</option>
                    <option value="success"  {{ request('status') === 'success'  ? 'selected' : '' }}>Success</option>
                    <option value="failed"   {{ request('status') === 'failed'   ? 'selected' : '' }}>Failed</option>
                    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Cycle</label>
                <select name="cycle" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All</option>
                    <option value="monthly" {{ request('cycle') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="yearly"  {{ request('cycle') === 'yearly'  ? 'selected' : '' }}>Annual</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-1.5 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                    Filter
                </button>
                @if(request()->hasAny(['search','plan','status','cycle','date_from','date_to']))
                <a href="{{ route('superadmin.transactions') }}"
                   class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 border border-gray-300 rounded-md">
                    Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-3 border-b flex items-center justify-between flex-wrap gap-2">
            <p class="text-sm font-semibold text-gray-700">
                {{ $payments->total() }} {{ Str::plural('transaction', $payments->total()) }}
                @if(request()->hasAny(['search','plan','status','cycle','date_from','date_to']))
                <span class="text-gray-400 font-normal">(filtered)</span>
                @endif
            </p>
            <div class="flex items-center gap-2">
                <a href="{{ route('superadmin.transactions.export.excel') }}?{{ http_build_query(request()->only(['search','plan','status','cycle','date_from','date_to'])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v6m0 0l-3-3m3 3l3-3M12 3v9"/>
                    </svg>
                    Excel
                </a>
                <a href="{{ route('superadmin.transactions.export.pdf') }}?{{ http_build_query(request()->only(['search','plan','status','cycle','date_from','date_to'])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    PDF
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Company</th>
                        <th class="px-5 py-3 text-left font-semibold">Plan</th>
                        <th class="px-5 py-3 text-left font-semibold">Type</th>
                        <th class="px-5 py-3 text-center font-semibold">Cycle</th>
                        <th class="px-5 py-3 text-right font-semibold">Amount</th>
                        <th class="px-5 py-3 text-center font-semibold">Status</th>
                        <th class="px-5 py-3 text-left font-semibold">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3">
                            <a href="{{ route('superadmin.companies.show', $payment->tenant) }}"
                               class="font-medium text-gray-900 hover:text-indigo-600">
                                {{ $payment->tenant?->name ?? '—' }}
                            </a>
                            <p class="text-xs text-gray-400">{{ $payment->tenant?->email ?? '' }}</p>
                        </td>
                        <td class="px-5 py-3 text-gray-700">{{ $payment->plan?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $payment->typeLabel() }}</td>
                        <td class="px-5 py-3 text-center">
                            @if(($payment->billing_cycle ?? 'monthly') === 'yearly')
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">Annual</span>
                            @else
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Monthly</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900">
                            ₦{{ number_format($payment->amount, 2) }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            @php
                                $colour = match($payment->status) {
                                    'success'  => 'bg-green-100 text-green-700',
                                    'failed'   => 'bg-red-100 text-red-600',
                                    'pending'  => 'bg-yellow-100 text-yellow-700',
                                    'refunded' => 'bg-purple-100 text-purple-700',
                                    default    => 'bg-gray-100 text-gray-500',
                                };
                            @endphp
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $colour }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500 whitespace-nowrap">
                            {{ $payment->paid_at?->format('d M Y') ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400">
                            No transactions found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
        <div class="px-5 py-3 border-t bg-gray-50">
            {{ $payments->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
