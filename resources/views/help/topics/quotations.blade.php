@extends('layouts.app')
@section('page-title', 'Quotations – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Quotations (Proforma Invoices)</h1>
        <p class="text-sm text-gray-500 mt-1">Send price estimates to customers before issuing a formal invoice.</p>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
        <strong>What is a Quotation?</strong> A quotation (also called a proforma invoice or estimate) shows your customer what a job or supply will cost. It is <em>not</em> a demand for payment. Once the customer accepts, you convert it to a formal invoice.
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Creating a Quotation</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Go to <strong>Sales & Finance → Quotes</strong></li>
                <li>Click <strong>New Quote</strong></li>
                <li>Select the customer</li>
                <li>Set the <strong>Quote Date</strong> and <strong>Valid Until</strong> (expiry date)</li>
                <li>Add line items with descriptions, quantities, and prices</li>
                <li>Toggle VAT or WHT as needed</li>
                <li>Add any notes or terms</li>
                <li>Click <strong>Save Quote</strong></li>
            </ol>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Quote Statuses</h2>
        </div>
        <div class="p-5 text-sm text-gray-700">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left p-2 border border-gray-200 font-semibold">Status</th>
                        <th class="text-left p-2 border border-gray-200 font-semibold">Meaning</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="p-2 border border-gray-200"><span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full font-medium">Draft</span></td>
                        <td class="p-2 border border-gray-200">Created but not yet sent to the customer</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="p-2 border border-gray-200"><span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full font-medium">Sent</span></td>
                        <td class="p-2 border border-gray-200">Shared with the customer, awaiting response</td>
                    </tr>
                    <tr>
                        <td class="p-2 border border-gray-200"><span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full font-medium">Accepted</span></td>
                        <td class="p-2 border border-gray-200">Customer agreed — ready to convert to an invoice</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="p-2 border border-gray-200"><span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full font-medium">Declined</span></td>
                        <td class="p-2 border border-gray-200">Customer rejected the quote</td>
                    </tr>
                    <tr>
                        <td class="p-2 border border-gray-200"><span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full font-medium">Expired</span></td>
                        <td class="p-2 border border-gray-200">Past the valid-until date without a response</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Converting a Quote to an Invoice</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Open the quote (must be in <strong>Accepted</strong> or <strong>Sent</strong> status)</li>
                <li>Click <strong>Convert to Invoice</strong></li>
                <li>Review the pre-filled invoice details — all line items are copied over</li>
                <li>Adjust the invoice date and due date</li>
                <li>Click <strong>Save Invoice</strong></li>
            </ol>
            <p class="text-xs text-gray-500">The original quote remains in your system for reference. Converting does not delete it.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Downloading the Quotation PDF</h2>
        </div>
        <div class="p-5 text-sm text-gray-700 space-y-2">
            <p>Open the quote and click <strong>Download PDF</strong>. The PDF is clearly labelled "PROFORMA INVOICE / ESTIMATE" and states that it is not a demand for payment.</p>
            <p>Share the PDF or its public link directly with your customer via email or WhatsApp.</p>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
