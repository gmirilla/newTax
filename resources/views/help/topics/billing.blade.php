@extends('layouts.app')
@section('page-title', 'Billing & Subscription – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Billing & Subscription</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your plan, make payments, and use referral credits.</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Your Current Plan</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Go to <strong>Settings → Billing & Plan</strong> to see:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Your current plan and what features it includes</li>
                <li>Your subscription renewal date</li>
                <li>Your referral credit balance (if any)</li>
                <li>Payment history</li>
            </ul>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Upgrading Your Plan</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Go to <strong>Settings → Billing & Plan</strong></li>
                <li>Click <strong>Upgrade</strong> next to the plan you want</li>
                <li>Review the amount (any referral credits are applied automatically)</li>
                <li>Complete payment via Paystack — supports cards, bank transfer, and USSD</li>
            </ol>
            <p>Your plan upgrades immediately after payment is confirmed. You won't lose any data when upgrading.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Referral Program</h2>
        </div>
        <div class="p-5 space-y-4 text-sm text-gray-700">
            <p>Earn <strong>₦2,000</strong> in account credit for every business you refer to NaijaBooks, up to a maximum of <strong>₦20,000</strong> in credits.</p>
            <div>
                <p class="font-medium mb-1">How it works:</p>
                <ol class="list-decimal list-inside space-y-1 pl-2">
                    <li>Go to <strong>Settings → Referrals & Credits</strong></li>
                    <li>Copy your unique referral link</li>
                    <li>Share with business owners you know</li>
                    <li>When they sign up using your link <em>and</em> make their first subscription payment, ₦2,000 is credited to your account</li>
                    <li>Your credit is automatically applied the next time you renew your subscription</li>
                </ol>
            </div>
            <div class="bg-green-50 border border-green-200 rounded p-3 text-green-800 text-xs">
                <strong>Credit carry-over:</strong> If your credit balance exceeds your renewal amount, the remaining credit is kept for your next renewal. Credits never expire.
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Payment Methods</h2>
        </div>
        <div class="p-5 space-y-2 text-sm text-gray-700">
            <p>All payments are processed securely by <strong>Paystack</strong>. Accepted methods:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Debit / credit card (Mastercard, Visa, Verve)</li>
                <li>Bank transfer</li>
                <li>USSD (all major Nigerian banks)</li>
            </ul>
            <p class="text-xs text-gray-500">NaijaBooks does not store your card details — Paystack handles all payment security.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Cancelling Your Subscription</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>You can cancel at any time from <strong>Settings → Billing & Plan</strong>. After cancellation:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Your access continues until the end of the current billing period</li>
                <li>All your data is retained for 90 days after expiry</li>
                <li>You can reactivate at any time without losing data</li>
            </ul>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
