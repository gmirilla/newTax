@extends('layouts.app')

@section('page-title', 'Run Payroll')

@section('content')
<div class="space-y-6">
    <form method="POST" action="{{ route('payroll.store') }}">
        @csrf

        {{-- Header --}}
        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <div>
                <h2 class="text-base font-semibold">Process Monthly Payroll</h2>
                <p class="text-sm text-gray-500 mt-1">
                    PAYE (0%–25% — 2026 bands, ₦800k tax-free), Pension (8%), NHF (2.5%), and NHIS (where enabled) are auto-computed per Nigerian law.
                    Override overtime, bonuses, and deductions for individual employees below.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pay Year *</label>
                    <select name="pay_year" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        @for($y = now()->year; $y >= now()->year - 2; $y--)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pay Month *</label>
                    <select name="pay_month" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0,0,0,$m,1)) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pay Date *</label>
                    <input type="date" name="pay_date" value="{{ now()->endOfMonth()->toDateString() }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
            </div>
        </div>

        {{-- Per-employee overrides --}}
        @if($employees->isNotEmpty())
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-sm font-semibold">
                    Employee Adjustments
                    <span class="text-gray-400 font-normal">({{ $employees->count() }} active employees)</span>
                </h3>
                <p class="text-xs text-gray-400">Leave blank to use employee's standard salary structure</p>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach($employees as $employee)
                @php
                    $pensionBase = $employee->basic_salary + $employee->housing_allowance + $employee->transport_allowance;
                    $grossSalary = $employee->calculateGrossSalary();
                @endphp
                <div class="px-6 py-4" x-data="{ open: false }">
                    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $employee->job_title }} · {{ $employee->department }}
                                · Gross: ₦{{ number_format($grossSalary, 2) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($employee->nhf_enabled)
                                <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded">NHF</span>
                            @endif
                            @if($employee->nhis_enabled)
                                <span class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded">NHIS</span>
                            @endif
                            <span class="text-xs text-gray-400" x-text="open ? '▲ Collapse' : '▼ Adjust'"></span>
                        </div>
                    </div>

                    <div x-show="open" x-cloak class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                        {{-- Variable earnings --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Bonus (₦)</label>
                            <input type="number" name="employees[{{ $employee->id }}][bonus]"
                                   value="0" min="0" step="0.01"
                                   class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Overtime (₦)</label>
                            <input type="number" name="employees[{{ $employee->id }}][overtime]"
                                   value="0" min="0" step="0.01"
                                   class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                        </div>

                        {{-- Deductions --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Loan Repayment (₦)</label>
                            <input type="number" name="employees[{{ $employee->id }}][loan_deduction]"
                                   value="0" min="0" step="0.01"
                                   class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Advance Recovery (₦)</label>
                            <input type="number" name="employees[{{ $employee->id }}][advance_deduction]"
                                   value="0" min="0" step="0.01"
                                   class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Penalty / Absence (₦)</label>
                            <input type="number" name="employees[{{ $employee->id }}][penalty_deduction]"
                                   value="0" min="0" step="0.01"
                                   class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Notes</label>
                            <input type="text" name="employees[{{ $employee->id }}][notes]"
                                   placeholder="e.g. Mid-year bonus"
                                   class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                        </div>

                        {{-- Statutory preview --}}
                        <div class="col-span-2 md:col-span-3 bg-gray-50 rounded p-3 text-xs text-gray-500 grid grid-cols-4 gap-2">
                            <div>
                                <span class="block font-medium text-gray-600">Pension (8%)</span>
                                ₦{{ number_format($pensionBase * 0.08, 2) }}
                            </div>
                            @if($employee->nhf_enabled)
                            <div>
                                <span class="block font-medium text-gray-600">NHF (2.5%)</span>
                                ₦{{ number_format($employee->basic_salary * 0.025, 2) }}
                            </div>
                            @endif
                            @if($employee->nhis_enabled)
                            <div>
                                <span class="block font-medium text-gray-600">HMO/NHIS</span>
                                ₦{{ number_format($employee->nhis_amount ?? 0, 2) }}
                            </div>
                            @endif
                            <div>
                                <span class="block font-medium text-gray-600">Est. PAYE</span>
                                @php
                                    $payeService = app(\App\Services\PayeService::class);
                                    $annualGross = $grossSalary * 12;
                                    $annualPension = $pensionBase * 0.08 * 12;
                                    $annualNhf = $employee->nhf_enabled ? $employee->basic_salary * 0.025 * 12 : 0;
                                    $cra = $payeService->computeConsolidatedRelief($annualGross);
                                    $taxable = max(0, $annualGross - $annualPension - $annualNhf - $cra);
                                    $paye = round($payeService->computeProgressiveTax($taxable) / 12, 2);
                                @endphp
                                ₦{{ number_format($paye, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-700">
            No active employees found.
            <a href="{{ route('payroll.employees.create') }}" class="underline ml-1">Add employees first →</a>
        </div>
        @endif

        <div class="flex justify-end gap-3">
            <a href="{{ route('payroll.index') }}"
               class="px-4 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">Cancel</a>
            <button type="submit" @if($employees->isEmpty()) disabled @endif
                    class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 disabled:opacity-50">
                Process Payroll
            </button>
        </div>
    </form>
</div>
@endsection
