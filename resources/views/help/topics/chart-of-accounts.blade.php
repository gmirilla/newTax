@extends('layouts.app')
@section('page-title', 'Chart of Accounts – Help')

@section('content')
<div class="max-w-3xl space-y-6">
    @include('help.partials.header', [
        'why' => 'The chart of accounts is the list of "buckets" your money is sorted into: what you own, what you owe, what you earn and what you spend. Your reports are built from it. Most businesses never need to change it, but you can add accounts that match how you run your business.',
        'who' => 'Admins (Settings → Chart of Accounts).',
    ])

    @component('help.partials.section', ['heading' => 'Account types and codes'])
        <table class="w-full text-xs border-collapse">
            <thead><tr class="bg-gray-50"><th class="text-left p-2 border border-gray-200">Type</th><th class="text-left p-2 border border-gray-200">Code range</th><th class="text-left p-2 border border-gray-200">Examples</th></tr></thead>
            <tbody>
                <tr><td class="p-2 border border-gray-200">Assets</td><td class="p-2 border border-gray-200">1xxx</td><td class="p-2 border border-gray-200">Bank, Accounts Receivable, Inventory</td></tr>
                <tr class="bg-gray-50"><td class="p-2 border border-gray-200">Liabilities</td><td class="p-2 border border-gray-200">2xxx</td><td class="p-2 border border-gray-200">Accounts Payable, VAT Payable</td></tr>
                <tr><td class="p-2 border border-gray-200">Equity</td><td class="p-2 border border-gray-200">3xxx</td><td class="p-2 border border-gray-200">Owner's Equity</td></tr>
                <tr class="bg-gray-50"><td class="p-2 border border-gray-200">Revenue</td><td class="p-2 border border-gray-200">4xxx</td><td class="p-2 border border-gray-200">Sales Revenue</td></tr>
                <tr><td class="p-2 border border-gray-200">Expenses</td><td class="p-2 border border-gray-200">5xxx</td><td class="p-2 border border-gray-200">Rent, Salaries, Utilities</td></tr>
            </tbody>
        </table>
    @endcomponent

    @component('help.partials.section', ['heading' => 'What you can change'])
        <ul class="list-disc list-inside space-y-1 pl-2">
            <li><strong>Add</strong> a new account when you need a separate line on your reports (for example "Delivery Costs").</li>
            <li><strong>Rename or deactivate</strong> any account you no longer use.</li>
            <li><strong>System accounts</strong> (marked 🔒) can be renamed and deactivated but never recoded or deleted, because the rest of the app relies on their exact code and type.</li>
        </ul>
    @endcomponent

    @include('help.partials.footer')
</div>
@endsection
