@extends('layouts.app')
@section('page-title', 'Accounting Firms & Clients – Help')

@section('content')
<div class="max-w-3xl space-y-6">
    @include('help.partials.header', [
        'why' => 'If you are an accountant or bookkeeper who looks after the books of other companies, this lets you manage all of them from one login, with each client\'s records kept completely separate. If you are a business owner, you can use the same feature to give your accountant access to your books.',
        'who' => 'Admins and Accountants (the firm side). Only an Admin can invite or revoke an accounting partner (the client side).',
    ])

    @component('help.partials.section', ['heading' => 'A worked example'])
        <p><strong>Adaeze Okoye &amp; Co.</strong> is a small accounting firm. It manages <strong>Bola's Bakery Ltd</strong>, <strong>Sunrise Logistics</strong> and <strong>Eko Fashion House</strong>.</p>
        <ul class="list-disc list-inside space-y-1 pl-2">
            <li>On Monday Adaeze opens <strong>Clients</strong>, clicks <em>Enter Workspace</em> next to Bola's Bakery, and records the week's expenses and invoices.</li>
            <li>She leaves that workspace and enters Sunrise Logistics to run payroll.</li>
            <li>Then she enters Eko Fashion House to compute its VAT return.</li>
        </ul>
        <p class="text-xs text-gray-500">She never logs out, and nothing from one company shows up in another. Each owner can still log in to see their own books.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Adding a client: three ways'])
        <div class="space-y-3">
            <div>
                <p class="font-semibold text-gray-800">1. Create a new client (the company is not on AccountTaxNG yet)</p>
                <ol class="list-decimal list-inside space-y-1 pl-2 mt-1">
                    <li>Go to <strong>Clients → + Create a New Client</strong></li>
                    <li>Enter the company name and email, then the client's admin name, email and password</li>
                    <li>Click <strong>Create Client &amp; Link Access</strong>. The client is linked to your firm immediately, with no approval step.</li>
                </ol>
            </div>
            <div>
                <p class="font-semibold text-gray-800">2. Invite an existing company by email</p>
                <p>Click <strong>Invite a Client by Email</strong> and enter the email of the company's admin. They receive a message and must approve before you get access. While you wait, the request shows under <em>Awaiting Their Approval</em>, and you can cancel it.</p>
            </div>
            <div>
                <p class="font-semibold text-gray-800">3. The client invites you with your partner code</p>
                <p>Your <strong>partner code</strong> is shown on the Clients page. Give it to the client. They enter it under <strong>Settings → Accounting Partners</strong>. The request then appears under <em>Incoming Requests</em> on your Clients page, where you click <strong>Approve</strong> or <strong>Decline</strong>.</p>
            </div>
        </div>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Working inside a client\'s workspace'])
        <ul class="list-disc list-inside space-y-1 pl-2">
            <li>Click <strong>Enter Workspace</strong> next to the client. You are now working in that company's books.</li>
            <li>When you are done, click <strong>Exit Client Workspace</strong> in the banner at the top to return to your own firm.</li>
            <li>Firm access covers bookkeeping: invoices, expenses and reports. It does not include billing, team management or company settings.</li>
        </ul>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Ending access'])
        <p>Either side can end the link at any time. A firm clicks <strong>Revoke</strong> on the Clients page. A client's admin clicks <strong>Revoke</strong> under <strong>Settings → Accounting Partners</strong>. The client's data is untouched. Only the access ends.</p>
    @endcomponent

    @include('help.partials.footer')
</div>
@endsection
