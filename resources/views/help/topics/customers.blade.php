@extends('layouts.app')
@section('page-title', 'Customers – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Customers</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your customer records for faster invoicing and accurate payment tracking.</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Adding a Customer</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Go to <strong>Customers</strong> in the sidebar</li>
                <li>Click <strong>New Customer</strong></li>
                <li>Fill in the details below and click <strong>Save</strong></li>
            </ol>
            <table class="w-full text-xs border-collapse mt-2">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left p-2 border border-gray-200 font-semibold">Field</th>
                        <th class="text-left p-2 border border-gray-200 font-semibold">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="p-2 border border-gray-200 font-medium">Name</td><td class="p-2 border border-gray-200">Individual or company name (required)</td></tr>
                    <tr class="bg-gray-50"><td class="p-2 border border-gray-200 font-medium">Email</td><td class="p-2 border border-gray-200">Used for the invoice public link and correspondence</td></tr>
                    <tr><td class="p-2 border border-gray-200 font-medium">Phone</td><td class="p-2 border border-gray-200">Optional but useful for follow-up</td></tr>
                    <tr class="bg-gray-50"><td class="p-2 border border-gray-200 font-medium">Address / City / State</td><td class="p-2 border border-gray-200">Appears on invoice PDFs in the "Bill To" section</td></tr>
                    <tr><td class="p-2 border border-gray-200 font-medium">TIN</td><td class="p-2 border border-gray-200">Required if the customer will deduct WHT</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Customer Overview</h2>
        </div>
        <div class="p-5 space-y-2 text-sm text-gray-700">
            <p>Clicking on a customer name opens their profile, showing:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>All invoices raised for this customer</li>
                <li>Total amount invoiced and total paid</li>
                <li>Outstanding balance</li>
                <li>All quotations sent</li>
            </ul>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Walk-in / Cash Customers</h2>
        </div>
        <div class="p-5 text-sm text-gray-700 space-y-2">
            <p>You do not need to pre-create a customer for every sale. When creating an invoice, you can leave the customer field blank. The invoice will show <strong>"Walk-in Customer"</strong> on the PDF.</p>
            <p>This is useful for retail counter sales where you don't collect customer details.</p>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
