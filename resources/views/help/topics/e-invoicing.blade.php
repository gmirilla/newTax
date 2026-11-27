@extends('layouts.app')
@section('page-title', 'NRS e-Invoicing – Help')

@section('content')
<div class="max-w-3xl space-y-6">
    @include('help.partials.header', [
        'why' => 'The Nigeria Revenue Service (NRS) is rolling out national e-invoicing. If your business must submit invoices to the NRS, AccountTaxNG can send them from the invoice itself, so you do not enter them twice.',
        'who' => 'Admins, on plans that include e-invoicing.',
    ])

    @component('help.partials.section', ['heading' => 'Connect your NRS credentials'])
        <ol class="list-decimal list-inside space-y-1 pl-2">
            <li>Go to <strong>Settings → NRS e-Invoicing</strong></li>
            <li>Enter the credentials supplied by your NRS Access Provider (for example your Service ID)</li>
            <li>Save. Values are encrypted. To change them later, re-enter them, or leave a field blank to keep the existing value.</li>
        </ol>
        <p class="text-xs text-gray-500">Your Access Provider may charge for submissions. That charge is separate from your AccountTaxNG plan.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Submit an invoice'])
        <p>Open an invoice and use the <strong>Submit to NRS</strong> action. The invoice must be complete, including your company's TIN. You can turn e-invoicing off again from the same settings page.</p>
    @endcomponent

    @include('help.partials.footer')
</div>
@endsection
