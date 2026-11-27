@extends('layouts.app')
@section('page-title', 'Online Storefront – Help')

@section('content')
<div class="max-w-3xl space-y-6">
    @include('help.partials.header', [
        'why' => 'The storefront gives your business its own public shop page where customers can browse your products and services and place orders online. Orders land in your account so you can fulfil and invoice them without re-typing anything.',
        'who' => 'Users given Storefront access, on plans that include it.',
    ])

    @component('help.partials.section', ['heading' => 'Set it up'])
        <ol class="list-decimal list-inside space-y-1 pl-2">
            <li>Go to <strong>Storefront → Settings</strong> and switch the shop on</li>
            <li>Add <strong>Categories</strong> to organise what you sell</li>
            <li>Add <strong>Products</strong> (physical items) and <strong>Services</strong> (things you do for customers)</li>
            <li>Share your shop link with customers</li>
        </ol>
        <p class="text-xs text-gray-500">Your shop address uses your company's short name, in the form <em>yoursite/your-company/shop</em>.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Handling orders'])
        <p>New orders appear under <strong>Storefront → Orders</strong>, and the sidebar shows a count of orders waiting for you. Open an order to review it and update its status as you fulfil it. Customers can check their order status from a link they receive.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'A worked example'])
        <p>Eko Fashion House lists five dresses as products and "Custom tailoring" as a service. A customer opens the shop link, picks a dress and checks out. Eko Fashion House sees the order in <strong>Orders</strong>, prepares it and updates the status.</p>
    @endcomponent

    @include('help.partials.footer')
</div>
@endsection
