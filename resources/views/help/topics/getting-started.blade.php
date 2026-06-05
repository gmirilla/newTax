@extends('layouts.app')
@section('page-title', 'Getting Started – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Getting Started with NaijaBooks</h1>
        <p class="text-sm text-gray-500 mt-1">Follow these steps to set up your account and start managing your business finances.</p>
    </div>

    {{-- Step 1 --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="bg-green-600 px-5 py-3 flex items-center gap-3">
            <span class="w-7 h-7 bg-white text-green-700 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
            <h2 class="text-white font-semibold">Complete Your Company Profile</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Go to <strong>Settings → Company Settings</strong>. Fill in:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li><strong>Business Name</strong> — appears on every invoice and report</li>
                <li><strong>RC Number</strong> — your CAC registration number</li>
                <li><strong>TIN</strong> — your FIRS Tax Identification Number</li>
                <li><strong>Address, City, State</strong></li>
                <li><strong>Email and Phone</strong> — printed on invoices</li>
                <li><strong>Logo</strong> — upload a PNG or JPG (shown on PDFs)</li>
            </ul>
            <div class="bg-amber-50 border border-amber-200 rounded p-3 text-amber-800 text-xs">
                Your TIN and RC number are printed on all invoices. If you do not have a TIN yet, you can add it later, but FIRS requires it for formal invoicing.
            </div>
        </div>
    </div>

    {{-- Step 2 --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="bg-green-600 px-5 py-3 flex items-center gap-3">
            <span class="w-7 h-7 bg-white text-green-700 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
            <h2 class="text-white font-semibold">Add Your Bank Account</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Go to <strong>Settings → Bank Accounts</strong> and click <strong>Add Bank Account</strong>.</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Enter your account name, bank name, and account number</li>
                <li>Set the <strong>Opening Balance</strong> — the amount currently in the account on your start date</li>
                <li>The first account you add becomes the default for payments</li>
            </ul>
            <div class="bg-blue-50 border border-blue-200 rounded p-3 text-blue-800 text-xs">
                The opening balance is used to calculate your Balance Sheet correctly. Enter the exact amount in the account on the day you started using NaijaBooks.
            </div>
        </div>
    </div>

    {{-- Step 3 --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="bg-green-600 px-5 py-3 flex items-center gap-3">
            <span class="w-7 h-7 bg-white text-green-700 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
            <h2 class="text-white font-semibold">Add Your First Customer</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Go to <strong>Customers</strong> and click <strong>New Customer</strong>. Add:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Customer or company name</li>
                <li>Email address (for sending invoices)</li>
                <li>Phone number and address</li>
                <li>Their TIN (required for WHT deductions)</li>
            </ul>
            <p class="text-xs text-gray-500">You can also create a customer on the fly when creating an invoice — but pre-adding customers saves time.</p>
        </div>
    </div>

    {{-- Step 4 --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="bg-green-600 px-5 py-3 flex items-center gap-3">
            <span class="w-7 h-7 bg-white text-green-700 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">4</span>
            <h2 class="text-white font-semibold">Create Your First Invoice</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Go to <strong>Sales & Finance → Invoices</strong> and click <strong>New Invoice</strong>.</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Select the customer</li>
                <li>Add line items — description, quantity, unit price</li>
                <li>Toggle VAT (7.5%) if applicable</li>
                <li>Toggle WHT if the customer is required to withhold tax</li>
                <li>Click <strong>Save</strong>, then download the PDF to send to your customer</li>
            </ul>
            <p>When the customer pays, open the invoice and click <strong>Record Payment</strong> to mark it settled.</p>
        </div>
    </div>

    {{-- Step 5 --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="bg-green-600 px-5 py-3 flex items-center gap-3">
            <span class="w-7 h-7 bg-white text-green-700 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">5</span>
            <h2 class="text-white font-semibold">Invite Your Team</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Go to <strong>Settings → Team Members</strong> to invite your accountant or staff. There are three roles:</p>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left p-2 border border-gray-200 font-semibold">Role</th>
                            <th class="text-left p-2 border border-gray-200 font-semibold">What they can do</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="p-2 border border-gray-200 font-medium text-green-700">Admin</td>
                            <td class="p-2 border border-gray-200">Full access including settings, billing, and team management</td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td class="p-2 border border-gray-200 font-medium text-blue-700">Accountant</td>
                            <td class="p-2 border border-gray-200">All financial operations (invoices, reports, payroll) — no settings or billing</td>
                        </tr>
                        <tr>
                            <td class="p-2 border border-gray-200 font-medium text-gray-700">Staff</td>
                            <td class="p-2 border border-gray-200">Limited portal access only</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Next steps --}}
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Next steps</h3>
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('help.show', 'invoicing') }}" class="text-sm text-green-600 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                Invoices & Receipts
            </a>
            <a href="{{ route('help.show', 'bookkeeping') }}" class="text-sm text-green-600 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                Bookkeeping Basics
            </a>
            <a href="{{ route('help.show', 'reports') }}" class="text-sm text-green-600 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                Financial Reports
            </a>
            <a href="{{ route('help.show', 'billing') }}" class="text-sm text-green-600 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                Plans & Billing
            </a>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
