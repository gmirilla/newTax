@extends('layouts.app')
@section('page-title', 'Bank Accounts – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Bank Accounts</h1>
        <p class="text-sm text-gray-500 mt-1">Connect your business bank accounts so payments flow into the right place in your books.</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Adding a Bank Account</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Go to <strong>Settings → Bank Accounts</strong></li>
                <li>Click <strong>Add Bank Account</strong></li>
                <li>Enter the account name (e.g. "GTBank Current"), bank name, and account number</li>
                <li>Select the account type: Current, Savings, or Other</li>
                <li>Enter the <strong>Opening Balance</strong> — the amount in the account on the day you started using NaijaBooks</li>
                <li>Click <strong>Save</strong></li>
            </ol>
            <div class="bg-amber-50 border border-amber-200 rounded p-3 text-amber-800 text-xs">
                <strong>Opening Balance is important.</strong> It ensures your Balance Sheet correctly reflects how much cash you have. If you leave it at ₦0 but you already have ₦500,000 in the account, your Balance Sheet will show the wrong figure.
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Multiple Bank Accounts</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>You can add as many bank accounts as your business operates. The first account you add becomes the <strong>default</strong> — it is pre-selected when recording invoice payments.</p>
            <p>To change the default, open the account and click <strong>Set as Default</strong>.</p>
            <p>Common setups:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Main operating current account (GTBank, Access, Zenith, etc.)</li>
                <li>Savings / reserve account</li>
                <li>Dedicated payroll account</li>
                <li>Dollar / domiciliary account (still tracked in ₦ equivalent)</li>
            </ul>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">How Bank Accounts Affect Your Reports</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Each bank account is linked to a <strong>GL account</strong> (General Ledger account) in the Assets section of your chart of accounts. This means:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Your <strong>Balance Sheet</strong> shows each bank account's current balance under Assets</li>
                <li>Every payment recorded to a bank account updates that account's balance automatically</li>
                <li>The <strong>General Ledger</strong> shows every transaction that passed through the account</li>
            </ul>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Deactivating vs Deleting a Bank Account</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p><strong>Deactivate</strong> an account if you closed it but it has transaction history — this preserves your records while hiding it from active use.</p>
            <p><strong>Delete</strong> is only possible for accounts that have never had a transaction recorded. Deleting is permanent.</p>
            <div class="bg-blue-50 border border-blue-200 rounded p-3 text-blue-800 text-xs">
                If you try to delete a bank account that has journal entries, you'll get an error. Deactivate it instead using the Edit option.
            </div>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
