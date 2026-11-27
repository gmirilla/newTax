@extends('layouts.app')
@section('page-title', 'Tax & Compliance – Help')

@section('content')
<div class="max-w-3xl space-y-6">
    @include('help.partials.header', [
        'why' => 'Nigerian businesses owe VAT, withholding tax (WHT) and company income tax (CIT). AccountTaxNG works these out from the invoices and expenses you already record, so filing day is a review rather than a recalculation.',
        'who' => 'Admins and Accountants.',
    ])

    @component('help.partials.section', ['heading' => 'Where to find it'])
        <p>Open <strong>Tax &amp; Compliance</strong> in the sidebar. <strong>Overview</strong> shows year-to-date position and any overdue returns. <strong>VAT Returns</strong>, <strong>WHT Records</strong> and <strong>CIT</strong> each have their own page.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'VAT returns (7.5%)'])
        <ol class="list-decimal list-inside space-y-1 pl-2">
            <li>Go to <strong>VAT Returns</strong> and click the compute button.</li>
            <li>Pick the period. AccountTaxNG adds up output VAT (charged on invoices) and input VAT (paid on expenses).</li>
            <li>Review the net amount payable, then save the return.</li>
            <li>After filing with the NRS, update its status (filed, paid) so the overview stays accurate.</li>
        </ol>
        <p class="text-xs text-gray-500">Only add VAT to invoices if your business is VAT-registered. You can export the list of returns as CSV.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Withholding tax (WHT)'])
        <p>WHT is recorded when you tick WHT on an invoice or an expense. The <strong>WHT Records</strong> page totals gross, WHT and net amounts, breaks them down by transaction type, and shows a rate reference (for example 5% on contracts and professional services for companies).</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Company income tax (CIT)'])
        <ul class="list-disc list-inside space-y-1 pl-2">
            <li>Small companies (turnover up to ₦50 million) are exempt from CIT and the development levy.</li>
            <li>Larger companies, and professional firms, pay CIT plus a 4% development levy on assessable profit.</li>
            <li>A minimum tax applies when the company has a loss or the computed tax is lower.</li>
        </ul>
        <p class="text-xs text-gray-500">The CIT page shows which band your turnover falls in. The exact rates are on our public Tax Rules page.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Tips'])
        <ul class="list-disc list-inside space-y-1 pl-2">
            <li>Record expenses promptly. Input VAT you forget to record is VAT you cannot claim.</li>
            <li>Check the <strong>Tax Summary</strong> report before filing. See <a class="text-green-700 underline" href="{{ route('help.show', 'reports') }}">Financial Reports</a>.</li>
        </ul>
    @endcomponent

    @include('help.partials.footer')
</div>
@endsection
