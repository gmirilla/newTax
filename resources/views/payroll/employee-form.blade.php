@extends('layouts.app')

@section('page-title', isset($employee) ? 'Edit Employee' : 'Add Employee')

@section('content')
@php
    $editing = isset($employee);
    $action  = $editing
        ? route('payroll.employees.update', $employee)
        : route('payroll.employees.store');
@endphp

<div class="max-w-3xl mx-auto space-y-6" x-data="{ nhisEnabled: {{ $editing && $employee->nhis_enabled ? 'true' : 'false' }} }">

    <div class="flex items-center justify-between">
        <a href="{{ route('payroll.employees') }}" class="text-sm text-gray-500 hover:text-gray-700">← Employees</a>
        @if($editing)
        <span class="text-xs text-gray-400">ID: {{ $employee->employee_id }}</span>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-base font-semibold mb-4">
            {{ $editing ? 'Edit ' . $employee->full_name : 'New Employee' }}
        </h2>

        <form method="POST" action="{{ $action }}" class="space-y-5">
            @csrf
            @if($editing) @method('PUT') @endif

            {{-- Personal Details --}}
            <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider border-b pb-1">Personal Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First Name *</label>
                    <input type="text" name="first_name" required
                           value="{{ old('first_name', $employee->first_name ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                    <input type="text" name="last_name" required
                           value="{{ old('last_name', $employee->last_name ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email"
                           value="{{ old('email', $employee->email ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="phone" placeholder="+234..."
                           value="{{ old('phone', $employee->phone ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">State of Residence <span class="text-xs text-gray-400">(PAYE remittance)</span></label>
                    <select name="state_of_residence" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">— Select State —</option>
                        @foreach(['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'] as $state)
                        <option value="{{ $state }}"
                            {{ old('state_of_residence', $employee->state_of_residence ?? '') === $state ? 'selected' : '' }}>
                            {{ $state }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Personal TIN</label>
                    <input type="text" name="tin" placeholder="1234567-0001"
                           value="{{ old('tin', $employee->tin ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" name="address"
                           value="{{ old('address', $employee->address ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            {{-- Employment --}}
            <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider border-b pb-1 pt-2">Employment</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Job Title *</label>
                    <input type="text" name="job_title" required
                           value="{{ old('job_title', $employee->job_title ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Department</label>
                    <input type="text" name="department"
                           value="{{ old('department', $employee->department ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Hire Date *</label>
                    <input type="date" name="hire_date" required
                           value="{{ old('hire_date', isset($employee) ? $employee->hire_date->toDateString() : now()->toDateString()) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Employment Type</label>
                    <select name="employment_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        @foreach(['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract'] as $val => $label)
                        <option value="{{ $val }}"
                            {{ old('employment_type', $employee->employment_type ?? 'full_time') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @if($editing)
                <div>
                    <label class="block text-sm font-medium text-gray-700">Termination Date</label>
                    <input type="date" name="termination_date"
                           value="{{ old('termination_date', $employee->termination_date?->toDateString() ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">Set to deactivate the employee</p>
                </div>
                @endif
            </div>

            {{-- Salary Structure --}}
            <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider border-b pb-1 pt-2">Salary Structure (₦/month)</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Basic Salary * <span class="text-xs text-gray-400">(NMW: ₦70,000)</span>
                    </label>
                    <input type="number" name="basic_salary" required min="30000" step="0.01"
                           value="{{ old('basic_salary', $employee->basic_salary ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    <p class="text-xs text-gray-400 mt-0.5">Pension base &amp; NHF base</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Housing Allowance</label>
                    <input type="number" name="housing_allowance" min="0" step="0.01"
                           value="{{ old('housing_allowance', $employee->housing_allowance ?? 0) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">Pension base</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Transport Allowance</label>
                    <input type="number" name="transport_allowance" min="0" step="0.01"
                           value="{{ old('transport_allowance', $employee->transport_allowance ?? 0) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">Pension base</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Medical Allowance</label>
                    <input type="number" name="medical_allowance" min="0" step="0.01"
                           value="{{ old('medical_allowance', $employee->medical_allowance ?? 0) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Utility Allowance</label>
                    <input type="number" name="utility_allowance" min="0" step="0.01"
                           value="{{ old('utility_allowance', $employee->utility_allowance ?? 0) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Other Allowances</label>
                    <input type="number" name="other_allowances" min="0" step="0.01"
                           value="{{ old('other_allowances', $employee->other_allowances ?? 0) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            {{-- Statutory Deductions --}}
            <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider border-b pb-1 pt-2">Statutory Deduction Settings</h3>
            <div class="grid grid-cols-2 gap-4">

                {{-- NHF --}}
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-md">
                    <input type="hidden" name="nhf_enabled" value="0">
                    <input type="checkbox" name="nhf_enabled" id="nhf_enabled" value="1"
                           {{ old('nhf_enabled', $employee->nhf_enabled ?? true) ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 text-green-600">
                    <div>
                        <label for="nhf_enabled" class="text-sm font-medium text-gray-700 cursor-pointer">
                            NHF (National Housing Fund)
                        </label>
                        <p class="text-xs text-gray-400">2.5% of basic salary per month</p>
                    </div>
                </div>

                {{-- NHIS / HMO --}}
                <div class="p-3 bg-gray-50 rounded-md space-y-3">
                    <div class="flex items-start gap-3">
                        <input type="hidden" name="nhis_enabled" value="0">
                        <input type="checkbox" name="nhis_enabled" id="nhis_enabled" value="1"
                               x-model="nhisEnabled"
                               {{ old('nhis_enabled', $employee->nhis_enabled ?? false) ? 'checked' : '' }}
                               class="mt-1 rounded border-gray-300 text-green-600">
                        <div>
                            <label for="nhis_enabled" class="text-sm font-medium text-gray-700 cursor-pointer">
                                HMO / NHIS Deduction
                            </label>
                            <p class="text-xs text-gray-400">Fixed monthly amount deducted from payslip</p>
                        </div>
                    </div>
                    <div x-show="nhisEnabled" class="pl-1">
                        <label class="block text-xs font-medium text-gray-600">Monthly Amount (₦)</label>
                        <input type="number" name="nhis_amount" min="0" step="0.01"
                               value="{{ old('nhis_amount', $employee->nhis_amount ?? 0) }}"
                               placeholder="e.g. 5000"
                               class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
            </div>

            <div class="p-3 bg-blue-50 border border-blue-100 rounded text-xs text-blue-700">
                <strong>Auto-computed each payroll run:</strong>
                Pension = 8% of (Basic + Housing + Transport) ·
                NHF = 2.5% of Basic ·
                HMO/NHIS = fixed ₦ amount above ·
                PAYE per NTA 2025 bands (0%–25%, ₦800k tax-free) after CRA
            </div>

            {{-- NTA 2025 Personal Tax Reliefs --}}
            <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider border-b pb-1 pt-2">
                Personal Tax Reliefs
                <span class="font-normal normal-case text-gray-400">(Nigeria Tax Act 2025 — reduce taxable income for PAYE)</span>
            </h3>
            <div class="grid grid-cols-2 gap-4">

                {{-- Home Loan Interest --}}
                <div class="p-3 bg-gray-50 rounded-md space-y-1">
                    <label class="block text-sm font-medium text-gray-700">
                        Home Loan Interest (₦/year)
                    </label>
                    <input type="number" name="home_loan_interest" min="0" step="0.01"
                           value="{{ old('home_loan_interest', $employee->home_loan_interest ?? 0) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    <p class="text-xs text-gray-400">Annual interest on owner-occupied residential home loan — fully deductible. Requires loan documents + payment evidence.</p>
                </div>

                {{-- Life Insurance Premium --}}
                <div class="p-3 bg-gray-50 rounded-md space-y-1">
                    <label class="block text-sm font-medium text-gray-700">
                        Life / Annuity Insurance Premium (₦/year)
                    </label>
                    <input type="number" name="life_insurance_premium" min="0" step="0.01"
                           value="{{ old('life_insurance_premium', $employee->life_insurance_premium ?? 0) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    <p class="text-xs text-gray-400">Annual premium paid to an approved insurer — fully deductible. Requires insurer receipts.</p>
                </div>

                {{-- Annual Rent --}}
                <div class="p-3 bg-gray-50 rounded-md space-y-1 col-span-2 md:col-span-1" x-data="{ rent: {{ old('annual_rent', $employee->annual_rent ?? 0) }} }">
                    <label class="block text-sm font-medium text-gray-700">
                        Annual Rent Paid (₦/year)
                    </label>
                    <input type="number" name="annual_rent" min="0" step="0.01"
                           x-model="rent"
                           value="{{ old('annual_rent', $employee->annual_rent ?? 0) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    <p class="text-xs text-gray-400">
                        Rent relief = 20% of annual rent, max ₦500,000.
                        <span class="text-green-700 font-medium" x-text="'Relief: ₦' + Math.min(rent * 0.20, 500000).toLocaleString('en-NG', {minimumFractionDigits:2, maximumFractionDigits:2})"></span>
                    </p>
                </div>

            </div>

            <div class="p-3 bg-amber-50 border border-amber-100 rounded text-xs text-amber-700">
                <strong>NTA 2025 reliefs reduce taxable income for PAYE.</strong>
                They do not reduce gross pay. Employees must provide supporting documents to the employer for record-keeping.
            </div>

            {{-- Bank Details --}}
            <h3 class="text-xs font-bold uppercase text-gray-400 tracking-wider border-b pb-1 pt-2">Bank Details</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                    <input type="text" name="bank_name"
                           value="{{ old('bank_name', $employee->bank_name ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Account Number</label>
                    <input type="text" name="account_number" maxlength="10"
                           value="{{ old('account_number', $employee->account_number ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Account Name</label>
                    <input type="text" name="account_name"
                           value="{{ old('account_name', $employee->account_name ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('payroll.employees') }}"
                   class="px-4 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">Cancel</a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    {{ $editing ? 'Save Changes' : 'Add Employee' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
