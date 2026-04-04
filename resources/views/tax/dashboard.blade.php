@extends('layouts.app')

@section('page-title', 'Tax Compliance Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Compliance Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- VAT Card --}}
        <div class="bg-white rounded-lg shadow p-6 border-t-4
            {{ $pending['overdue_vat_returns'] > 0 ? 'border-red-500' : 'border-green-500' }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">📋 VAT (Value Added Tax)</h3>
                <span class="text-sm font-bold text-blue-700">7.5%</span>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">This Month Output</dt>
                    <dd class="font-medium">₦{{ number_format($compliance['vat']['ytd_summary']['ytd_output_vat'] ?? 0, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">This Month Input</dt>
                    <dd class="font-medium">₦{{ number_format($compliance['vat']['ytd_summary']['ytd_input_vat'] ?? 0, 2) }}</dd>
                </div>
                <div class="flex justify-between border-t pt-2">
                    <dt class="font-semibold">Net VAT Payable</dt>
                    <dd class="font-bold text-green-700">
                        ₦{{ number_format($compliance['vat']['ytd_summary']['ytd_net_vat'] ?? 0, 2) }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Next Due</dt>
                    <dd class="font-medium text-orange-600">{{ $compliance['vat']['next_due'] }}</dd>
                </div>
            </dl>

            @if($pending['overdue_vat_returns'] > 0)
            <div class="mt-3 p-2 bg-red-50 rounded text-xs text-red-700">
                ⚠️ {{ $pending['overdue_vat_returns'] }} overdue return(s). File immediately!
            </div>
            @endif

            <div class="mt-4 flex gap-2">
                <a href="{{ route('tax.vat.compute') }}"
                   class="flex-1 text-center py-1.5 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                    Compute Return
                </a>
                <a href="{{ route('tax.vat.index') }}"
                   class="flex-1 text-center py-1.5 border border-gray-300 text-xs rounded hover:bg-gray-50">
                    View History
                </a>
            </div>
        </div>

        {{-- WHT Card --}}
        <div class="bg-white rounded-lg shadow p-6 border-t-4 border-blue-500">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">🔖 WHT (Withholding Tax)</h3>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Services (Company)</dt>
                    <dd class="font-medium">5%</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Services (Individual)</dt>
                    <dd class="font-medium">10%</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Rent / Dividends</dt>
                    <dd class="font-medium">10%</dd>
                </div>
                <div class="flex justify-between border-t pt-2">
                    <dt class="font-semibold">Pending Remittance</dt>
                    <dd class="font-bold text-orange-600">
                        ₦{{ number_format($pending['pending_wht_amount'], 2) }}
                    </dd>
                </div>
            </dl>

            <div class="mt-4">
                <a href="{{ route('tax.wht.index') }}"
                   class="block text-center py-1.5 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                    View WHT Schedule
                </a>
            </div>
        </div>

        {{-- CIT Card --}}
        <div class="bg-white rounded-lg shadow p-6 border-t-4
            {{ $compliance['cit']['summary']['is_exempt'] ? 'border-green-500' : 'border-orange-500' }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">🏢 CIT (Company Income Tax)</h3>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Company Size</dt>
                    <dd class="font-medium capitalize">{{ $compliance['cit']['summary']['company_size'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">CIT Rate</dt>
                    <dd class="font-bold {{ $compliance['cit']['summary']['is_exempt'] ? 'text-green-600' : 'text-orange-600' }}">
                        {{ $compliance['cit']['summary']['cit_rate'] }}%
                        @if($compliance['cit']['summary']['is_exempt'])
                            <span class="text-green-600 font-normal">(Exempt)</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Education Tax</dt>
                    <dd class="font-medium">2.5% of profit</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Annual Filing Due</dt>
                    <dd class="font-medium">{{ $compliance['cit']['summary']['next_due_date'] }}</dd>
                </div>
            </dl>

            @if($compliance['cit']['summary']['is_exempt'])
            <div class="mt-3 p-2 bg-green-50 rounded text-xs text-green-700">
                ✅ Small company (≤₦25M) – 0% CIT rate. Annual filing still required by FIRS.
            </div>
            @endif

            <div class="mt-4">
                <a href="{{ route('tax.cit.compute') }}"
                   class="block text-center py-1.5 bg-orange-600 text-white text-xs rounded hover:bg-orange-700">
                    Compute CIT
                </a>
            </div>
        </div>
    </div>

    {{-- Nigerian Tax Reference Table --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-gray-900 mb-4">🇳🇬 Nigerian Tax Reference Guide</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-green-50 border-b">
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Tax Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Rate</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Filing Deadline</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Authority</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-4 py-3 font-medium">VAT</td>
                        <td class="px-4 py-3 text-blue-700 font-bold">7.5%</td>
                        <td class="px-4 py-3">21st of following month</td>
                        <td class="px-4 py-3">FIRS</td>
                        <td class="px-4 py-3 text-gray-500">Mandatory if turnover > ₦25M</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 font-medium">WHT (Services/Company)</td>
                        <td class="px-4 py-3 text-blue-700 font-bold">5%</td>
                        <td class="px-4 py-3">Monthly</td>
                        <td class="px-4 py-3">FIRS</td>
                        <td class="px-4 py-3 text-gray-500">Deducted at source by payer</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">WHT (Services/Individual)</td>
                        <td class="px-4 py-3 text-blue-700 font-bold">10%</td>
                        <td class="px-4 py-3">Monthly</td>
                        <td class="px-4 py-3">FIRS</td>
                        <td class="px-4 py-3 text-gray-500">Individuals incl. freelancers</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 font-medium">WHT (Rent/Dividends)</td>
                        <td class="px-4 py-3 text-blue-700 font-bold">10%</td>
                        <td class="px-4 py-3">Monthly</td>
                        <td class="px-4 py-3">FIRS</td>
                        <td class="px-4 py-3 text-gray-500">Also applies to interest</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">CIT (Small ≤₦25M)</td>
                        <td class="px-4 py-3 text-green-700 font-bold">0%</td>
                        <td class="px-4 py-3">6 months after year-end</td>
                        <td class="px-4 py-3">FIRS</td>
                        <td class="px-4 py-3 text-gray-500">Filing still required</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 font-medium">CIT (Medium ₦25M–₦100M)</td>
                        <td class="px-4 py-3 text-orange-700 font-bold">20%</td>
                        <td class="px-4 py-3">6 months after year-end</td>
                        <td class="px-4 py-3">FIRS</td>
                        <td class="px-4 py-3 text-gray-500">+ 2.5% Education Tax</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">CIT (Large >₦100M)</td>
                        <td class="px-4 py-3 text-red-700 font-bold">30%</td>
                        <td class="px-4 py-3">6 months after year-end</td>
                        <td class="px-4 py-3">FIRS</td>
                        <td class="px-4 py-3 text-gray-500">+ 2.5% Education Tax</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 font-medium">PAYE (Income Tax)</td>
                        <td class="px-4 py-3 text-blue-700 font-bold">7% – 24%</td>
                        <td class="px-4 py-3">Monthly (10th)</td>
                        <td class="px-4 py-3">State IRS</td>
                        <td class="px-4 py-3 text-gray-500">Progressive bands on taxable income</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
