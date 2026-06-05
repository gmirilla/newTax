@extends('layouts.app')
@section('page-title', 'Invoices & Receipts – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Invoices & Receipts</h1>
        <p class="text-sm text-gray-500 mt-1">Create, send, and manage invoices. Record payments and download professional PDFs.</p>
    </div>

    {{-- Creating an invoice --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Creating an Invoice</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Go to <strong>Sales & Finance → Invoices</strong></li>
                <li>Click <strong>New Invoice</strong></li>
                <li>Select or type a customer name. For walk-in/cash sales, you can leave the customer blank — the invoice will show "Walk-in Customer"</li>
                <li>Set the <strong>Invoice Date</strong> and <strong>Due Date</strong></li>
                <li>Add line items: description, quantity, and unit price. Tick <strong>VAT</strong> on each item if applicable</li>
                <li>At the invoice level, toggle <strong>Apply VAT (7.5%)</strong> or <strong>Apply WHT</strong> as needed</li>
                <li>Add any notes or payment terms in the Notes field</li>
                <li>Click <strong>Save Invoice</strong></li>
            </ol>
        </div>
    </div>

    {{-- VAT and WHT --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">VAT and WHT Explained</h2>
        </div>
        <div class="p-5 space-y-4 text-sm text-gray-700">
            <div>
                <p class="font-semibold text-gray-800 mb-1">VAT — Value Added Tax (7.5%)</p>
                <ul class="list-disc list-inside space-y-1 pl-2 text-gray-600">
                    <li>Added to the invoice total and collected from the customer</li>
                    <li>You remit this to FIRS on behalf of your customer</li>
                    <li>Only add VAT if your business is VAT-registered</li>
                    <li>You can enable VAT per line item or on the whole invoice</li>
                </ul>
            </div>
            <div>
                <p class="font-semibold text-gray-800 mb-1">WHT — Withholding Tax</p>
                <ul class="list-disc list-inside space-y-1 pl-2 text-gray-600">
                    <li>Deducted from the amount the customer pays you</li>
                    <li>The customer pays WHT directly to FIRS on your behalf</li>
                    <li>Common for professional services, consulting, and contracts</li>
                    <li>Rate depends on the type of service (5% or 10%)</li>
                    <li>The WHT certificate from the customer is your proof of deduction</li>
                </ul>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded p-3 text-amber-800 text-xs">
                WHT reduces the amount you receive, not the amount you charge. On a ₦100,000 invoice with 5% WHT, you receive ₦95,000. The ₦5,000 is paid by the customer to FIRS.
            </div>
        </div>
    </div>

    {{-- Downloading PDF --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Downloading the Invoice PDF</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Open any invoice and click <strong>Download PDF</strong>. The PDF includes:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Your company logo, name, address, and TIN/RC number</li>
                <li>Customer billing details</li>
                <li>Itemised line items with VAT indicators</li>
                <li>Subtotal, VAT, WHT, and final total</li>
                <li>Payment status badge (Draft / Sent / Paid / Overdue)</li>
            </ul>
            <p class="text-xs text-gray-500">You can customise the PDF accent colour in <strong>Settings → Company Settings → Invoice Appearance</strong>.</p>
        </div>
    </div>

    {{-- Recording payment --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Recording a Payment</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Open the invoice</li>
                <li>Click <strong>Record Payment</strong></li>
                <li>Enter the amount paid, payment date, and payment method</li>
                <li>Select which bank account the money was received into</li>
                <li>Click <strong>Save Payment</strong></li>
            </ol>
            <p>The invoice status updates automatically:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li><span class="font-medium text-blue-700">Sent</span> — issued but not yet paid</li>
                <li><span class="font-medium text-yellow-700">Partial</span> — part payment received</li>
                <li><span class="font-medium text-green-700">Paid</span> — fully settled</li>
                <li><span class="font-medium text-red-700">Overdue</span> — past due date with outstanding balance</li>
            </ul>
        </div>
    </div>

    {{-- Invoice statuses --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Invoice Numbering</h2>
        </div>
        <div class="p-5 text-sm text-gray-700 space-y-2">
            <p>Invoice numbers are automatically assigned in the format <strong>INV-0001</strong>, incrementing with each new invoice. This sequence is unique to your business.</p>
            <p class="text-xs text-gray-500">Invoice numbers cannot be changed after creation to maintain audit integrity.</p>
        </div>
    </div>

    {{-- Public link --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Sharing Invoice Links</h2>
        </div>
        <div class="p-5 text-sm text-gray-700 space-y-2">
            <p>Each invoice has a <strong>public link</strong> that you can share directly with your customer. They can view and download the invoice PDF without needing a NaijaBooks account.</p>
            <p>Find the link on the invoice detail page — it's safe to share via email or WhatsApp.</p>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
