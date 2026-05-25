@extends('layouts.app')
@section('page-title', 'Referrals & Credits')

@section('content')
<div class="space-y-6 max-w-3xl">

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold text-gray-900">Referrals & Credits</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Share your referral link. Earn <strong>₦{{ number_format(\App\Models\Referral::REWARD_NGN, 0) }}</strong>
            credit each time someone signs up and makes their first payment.
            Credit is applied automatically to your next subscription renewal.
        </p>
    </div>

    {{-- Credit balance + referral link --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Available Credit</p>
            <p class="text-3xl font-bold text-indigo-600">
                ₦{{ number_format($tenant->referral_credit_ngn, 2) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Applied automatically at your next renewal
                (max balance ₦{{ number_format(\App\Models\Referral::MAX_CREDIT_NGN, 0) }})
            </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Your Referral Link</p>
            <div class="flex items-center gap-2">
                <input id="reflink" type="text" readonly
                       value="{{ $tenant->referralLink() }}"
                       class="flex-1 text-xs rounded-lg border-gray-200 bg-gray-50 text-gray-700 focus:ring-0 focus:border-gray-300">
                <button onclick="copyRef()"
                        class="px-3 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 whitespace-nowrap">
                    Copy
                </button>
            </div>
            <p class="text-xs text-gray-400">
                Share this link. When someone registers and pays for the first time, you earn credit.
            </p>
        </div>
    </div>

    {{-- Referral list --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                Your Referrals
                <span class="ml-2 font-normal text-gray-400">({{ $referrals->count() }} total)</span>
            </h2>
        </div>

        @if($referrals->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-gray-400">
            No referrals yet. Share your link to get started.
        </div>
        @else
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach($referrals as $ref)
                <tr>
                    <td class="px-5 py-3">
                        <p class="text-sm font-medium text-gray-900">{{ $ref->referee->name }}</p>
                        <p class="text-xs text-gray-400">{{ $ref->referee->email }}</p>
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-500">
                        {{ $ref->created_at->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3">
                        @if($ref->status === 'rewarded')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                Credit earned
                            </span>
                        @elseif($ref->status === 'qualified')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                Qualified
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                Pending first payment
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right text-sm font-medium">
                        @if($ref->status === 'rewarded')
                            <span class="text-green-700">+₦{{ number_format($ref->reward_ngn, 0) }}</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Credit history --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Credit History</h2>
        </div>

        @if($ledger->isEmpty())
        <div class="px-5 py-8 text-center text-sm text-gray-400">No credit activity yet.</div>
        @else
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach($ledger as $entry)
                <tr>
                    <td class="px-5 py-3 text-sm text-gray-500 whitespace-nowrap">
                        {{ $entry->created_at->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-700">{{ $entry->description }}</td>
                    <td class="px-5 py-3 text-right text-sm font-medium {{ $entry->type === 'credit' ? 'text-green-700' : 'text-red-600' }}">
                        {{ $entry->type === 'credit' ? '+' : '−' }}₦{{ number_format($entry->amount_ngn, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>

<script>
function copyRef() {
    const el = document.getElementById('reflink');
    el.select();
    navigator.clipboard?.writeText(el.value) ?? document.execCommand('copy');
    const btn = el.nextElementSibling;
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy', 2000);
}
</script>
@endsection
