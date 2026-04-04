@extends('layouts.app')

@section('page-title', 'CIT Report')

@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex gap-3 items-center">
            <select name="year" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                @for($y = now()->year; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">View Report</button>
        </form>
    </div>

    {{-- Company Category Badge --}}
    <div class="bg-white rounded-lg shadow p-4 flex items-center gap-4">
        <div>
            <p class="text-xs text-gray-500 uppercase font-medium">Company Category</p>
            <p class="text-lg font-bold text-gray-800">{{ ucfirst($report['company_size']) }} Company</p>
        </div>
        <div class="border-l pl-4">
            <p class="text-xs text-gray-500 uppercase font-medium">Gross Turnover</p>
            <p class="text-lg font-bold text-gray-800">₦{{ number_format($report['gross_turnover'], 2) }}</p>
        </div>
        <div class="border-l pl-4">
            <p class="text-xs text-gray-500 uppercase font-medium">CIT Rate</p>
            <p class="text-lg font-bold {{ $report['cit_rate'] == 0 ? 'text-green-700' : 'text-orange-700' }}">{{ $report['cit_rate'] }}%</p>
        </div>
        <div class="border-l pl-4">
            <p class="text-xs text-gray-500 uppercase font-medium">Filing Deadline</p>
            <p class="text-sm font-medium text-gray-700">{{ $report['filing_deadline'] }}</p>
        </div>
    </div>

    {{-- CIT Computation Table --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-sm font-semibold">CIT Computation — {{ $year }}</h3>
        </div>
        <div class="p-6">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-gray-600">Gross Turnover</td>
                        <td class="py-2 text-right font-medium">₦{{ number_format($report['gross_turnover'], 2) }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-gray-600">Total Revenue</td>
                        <td class="py-2 text-right font-medium text-green-700">₦{{ number_format($report['total_revenue'], 2) }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-gray-600">Total Allowable Expenses</td>
                        <td class="py-2 text-right font-medium text-red-600">(₦{{ number_format($report['total_expenses'], 2) }})</td>
                    </tr>
                    <tr class="bg-gray-50 font-semibold">
                        <td class="py-2 pl-2">Assessable Profit / (Loss)</td>
                        <td class="py-2 text-right {{ $report['assessable_profit'] >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                            ₦{{ number_format($report['assessable_profit'], 2) }}
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-gray-600">CIT Rate</td>
                        <td class="py-2 text-right">{{ $report['cit_rate'] }}%</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-gray-600">Tax on Assessable Profit</td>
                        <td class="py-2 text-right font-medium">₦{{ number_format($report['tax_on_profit'], 2) }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-gray-500 text-xs">Minimum Tax (0.5% of turnover or ₦200,000 minimum)</td>
                        <td class="py-2 text-right text-xs text-gray-500">₦{{ number_format($report['minimum_tax'], 2) }}</td>
                    </tr>
                    <tr class="bg-orange-50 font-bold border-t-2 border-orange-200">
                        <td class="py-3 pl-2 text-orange-800">CIT Payable (higher of above)</td>
                        <td class="py-3 text-right text-orange-700 text-lg">₦{{ number_format($report['cit_payable'], 2) }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 text-gray-600">Education Tax (2.5% of assessable profit)</td>
                        <td class="py-2 text-right font-medium text-purple-700">₦{{ number_format($report['education_tax'], 2) }}</td>
                    </tr>
                    <tr class="bg-purple-50 font-bold border-t">
                        <td class="py-3 pl-2 text-purple-800">Total Tax Liability</td>
                        <td class="py-3 text-right text-purple-700 text-lg">₦{{ number_format($report['total_tax_liability'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if($report['company_size'] === 'small')
    <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
        <strong>Small Company Exemption:</strong> Companies with annual turnover of ₦25 million or less are exempt from Company Income Tax (0% rate) per the Finance Act 2019. However, Education Tax at 2.5% of assessable profit still applies.
    </div>
    @endif

    @if($report['minimum_tax_applies'])
    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-700">
        <strong>Minimum Tax Applies:</strong> Because the company reported a loss or the computed CIT is lower than the minimum tax, the minimum tax of 0.5% of gross turnover (or ₦200,000 whichever is higher) is payable.
    </div>
    @endif

    <div class="p-3 bg-gray-50 border rounded text-xs text-gray-500">
        CIT computed under the Companies Income Tax Act (CITA) Cap C21 LFN 2004 as amended by Finance Acts 2019–2023. Education Tax under Tertiary Education Trust Fund (Establishment Act) 2011.
        Filing deadline: 6 months after financial year-end. Due: <strong>{{ $report['filing_deadline'] }}</strong>.
    </div>
</div>
@endsection
