@extends('layouts.app')

@section('page-title', 'Tax Compliance Summary')

@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex gap-3 items-center">
            <select name="year" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                @for($y = now()->year; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">View</button>
        </form>
    </div>

    {{-- Overall Compliance Score --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Compliance Score</h2>
                <p class="text-sm text-gray-500">Based on filings, payments, and registration status for {{ $year }}</p>
            </div>
            <div class="text-center">
                <div class="text-5xl font-bold {{ $summary['compliance_score'] >= 80 ? 'text-green-600' : ($summary['compliance_score'] >= 50 ? 'text-yellow-500' : 'text-red-600') }}">
                    {{ $summary['compliance_score'] }}%
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ $summary['compliance_score'] >= 80 ? 'Good Standing' : ($summary['compliance_score'] >= 50 ? 'Needs Attention' : 'Non-Compliant') }}</p>
            </div>
        </div>
    </div>

    {{-- Tax Modules Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- VAT Summary --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-sm font-semibold">VAT (Value Added Tax)</h3>
                <span class="px-2 py-0.5 text-xs rounded-full {{ $summary['vat']['registered'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $summary['vat']['registered'] ? 'Registered' : 'Below Threshold' }}
                </span>
            </div>
            <div class="p-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Rate</span>
                    <span class="font-medium">7.5%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Output VAT ({{ $year }})</span>
                    <span class="font-medium text-blue-700">₦{{ number_format($summary['vat']['output_total'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Input VAT ({{ $year }})</span>
                    <span class="font-medium text-green-700">₦{{ number_format($summary['vat']['input_total'], 2) }}</span>
                </div>
                <div class="flex justify-between border-t pt-2 font-semibold">
                    <span>Net VAT Payable</span>
                    <span class="{{ $summary['vat']['net'] >= 0 ? 'text-orange-700' : 'text-green-700' }}">
                        ₦{{ number_format(abs($summary['vat']['net']), 2) }}
                        {{ $summary['vat']['net'] < 0 ? '(Credit)' : '' }}
                    </span>
                </div>
                <div class="flex justify-between text-xs text-gray-400">
                    <span>Monthly returns filed</span>
                    <span>{{ $summary['vat']['returns_filed'] }} / {{ $summary['vat']['returns_due'] }}</span>
                </div>
                <div class="text-xs text-gray-400">Filing deadline: 21st of each following month</div>
            </div>
        </div>

        {{-- WHT Summary --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-sm font-semibold">WHT (Withholding Tax)</h3>
            </div>
            <div class="p-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Company Services Rate</span>
                    <span class="font-medium">5%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Individual Services Rate</span>
                    <span class="font-medium">10%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total WHT Deducted</span>
                    <span class="font-medium text-purple-700">₦{{ number_format($summary['wht']['total_deducted'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">WHT Remitted</span>
                    <span class="font-medium text-green-700">₦{{ number_format($summary['wht']['total_remitted'], 2) }}</span>
                </div>
                <div class="flex justify-between border-t pt-2">
                    <span class="text-gray-500">Outstanding WHT</span>
                    <span class="{{ $summary['wht']['outstanding'] > 0 ? 'text-red-600 font-semibold' : 'text-green-700' }}">
                        ₦{{ number_format($summary['wht']['outstanding'], 2) }}
                    </span>
                </div>
                <div class="text-xs text-gray-400">Remittance due: 21st of following month</div>
            </div>
        </div>

        {{-- CIT Summary --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-sm font-semibold">CIT (Company Income Tax)</h3>
                <span class="px-2 py-0.5 text-xs rounded-full bg-orange-100 text-orange-700">
                    {{ ucfirst($summary['cit']['company_size']) }} ({{ $summary['cit']['rate'] }}%)
                </span>
            </div>
            <div class="p-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Assessable Profit</span>
                    <span class="font-medium">₦{{ number_format($summary['cit']['assessable_profit'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">CIT Payable</span>
                    <span class="font-medium text-orange-700">₦{{ number_format($summary['cit']['cit_payable'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Development Levy (4%)</span>
                    <span class="font-medium text-purple-700">₦{{ number_format($summary['cit']['education_tax'], 2) }}</span>
                </div>
                <div class="flex justify-between border-t pt-2 font-semibold">
                    <span>Total CIT Liability</span>
                    <span class="text-orange-700">₦{{ number_format($summary['cit']['total_liability'], 2) }}</span>
                </div>
                <div class="text-xs text-gray-400">Filing deadline: {{ $summary['cit']['filing_deadline'] }}</div>
            </div>
        </div>

        {{-- PAYE Summary --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-sm font-semibold">PAYE (Pay As You Earn)</h3>
            </div>
            <div class="p-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Active Employees</span>
                    <span class="font-medium">{{ $summary['paye']['employee_count'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Payroll ({{ $year }})</span>
                    <span class="font-medium">₦{{ number_format($summary['paye']['total_payroll'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total PAYE Deducted</span>
                    <span class="font-medium text-blue-700">₦{{ number_format($summary['paye']['total_paye'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Pension (Employee)</span>
                    <span class="font-medium">₦{{ number_format($summary['paye']['total_pension_employee'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total NHF</span>
                    <span class="font-medium">₦{{ number_format($summary['paye']['total_nhf'], 2) }}</span>
                </div>
                <div class="text-xs text-gray-400">Remittance to State IRS: 10th of following month</div>
            </div>
        </div>
    </div>

    {{-- Action Items --}}
    @if(count($summary['action_items']) > 0)
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-sm font-semibold text-red-600">Action Required</h3>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach($summary['action_items'] as $item)
            <li class="px-6 py-3 flex items-start gap-3">
                <span class="mt-0.5 text-red-500">⚠</span>
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $item['title'] }}</p>
                    <p class="text-xs text-gray-500">{{ $item['description'] }}</p>
                </div>
                @if(isset($item['due_date']))
                <span class="ml-auto text-xs text-red-600 font-medium whitespace-nowrap">Due: {{ $item['due_date'] }}</span>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @else
    <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 flex items-center gap-2">
        <span>✓</span> All tax obligations are up to date for {{ $year }}.
    </div>
    @endif

    <div class="text-xs text-gray-400 text-center">
        Compliance summary covers VAT (Finance Act 2019), WHT (CITA), CIT (CITA Cap C21), PAYE (PITA Cap P8), Pension (PRA 2014), NHF Act.
    </div>
</div>
@endsection
