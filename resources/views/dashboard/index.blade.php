@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Compliance Score Banner --}}
    <div class="bg-white rounded-lg shadow p-6 border-l-4
        {{ $dashboard['compliance_score'] >= 80 ? 'border-green-500' : ($dashboard['compliance_score'] >= 60 ? 'border-yellow-500' : 'border-red-500') }}">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Tax Compliance Score</h2>
                <p class="text-gray-500 text-sm">Based on your filing status and VAT registration</p>
            </div>
            <div class="text-right">
                <span class="text-5xl font-bold
                    {{ $dashboard['compliance_score'] >= 80 ? 'text-green-600' : ($dashboard['compliance_score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $dashboard['compliance_score'] }}
                </span>
                <span class="text-gray-400 text-xl">/100</span>
            </div>
        </div>

        @if(($dashboard['vat']['overdue_count'] ?? 0) > 0)
        <div class="mt-3 p-3 bg-red-50 rounded-md">
            <p class="text-sm text-red-700">
                ⚠️ <strong>{{ $dashboard['vat']['overdue_count'] }} overdue VAT return(s)!</strong>
                NRS penalties apply after the 21st. File immediately to avoid sanctions.
            </p>
        </div>
        @endif
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Revenue --}}
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md bg-green-100 p-3">
                    <span class="text-2xl">💵</span>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">YTD Revenue</p>
                    <p class="text-xl font-bold text-gray-900">
                        ₦{{ number_format($dashboard['invoices']['total_revenue'], 2) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Outstanding --}}
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md bg-yellow-100 p-3">
                    <span class="text-2xl">⏳</span>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Outstanding Invoices</p>
                    <p class="text-xl font-bold text-gray-900">
                        ₦{{ number_format($dashboard['invoices']['outstanding'], 2) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- VAT Due --}}
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md bg-blue-100 p-3">
                    <span class="text-2xl">🏛️</span>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Net VAT (YTD)</p>
                    <p class="text-xl font-bold text-gray-900">
                        ₦{{ number_format($dashboard['vat']['ytd_summary']['ytd_net_vat'], 2) }}
                    </p>
                    <p class="text-xs text-gray-400">Next due: {{ $dashboard['vat']['next_due'] }}</p>
                </div>
            </div>
        </div>

        {{-- WHT Pending --}}
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md bg-purple-100 p-3">
                    <span class="text-2xl">🔖</span>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">WHT to Remit</p>
                    <p class="text-xl font-bold text-gray-900">
                        ₦{{ number_format($dashboard['wht']['pending_remittance'], 2) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tax Status Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- VAT Status --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">📋 VAT Status</h3>
            <dl class="space-y-3">
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">VAT Rate</dt>
                    <dd class="font-semibold text-gray-900">7.5%</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Registered</dt>
                    <dd>
                        @if($dashboard['vat']['registered'])
                            <span class="text-green-600 font-medium">✅ Yes</span>
                        @else
                            <span class="text-red-600 font-medium">❌ No</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Output VAT (YTD)</dt>
                    <dd class="font-medium">₦{{ number_format($dashboard['vat']['ytd_summary']['ytd_output_vat'], 2) }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Input VAT (YTD)</dt>
                    <dd class="font-medium">₦{{ number_format($dashboard['vat']['ytd_summary']['ytd_input_vat'], 2) }}</dd>
                </div>
                <div class="flex justify-between text-sm border-t pt-2">
                    <dt class="font-semibold">Net VAT Payable</dt>
                    <dd class="font-bold text-green-700">₦{{ number_format($dashboard['vat']['ytd_summary']['ytd_net_vat'], 2) }}</dd>
                </div>
            </dl>
            <a href="{{ route('tax.vat.index') }}" class="mt-4 block text-center text-sm text-green-600 hover:text-green-800 font-medium">
                View VAT Returns →
            </a>
        </div>

        {{-- CIT Status --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">🏢 CIT Status</h3>
            <dl class="space-y-3">
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Company Size</dt>
                    <dd class="font-semibold capitalize">{{ $dashboard['cit']['summary']['company_size'] }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">CIT Rate</dt>
                    <dd class="font-semibold">
                        {{ $dashboard['cit']['summary']['cit_rate'] }}%
                        @if($dashboard['cit']['summary']['is_exempt'])
                            <span class="text-green-600">(Exempt)</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Last Filed Year</dt>
                    <dd>{{ $dashboard['cit']['summary']['last_filed_year'] ?? 'Not filed' }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Next Due Date</dt>
                    <dd>{{ $dashboard['cit']['summary']['next_due_date'] }}</dd>
                </div>
            </dl>
            @if($dashboard['cit']['summary']['is_exempt'])
            <div class="mt-3 p-2 bg-green-50 rounded text-xs text-green-700">
                ✅ Small company (≤₦25M turnover) – 0% CIT. Filing still required.
            </div>
            @endif
            <a href="{{ route('tax.cit.index') }}" class="mt-4 block text-center text-sm text-green-600 hover:text-green-800 font-medium">
                Compute CIT →
            </a>
        </div>

        {{-- Payroll Summary --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">👥 Payroll (YTD)</h3>
            <dl class="space-y-3">
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Active Employees</dt>
                    <dd class="font-semibold">{{ $dashboard['payroll']['employee_count'] }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Months Processed</dt>
                    <dd>{{ $dashboard['payroll']['months_run'] }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Total Gross</dt>
                    <dd>₦{{ number_format($dashboard['payroll']['total_gross'], 2) }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Total PAYE Deducted</dt>
                    <dd class="font-medium text-orange-600">₦{{ number_format($dashboard['payroll']['total_paye'], 2) }}</dd>
                </div>
                <div class="flex justify-between text-sm border-t pt-2">
                    <dt class="font-semibold">Total Net Pay</dt>
                    <dd class="font-bold">₦{{ number_format($dashboard['payroll']['total_net'], 2) }}</dd>
                </div>
            </dl>
            <a href="{{ route('payroll.index') }}" class="mt-4 block text-center text-sm text-green-600 hover:text-green-800 font-medium">
                Run Payroll →
            </a>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('invoices.create') }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                + New Invoice
            </a>
            <a href="{{ route('tax.vat.compute') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                Compute VAT Return
            </a>
            <a href="{{ route('payroll.create') }}"
               class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700">
                Run Payroll
            </a>
            <a href="{{ route('reports.pl') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                P&L Report
            </a>
        </div>
    </div>

</div>
@endsection
