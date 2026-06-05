@extends('layouts.app')
@section('page-title', 'Bookkeeping – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Bookkeeping</h1>
        <p class="text-sm text-gray-500 mt-1">How NaijaBooks records your financial transactions and keeps your books balanced.</p>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
        NaijaBooks uses <strong>double-entry bookkeeping</strong> — the same system used by accountants worldwide. Every transaction has two sides: a debit and a credit. You don't need to enter these manually; the system does it automatically when you create invoices, record payments, and run payroll.
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Chart of Accounts</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>The <strong>Chart of Accounts</strong> is the complete list of accounts used to categorise every transaction. Go to <strong>Bookkeeping → Chart of Accounts</strong> to view it.</p>
            <p>Accounts are grouped by type:</p>
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left p-2 border border-gray-200 font-semibold">Type</th>
                        <th class="text-left p-2 border border-gray-200 font-semibold">Examples</th>
                        <th class="text-left p-2 border border-gray-200 font-semibold">Where it appears</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="p-2 border border-gray-200 font-medium">Asset</td><td class="p-2 border border-gray-200">Bank accounts, Accounts Receivable, Inventory</td><td class="p-2 border border-gray-200">Balance Sheet</td></tr>
                    <tr class="bg-gray-50"><td class="p-2 border border-gray-200 font-medium">Liability</td><td class="p-2 border border-gray-200">Accounts Payable, VAT Payable, PAYE</td><td class="p-2 border border-gray-200">Balance Sheet</td></tr>
                    <tr><td class="p-2 border border-gray-200 font-medium">Equity</td><td class="p-2 border border-gray-200">Owner's Equity, Retained Earnings</td><td class="p-2 border border-gray-200">Balance Sheet</td></tr>
                    <tr class="bg-gray-50"><td class="p-2 border border-gray-200 font-medium">Revenue</td><td class="p-2 border border-gray-200">Sales Revenue</td><td class="p-2 border border-gray-200">Profit & Loss</td></tr>
                    <tr><td class="p-2 border border-gray-200 font-medium">Expense</td><td class="p-2 border border-gray-200">Salaries, Cost of Goods Sold, Rent</td><td class="p-2 border border-gray-200">Profit & Loss</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">How Transactions Are Recorded Automatically</h2>
        </div>
        <div class="p-5 space-y-4 text-sm text-gray-700">
            <div>
                <p class="font-medium text-gray-800 mb-1">When you create an invoice:</p>
                <ul class="list-disc list-inside space-y-1 pl-2 text-gray-600 text-xs">
                    <li>Debit: Accounts Receivable (money owed to you)</li>
                    <li>Credit: Revenue (income earned)</li>
                    <li>Credit: VAT Payable (if VAT applies)</li>
                </ul>
            </div>
            <div>
                <p class="font-medium text-gray-800 mb-1">When you record a payment:</p>
                <ul class="list-disc list-inside space-y-1 pl-2 text-gray-600 text-xs">
                    <li>Debit: Bank Account (cash received)</li>
                    <li>Credit: Accounts Receivable (clears the debt)</li>
                </ul>
            </div>
            <div>
                <p class="font-medium text-gray-800 mb-1">When you run payroll:</p>
                <ul class="list-disc list-inside space-y-1 pl-2 text-gray-600 text-xs">
                    <li>Debit: Salary Expense</li>
                    <li>Credit: PAYE Payable, Pension Payable, Bank Account</li>
                </ul>
            </div>
            <div class="bg-green-50 border border-green-200 rounded p-3 text-green-800 text-xs">
                You never need to enter journal entries manually for invoices, payments, or payroll. NaijaBooks creates them automatically.
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Viewing Transactions</h2>
        </div>
        <div class="p-5 space-y-2 text-sm text-gray-700">
            <p>Go to <strong>Bookkeeping → Transactions</strong> to see a full list of all posted journal entries. You can filter by date range and account.</p>
            <p>For a per-account view, go to <strong>Reports → General Ledger</strong> — this shows every debit and credit per account with running balances.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Opening Balances</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>When you start using NaijaBooks mid-year, you need to enter <strong>opening balances</strong> for accounts that already have values:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li><strong>Bank accounts</strong> — set when adding the account in Settings</li>
                <li><strong>Inventory</strong> — set per item when adding stock items</li>
            </ul>
            <p class="text-xs text-gray-500">Opening balances ensure your Balance Sheet and P&L are accurate from day one, even if you have pre-existing transactions in another system.</p>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
