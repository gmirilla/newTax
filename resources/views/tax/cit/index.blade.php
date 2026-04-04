@extends('layouts.app')

@section('page-title', 'Company Income Tax')

@section('content')
<div class="space-y-6">

    {{-- Company Status --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Category</p>
            <p class="text-lg font-bold text-gray-800">{{ ucfirst($citStatus['company_size']) }}</p>
            <p class="text-xs text-gray-400">Company</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">CIT Rate</p>
            <p class="text-2xl font-bold {{ $citStatus['cit_rate'] == 0 ? 'text-green-700' : 'text-orange-700' }}">
                {{ $citStatus['cit_rate'] }}%
            </p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Annual Turnover</p>
            <p class="text-lg font-bold text-gray-800">₦{{ number_format($citStatus['annual_turnover'], 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Next Filing Due</p>
            <p class="text-sm font-bold {{ $citStatus['days_to_deadline'] <= 30 ? 'text-red-600' : 'text-gray-800' }}">
                {{ $citStatus['next_deadline'] }}
            </p>
            @if($citStatus['days_to_deadline'] <= 30)
                <p class="text-xs text-red-500">{{ $citStatus['days_to_deadline'] }} days</p>
            @endif
        </div>
    </div>

    {{-- 2026 CIT Rate Bands --}}
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold">CIT Rate Bands — Finance Act 2025 (2026)</h3>
            <span class="text-xs text-gray-400">Development Levy: 4% of assessable profit (non-small companies)</span>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="rounded-lg border-2 p-4 {{ $citStatus['company_size'] === 'small' ? 'border-green-400 bg-green-50' : 'border-gray-200' }}">
                <p class="font-semibold text-green-700">Small Company</p>
                <p class="text-gray-600 mt-1">Turnover ≤ ₦50,000,000</p>
                <p class="text-3xl font-bold text-green-700 mt-2">0%</p>
                <p class="text-xs text-gray-500 mt-1">Exempt from CIT & Development Levy</p>
                <p class="text-xs text-red-500 mt-1">★ Professional firms excluded (always 30%)</p>
                @if($citStatus['company_size'] === 'small')
                    <span class="mt-2 inline-block text-xs bg-green-200 text-green-800 px-2 py-0.5 rounded-full">Your Category</span>
                @endif
            </div>
            <div class="rounded-lg border-2 p-4 {{ $citStatus['company_size'] === 'large' ? 'border-orange-400 bg-orange-50' : 'border-gray-200' }}">
                <p class="font-semibold text-orange-700">Large Company</p>
                <p class="text-gray-600 mt-1">Turnover > ₦50,000,000 (or professional firm)</p>
                <p class="text-3xl font-bold text-orange-700 mt-2">30%</p>
                <p class="text-xs text-gray-500 mt-1">On assessable profit + 4% Development Levy</p>
                @if($citStatus['company_size'] === 'large')
                    <span class="mt-2 inline-block text-xs bg-orange-200 text-orange-800 px-2 py-0.5 rounded-full">Your Category</span>
                @endif
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3">
            Minimum Tax: 0.5% of gross turnover or ₦200,000 (whichever is higher) — applies when company is in a loss or computed CIT is lower.
            Development Levy replaces TETFund, IT Levy, NASENI and Police Trust Fund contributions.
        </p>
    </div>

    {{-- CIT Records --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-sm font-semibold">CIT Filing History</h3>
            <a href="{{ route('tax.cit.compute') }}" class="px-4 py-1.5 bg-orange-600 text-white text-sm rounded-md hover:bg-orange-700">
                Compute CIT
            </a>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tax Year</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Turnover</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Assessable Profit</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">CIT Payable</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Dev. Levy</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Total</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Status</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Filed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($citRecords as $record)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium">{{ $record->tax_year }}</td>
                    <td class="px-4 py-2 text-right">₦{{ number_format($record->gross_turnover ?? $record->annual_turnover, 0) }}</td>
                    <td class="px-4 py-2 text-right">₦{{ number_format($record->assessable_profit ?? $record->taxable_profit, 0) }}</td>
                    <td class="px-4 py-2 text-right text-orange-700 font-medium">₦{{ number_format($record->cit_payable ?? $record->cit_amount, 2) }}</td>
                    <td class="px-4 py-2 text-right text-purple-700">₦{{ number_format($record->development_levy ?? $record->education_levy ?? 0, 2) }}</td>
                    <td class="px-4 py-2 text-right font-semibold">₦{{ number_format($record->total_tax_liability ?? $record->total_tax_due, 2) }}</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 text-xs rounded-full
                            {{ $record->status === 'filed' ? 'bg-green-100 text-green-700' :
                               ($record->status === 'exempt' ? 'bg-blue-100 text-blue-700' :
                               ($record->status === 'assessed' ? 'bg-indigo-100 text-indigo-700' : 'bg-yellow-100 text-yellow-700')) }}">
                            {{ ucfirst($record->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-center text-xs text-gray-500">
                        {{ $record->filed_date ? $record->filed_date->format('d M Y') : '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-6 text-center text-gray-400">No CIT records yet. Use "Compute CIT" to generate your first computation.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="text-xs text-gray-400 text-center">
        Companies Income Tax Act (CITA) as amended by Finance Acts 2019–2025.
        Annual returns and payment due within 6 months of financial year-end. Late filing: ₦25,000 + ₦5,000/day.
    </div>
</div>
@endsection
