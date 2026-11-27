@extends('layouts.app')
@section('page-title', 'Expenses & Vendors – Help')

@section('content')
<div class="max-w-3xl space-y-6">
    @include('help.partials.header', [
        'why' => 'Recording what the business spends keeps your profit figure honest and lets you claim the input VAT you paid. It also records the withholding tax you must deduct from certain vendors.',
        'who' => 'Admins and Accountants.',
    ])

    @component('help.partials.section', ['heading' => 'Record an expense'])
        <ol class="list-decimal list-inside space-y-1 pl-2">
            <li>Go to <strong>Sales &amp; Finance → Expenses</strong></li>
            <li>Click the button to add an expense and fill in the date, description, amount, category and vendor</li>
            <li>Tick VAT if the vendor charged it, and WHT if you are deducting it from the payment</li>
            <li>Save. The matching journal entry is posted for you.</li>
        </ol>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Vendors'])
        <p>Add the people and companies you buy from under <strong>Vendors</strong>. Choosing a vendor on an expense keeps spending history in one place. Use the search box on the Expenses page to find an expense by description, reference or vendor, and the date filters to narrow the period.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Correcting a mistake'])
        <p>Open the expense and use <strong>Edit</strong> to fix it. Changes are recorded in the <a class="text-green-700 underline" href="{{ route('help.show', 'activity-log') }}">Activity Log</a>.</p>
    @endcomponent

    @include('help.partials.footer')
</div>
@endsection
