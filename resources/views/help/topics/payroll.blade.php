@extends('layouts.app')
@section('page-title', 'Payroll – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Payroll</h1>
        <p class="text-sm text-gray-500 mt-1">Manage employees, run monthly payroll, generate payslips, and track PAYE deductions.</p>
    </div>

    @unless(auth()->user()->tenant->planAllows('payroll'))
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800 flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <div>
            <strong>Payroll is a Pro feature.</strong> Upgrade your plan to access payroll management.
            <a href="{{ route('billing') }}" class="underline ml-1">View Plans</a>
        </div>
    </div>
    @endunless

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Adding Employees</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Go to <strong>Payroll → Employees</strong> and click <strong>New Employee</strong></li>
                <li>Enter the employee's full name, email, and phone</li>
                <li>Set the <strong>Gross Salary</strong> (monthly)</li>
                <li>Enter the employee's <strong>Bank Account</strong> details for salary payment</li>
                <li>Set the <strong>Start Date</strong></li>
                <li>Mark whether the employee is exempt from PAYE (rare)</li>
                <li>Click <strong>Save</strong></li>
            </ol>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Understanding PAYE Deductions</h2>
        </div>
        <div class="p-5 space-y-4 text-sm text-gray-700">
            <p>Pay As You Earn (PAYE) is income tax deducted from employees' salaries and remitted to FIRS each month.</p>
            <p>NaijaBooks calculates PAYE automatically using the Nigeria tax bands:</p>
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left p-2 border border-gray-200 font-semibold">Annual Taxable Income</th>
                        <th class="text-left p-2 border border-gray-200 font-semibold">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="p-2 border border-gray-200">First ₦300,000</td><td class="p-2 border border-gray-200">7%</td></tr>
                    <tr class="bg-gray-50"><td class="p-2 border border-gray-200">Next ₦300,000</td><td class="p-2 border border-gray-200">11%</td></tr>
                    <tr><td class="p-2 border border-gray-200">Next ₦500,000</td><td class="p-2 border border-gray-200">15%</td></tr>
                    <tr class="bg-gray-50"><td class="p-2 border border-gray-200">Next ₦500,000</td><td class="p-2 border border-gray-200">19%</td></tr>
                    <tr><td class="p-2 border border-gray-200">Next ₦1,600,000</td><td class="p-2 border border-gray-200">21%</td></tr>
                    <tr class="bg-gray-50"><td class="p-2 border border-gray-200">Above ₦3,200,000</td><td class="p-2 border border-gray-200">24%</td></tr>
                </tbody>
            </table>
            <p class="text-xs text-gray-500">A Consolidated Relief Allowance (CRA) of the higher of ₦200,000 or 1% of gross income, plus 20% of gross income, is deducted before applying these bands.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Running Monthly Payroll</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Go to <strong>Payroll → Pay Runs</strong></li>
                <li>Click <strong>New Pay Run</strong></li>
                <li>Select the pay period (month and year)</li>
                <li>Review the computed amounts for each employee: gross, PAYE, pension, net pay</li>
                <li>Make any one-off adjustments (bonuses, deductions)</li>
                <li>Click <strong>Approve Pay Run</strong> — this posts salary expense and all statutory deductions to the GL</li>
                <li>Generate individual payslips as PDFs for each employee</li>
            </ol>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Pension Contributions</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Under the Pension Reform Act, both employee and employer contribute to a Retirement Savings Account (RSA):</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li><strong>Employee contribution</strong>: 8% of monthly emolument</li>
                <li><strong>Employer contribution</strong>: 10% of monthly emolument</li>
            </ul>
            <p class="text-xs text-gray-500">Monthly emolument includes basic salary, housing allowance, and transport allowance.</p>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
