@extends('layouts.app')

@section('page-title', 'Payroll — ' . $payroll->getMonthName())

@section('content')
<div class="space-y-6">

    {{-- Summary --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold">{{ $payroll->getMonthName() }} Payroll</h2>
                <p class="text-sm text-gray-500">Pay date: {{ $payroll->pay_date->format('d M Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                @php $colors = ['draft'=>'yellow','approved'=>'green','paid'=>'blue'] @endphp
                <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold
                    bg-{{ $colors[$payroll->status] ?? 'gray' }}-100
                    text-{{ $colors[$payroll->status] ?? 'gray' }}-800">
                    {{ ucfirst($payroll->status) }}
                </span>
                @if($payroll->status === 'draft')
                <form method="POST" action="{{ route('payroll.recompute', $payroll) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Recompute all payslips using current salary structures and 2026 tax rates? Per-run bonuses and deductions will be preserved.')"
                            class="px-4 py-1.5 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                        ↺ Recompute
                    </button>
                </form>
                @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('payroll.approve', $payroll) }}">
                    @csrf
                    <button class="px-4 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                        Approve Payroll
                    </button>
                </form>
                @endif
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-gray-50 rounded p-3 text-center">
                <p class="text-xs text-gray-500">Total Gross</p>
                <p class="text-lg font-bold">₦{{ number_format($payroll->total_gross, 2) }}</p>
            </div>
            <div class="bg-orange-50 rounded p-3 text-center">
                <p class="text-xs text-gray-500">PAYE Deducted</p>
                <p class="text-lg font-bold text-orange-700">₦{{ number_format($payroll->total_paye, 2) }}</p>
            </div>
            <div class="bg-blue-50 rounded p-3 text-center">
                <p class="text-xs text-gray-500">Pension (Employee 8%)</p>
                <p class="text-lg font-bold text-blue-700">₦{{ number_format($payroll->total_pension, 2) }}</p>
            </div>
            <div class="bg-purple-50 rounded p-3 text-center">
                <p class="text-xs text-gray-500">NHF (2.5%)</p>
                <p class="text-lg font-bold text-purple-700">₦{{ number_format($payroll->total_nhf, 2) }}</p>
            </div>
            <div class="bg-green-50 rounded p-3 text-center">
                <p class="text-xs text-gray-500">Total Net Pay</p>
                <p class="text-lg font-bold text-green-700">₦{{ number_format($payroll->total_net, 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Employee breakdown --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-base font-semibold">Employee Payslips</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Employee</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Gross</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Pension</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">NHF</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Taxable</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">PAYE</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Net Pay</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($payroll->items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $item->employee->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->employee->job_title }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">₦{{ number_format($item->gross_pay, 2) }}</td>
                        <td class="px-4 py-3 text-right text-blue-600">₦{{ number_format($item->pension_employee, 2) }}</td>
                        <td class="px-4 py-3 text-right text-purple-600">₦{{ number_format($item->nhf, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">₦{{ number_format($item->taxable_income, 2) }}</td>
                        <td class="px-4 py-3 text-right text-orange-600 font-medium">₦{{ number_format($item->paye_tax, 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-green-700">₦{{ number_format($item->net_pay, 2) }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('payroll.payslip', $item) }}" class="text-green-600 hover:underline text-xs">Payslip</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
