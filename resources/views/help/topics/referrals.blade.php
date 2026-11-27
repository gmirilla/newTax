@extends('layouts.app')
@section('page-title', 'Referrals – Help')

@section('content')
<div class="max-w-3xl space-y-6">
    @include('help.partials.header', [
        'why' => 'Recommend AccountTaxNG to another business and earn account credit that reduces your own subscription cost.',
        'who' => 'Admins.',
    ])

    @component('help.partials.section', ['heading' => 'How it works'])
        <ol class="list-decimal list-inside space-y-1 pl-2">
            <li>Open the Referrals page and copy your personal referral link</li>
            <li>Send it to a business owner you know</li>
            <li>When they sign up with your link <em>and</em> make their first subscription payment, you earn ₦{{ number_format(\App\Models\Referral::REWARD_NGN, 0) }} in credit (up to ₦{{ number_format(\App\Models\Referral::MAX_CREDIT_NGN, 0) }} in total)</li>
            <li>Your credit is applied automatically at your next subscription payment</li>
        </ol>
        <p class="text-xs text-gray-500">More about credits and plans: <a class="text-green-700 underline" href="{{ route('help.show', 'billing') }}">Billing &amp; Subscription</a>.</p>
    @endcomponent

    @include('help.partials.footer')
</div>
@endsection
