@extends('layouts.app')

@section('page-title', 'Payslip')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    <div class="flex justify-between items-center no-print">
        <a href="javascript:history.back()" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
        <button onclick="window.print()"
                class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md hover:bg-gray-800">
            Print / Save PDF
        </button>
    </div>

    <div class="bg-white rounded-lg shadow print:shadow-none">
        {{-- Header --}}
        <div class="bg-green-700 text-white px-6 py-5 rounded-t-lg">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-xl font-bold">PAYSLIP</h1>
                    <p class="text-green-200 text-sm mt-0.5">{{ $payslip['period'] }}</p>
                </div>
                <div class="text-right text-sm text-green-200">
                    <p class="font-semibold text-white">{{ auth()->user()->tenant->name }}</p>
                    <p>RC: {{ auth()->user()->tenant->rc_number ?? '—' }}</p>
                    <p>TIN: {{ auth()->user()->tenant->tin ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Employee Info --}}
        <div class="px-6 py-4 border-b bg-gray-50 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-400 uppercase font-medium">Employee</p>
                <p class="font-semibold">{{ $payslip['employee']->full_name }}</p>
                <p class="text-gray-500">{{ $payslip['employee']->job_title }}</p>
                <p class="text-gray-500">{{ $payslip['employee']->department }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-medium">Details</p>
                <p>Employee ID: {{ $payslip['employee']->employee_id }}</p>
                <p>TIN: {{ $payslip['employee']->tin ?? '—' }}</p>
                <p>State: {{ $payslip['employee']->state_of_residence ?? '—' }}</p>
                <p>Bank: {{ $payslip['employee']->bank_name }} — {{ $payslip['employee']->account_number }}</p>
            </div>
        </div>

        {{-- Earnings & Deductions --}}
        <div class="p-6 grid grid-cols-2 gap-6 text-sm">
            {{-- Earnings --}}
            <div>
                <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">Earnings</h3>
                <table class="w-full">
                    <tbody class="divide-y divide-gray-100">
                        @foreach($payslip['earnings'] as $label => $amount)
                        <tr>
                            <td class="py-1.5 text-gray-600">{{ $label }}</td>
                            <td class="py-1.5 text-right font-medium">₦{{ number_format($amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 font-bold">
                            <td class="pt-2">Gross Pay</td>
                            <td class="pt-2 text-right text-green-700">₦{{ number_format($payslip['gross_pay'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Deductions --}}
            <div>
                <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">Deductions</h3>
                <table class="w-full">
                    <tbody class="divide-y divide-gray-100">
                        @foreach($payslip['deductions'] as $label => $amount)
                        <tr>
                            <td class="py-1.5 text-gray-600">{{ $label }}</td>
                            <td class="py-1.5 text-right font-medium text-red-600">₦{{ number_format($amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 font-bold">
                            <td class="pt-2">Total Deductions</td>
                            <td class="pt-2 text-right text-red-700">
                                ₦{{ number_format(array_sum($payslip['deductions']), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- PAYE Workings --}}
        <div class="mx-6 mb-4 p-3 bg-blue-50 rounded text-xs text-blue-700 space-y-1">
            <p class="font-semibold text-blue-800">PAYE Computation — NTA 2025 (Nigeria Tax Act 2025)</p>
            <p>Monthly CRA:
                <strong>₦{{ number_format($payslip['consolidated_relief'], 2) }}</strong>
                <span class="text-blue-500 ml-1">(max(₦200,000, 1% gross) + 20% gross ÷ 12)</span>
            </p>
            @if(!empty($payslip['tax_reliefs']))
            <div class="border-t border-blue-200 pt-1 mt-1">
                <p class="font-medium text-blue-800">NTA 2025 Personal Reliefs Applied (monthly):</p>
                @foreach($payslip['tax_reliefs'] as $label => $amount)
                <p class="pl-2">{{ $label }}: <strong>₦{{ number_format($amount, 2) }}</strong></p>
                @endforeach
            </div>
            @endif
            <p class="border-t border-blue-200 pt-1">
                Monthly Taxable Income after all reliefs:
                <strong>₦{{ number_format($payslip['taxable_income'], 2) }}</strong>
            </p>
            <p class="text-blue-500">Tax Bands: 0% (≤₦66,667) | 15% | 18% | 21% | 23% | 25%</p>
        </div>

        {{-- Net Pay --}}
        <div class="mx-6 mb-6 bg-green-700 text-white rounded-lg px-6 py-4 flex justify-between items-center">
            <div>
                <p class="text-green-200 text-sm">NET PAY</p>
                <p class="text-3xl font-bold">₦{{ number_format($payslip['net_pay'], 2) }}</p>
            </div>
            <div class="text-right text-sm text-green-200">
                <p>Employer Pension (10%)</p>
                <p class="text-white font-semibold">
                    ₦{{ number_format($payslip['employer_cost']['Employer Pension (10%)'] ?? 0, 2) }}
                </p>
                <p class="text-xs mt-1 text-green-300">Not deducted from employee</p>
            </div>
        </div>

        @if($payslip['notes'])
        <div class="mx-6 mb-4 text-xs text-gray-500 italic border-t pt-3">
            Note: {{ $payslip['notes'] }}
        </div>
        @endif

        <div class="px-6 pb-4 text-xs text-gray-400 text-center border-t pt-3">
            Compliant with Nigeria Tax Act 2025 | Pension Reform Act 2014 | NHF Act | NHIS Act
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print, aside, header nav { display: none !important; }
        body, html { background: white !important; }
    }
</style>
@endsection
