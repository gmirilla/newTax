@extends('layouts.app')
@section('page-title', 'Financial Reports – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Financial Reports</h1>
        <p class="text-sm text-gray-500 mt-1">Understand your Profit & Loss, Balance Sheet, Trial Balance, General Ledger, and Tax Summary.</p>
    </div>

    {{-- P&L --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Profit & Loss (Income Statement)</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>The P&L shows how much money your business made or lost over a period. Go to <strong>Reports → Profit & Loss</strong> and select a date range.</p>
            <div class="bg-gray-50 rounded p-3 text-xs font-mono space-y-1">
                <p class="text-green-700 font-bold">Revenue</p>
                <p class="pl-4">Sales Revenue ........................ ₦X</p>
                <p class="text-red-700 font-bold mt-1">Less: Cost of Goods Sold</p>
                <p class="pl-4">COGS .................................. ₦X</p>
                <p class="text-blue-700 font-bold mt-1">Gross Profit .......................... ₦X</p>
                <p class="text-red-700 font-bold mt-1">Less: Operating Expenses</p>
                <p class="pl-4">Salaries .............................. ₦X</p>
                <p class="pl-4">Rent .................................. ₦X</p>
                <p class="font-bold mt-1">Net Profit / (Loss) ................... ₦X</p>
            </div>
            <ul class="list-disc list-inside space-y-1 pl-2 text-xs text-gray-500">
                <li>Positive net profit = your business is making money</li>
                <li>Negative net profit (loss) = expenses exceed income</li>
            </ul>
        </div>
    </div>

    {{-- Balance Sheet --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Balance Sheet</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>The Balance Sheet shows what your business <em>owns</em> (assets) and <em>owes</em> (liabilities), and the owner's stake (equity) at a specific point in time.</p>
            <div class="bg-gray-50 rounded p-3 text-xs font-mono space-y-1">
                <p class="text-green-700 font-bold">Assets</p>
                <p class="pl-4">Bank Accounts ........................ ₦X</p>
                <p class="pl-4">Accounts Receivable .................. ₦X</p>
                <p class="pl-4">Inventory ............................ ₦X</p>
                <p class="text-red-700 font-bold mt-1">Liabilities</p>
                <p class="pl-4">Accounts Payable ..................... ₦X</p>
                <p class="pl-4">VAT Payable .......................... ₦X</p>
                <p class="text-blue-700 font-bold mt-1">Equity</p>
                <p class="pl-4">Owner's Equity ....................... ₦X</p>
            </div>
            <p class="text-xs font-medium text-gray-800">Assets must always equal Liabilities + Equity. If they don't, check your opening balances.</p>
        </div>
    </div>

    {{-- Trial Balance --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Trial Balance</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>The Trial Balance lists every account with its total debits and credits for a period. It is used by accountants to verify that the books are balanced — total debits must equal total credits.</p>
            <p class="text-xs text-gray-500">If you share books with an external auditor or FIRS officer, the Trial Balance is one of the first documents they will request.</p>
        </div>
    </div>

    {{-- General Ledger --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">General Ledger</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>The General Ledger shows every transaction that affected a specific account, with running balances. Use it to:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Trace where a specific amount came from</li>
                <li>Verify a bank account balance transaction by transaction</li>
                <li>Review all VAT or PAYE postings</li>
            </ul>
            <p>Filter by account code and date range in <strong>Reports → General Ledger</strong>.</p>
        </div>
    </div>

    {{-- Tax Summary --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Tax Summary</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>The Tax Summary gives a period-by-period breakdown of:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li><strong>VAT collected</strong> from customers on your sales invoices</li>
                <li><strong>WHT deducted</strong> by customers on payments to you</li>
                <li><strong>PAYE</strong> deducted from employees (if payroll is active)</li>
            </ul>
            <p class="text-xs text-gray-500">Use this report to prepare your monthly VAT returns and quarterly WHT filings with FIRS.</p>
        </div>
    </div>

    {{-- Inventory Reports --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800">Inventory Reports</h2>
                <span class="text-xs px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full font-bold uppercase">Pro</span>
            </div>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Available under <strong>Reports → Inventory</strong> for tenants on the Business plan or higher:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li><strong>Stock Valuation</strong> — total value of inventory on hand</li>
                <li><strong>Low Stock</strong> — items at or below reorder level</li>
                <li><strong>Slow Moving</strong> — items with low sales velocity over a period</li>
                <li><strong>Fast Moving</strong> — top-selling items by volume and revenue</li>
                <li><strong>Reorder Analysis</strong> — prioritised reorder list based on days remaining and historical demand</li>
            </ul>
            <p class="text-xs text-gray-500">All inventory reports are exportable as PDF and Excel.</p>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
