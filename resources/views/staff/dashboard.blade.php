@extends('layouts.app')

@section('page-title', 'My Dashboard')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Welcome header --}}
    <div class="bg-white rounded-lg shadow px-6 py-5 flex items-center gap-4">
        <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-bold text-lg shrink-0">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-900">Welcome, {{ $user->name }}</h1>
            <p class="text-sm text-gray-500">
                {{ $currentTenant->name }} &middot;
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                    {{ ucfirst($user->role) }}
                </span>
            </p>
        </div>
    </div>

    @if($employee)
    {{-- Employee info card --}}
    <div class="bg-white rounded-lg shadow px-6 py-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">My Employment Details</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-400 uppercase font-medium">Employee ID</p>
                <p class="font-medium">{{ $employee->employee_id ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-medium">Job Title</p>
                <p class="font-medium">{{ $employee->job_title ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-medium">Department</p>
                <p class="font-medium">{{ $employee->department ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-medium">Hire Date</p>
                <p class="font-medium">{{ $employee->hire_date?->format('d M Y') ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Payslips --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-sm font-semibold text-gray-700">My Payslips</h2>
        </div>
        @if($recentPayslips->isEmpty())
        <div class="px-6 py-8 text-center text-sm text-gray-400">No payslips yet.</div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold">Period</th>
                    <th class="px-6 py-3 text-right font-semibold">Gross Pay</th>
                    <th class="px-6 py-3 text-right font-semibold">Net Pay</th>
                    <th class="px-6 py-3 text-center font-semibold">Payslip</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($recentPayslips as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-gray-700">
                        {{ $item->payroll?->period_label ?? $item->payroll?->payroll_month ?? '—' }}
                    </td>
                    <td class="px-6 py-3 text-right font-medium">
                        ₦{{ number_format($item->gross_pay ?? 0, 2) }}
                    </td>
                    <td class="px-6 py-3 text-right font-semibold text-green-700">
                        ₦{{ number_format($item->net_pay ?? 0, 2) }}
                    </td>
                    <td class="px-6 py-3 text-center">
                        <a href="{{ route('payroll.payslip', $item) }}"
                           target="_blank"
                           class="text-xs text-indigo-600 hover:underline">
                            Download PDF
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @else
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-4 text-sm text-amber-700">
        No employee record linked to your email address (<strong>{{ $user->email }}</strong>).
        Contact your admin to link your account to a payroll profile.
    </div>
    @endif

    {{-- My submitted expenses --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">My Submitted Expenses</h2>
        </div>
        @if($myExpenses->isEmpty())
        <div class="px-6 py-8 text-center text-sm text-gray-400">No expenses submitted yet.</div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold">Date</th>
                    <th class="px-6 py-3 text-left font-semibold">Category</th>
                    <th class="px-6 py-3 text-left font-semibold">Description</th>
                    <th class="px-6 py-3 text-right font-semibold">Amount</th>
                    <th class="px-6 py-3 text-center font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($myExpenses as $expense)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-gray-500 whitespace-nowrap">
                        {{ $expense->expense_date->format('d M Y') }}
                    </td>
                    <td class="px-6 py-3 text-gray-700 capitalize">{{ $expense->category }}</td>
                    <td class="px-6 py-3 text-gray-500 truncate max-w-xs">{{ $expense->description }}</td>
                    <td class="px-6 py-3 text-right font-medium">₦{{ number_format($expense->amount, 2) }}</td>
                    <td class="px-6 py-3 text-center">
                        @php
                            $colour = match($expense->status) {
                                'approved' => 'bg-green-100 text-green-700',
                                'paid'     => 'bg-blue-100 text-blue-700',
                                'rejected' => 'bg-red-100 text-red-600',
                                default    => 'bg-yellow-100 text-yellow-700',
                            };
                        @endphp
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $colour }}">
                            {{ ucfirst($expense->status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>
@endsection
