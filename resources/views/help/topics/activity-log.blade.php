@extends('layouts.app')
@section('page-title', 'Activity Log – Help')

@section('content')
<div class="max-w-3xl space-y-6">
    @include('help.partials.header', [
        'why' => 'The activity log is the record of who did what and when in your account. Use it to answer "who changed this invoice?", to review approvals, or to show an auditor that your books are controlled.',
        'who' => 'Admins and Accountants.',
    ])

    @component('help.partials.section', ['heading' => 'What is recorded'])
        <ul class="list-disc list-inside space-y-1 pl-2">
            <li>Creating, editing and deleting records such as invoices, expenses and customers</li>
            <li>Approvals, confirmations, completions and cancellations</li>
            <li>When an accounting firm works inside your account</li>
        </ul>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Using it'])
        <p>Open <strong>Activity Log</strong> in the sidebar. Entries are listed newest first and colour-coded by type, so approvals and deletions are easy to spot. Each entry shows who did it and when.</p>
        <p class="text-xs text-gray-500">Entries cannot be edited or removed by users, so the log can be relied on as a history.</p>
    @endcomponent

    @include('help.partials.footer')
</div>
@endsection
