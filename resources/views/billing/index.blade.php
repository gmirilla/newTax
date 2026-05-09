@extends('layouts.app')

@section('page-title', 'Billing & Plan')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{ cancelModal: false, cycle: 'monthly' }">

    {{-- Upgrade prompt banner (shown when redirected from a locked feature) --}}
    @if($upgradeFeature)
    @php
        $featureLabels = [
            'payroll'          => 'Payroll & PAYE',
            'firs'             => 'NRS e-Invoicing',
            'advanced_reports' => 'Advanced Reports',
            'api_access'       => 'API Access',
        ];
        $featureLabel = $featureLabels[$upgradeFeature] ?? ucfirst($upgradeFeature);
    @endphp
    <div class="bg-amber-50 border border-amber-300 rounded-lg px-5 py-4 flex items-start gap-3">
        <span class="text-2xl">🔒</span>
        <div>
            <p class="font-semibold text-amber-800">{{ $featureLabel }} requires an upgrade</p>
            <p class="text-sm text-amber-700 mt-0.5">
                Your current plan <strong>{{ $tenant->plan?->name ?? 'Free' }}</strong> does not include {{ $featureLabel }}.
                Upgrade to Growth or Business to unlock it.
            </p>
        </div>
    </div>
    @endif

    {{-- Pending plan change notice --}}
    @if($tenant->hasPendingPlanChange())
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-5 py-4 flex items-start gap-3">
        <span class="text-xl leading-tight mt-0.5">🔄</span>
        <div class="flex-1">
            <p class="font-semibold text-blue-800">Plan change scheduled</p>
            <p class="text-sm text-blue-700 mt-0.5">
                Your plan will switch to <strong>{{ $tenant->nextPlan?->name ?? 'Free' }}</strong>
                on {{ $tenant->subscription_expires_at?->format('d M Y') ?? 'end of current period' }}.
                You retain full {{ $tenant->plan?->name }} access until then.
            </p>
        </div>
        @if($tenant->plan && $tenant->plan->price_monthly > 0)
        <a href="{{ route('billing.checkout', $tenant->plan) }}?cycle={{ $tenant->billing_cycle ?? 'monthly' }}"
           class="shrink-0 text-xs font-medium text-blue-700 underline hover:text-blue-900 mt-0.5">
            Keep current plan
        </a>
        @endif
    </div>
    @endif

    {{-- Current plan + usage meters --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        {{-- Plan card --}}
        <div class="bg-white rounded-lg shadow p-5 space-y-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Current Plan</p>
                <h2 class="text-2xl font-bold text-green-700 mt-1">{{ $tenant->plan?->name ?? 'No Plan' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $tenant->plan?->priceLabel() ?? '—' }}
                    @if(($tenant->billing_cycle ?? 'monthly') === 'yearly')
                    <span class="ml-1 text-xs text-green-600 font-medium">· Annual</span>
                    @endif
                </p>
            </div>

            @php
                $inGrace       = $tenant->isInGracePeriod();
                $displayStatus = $inGrace ? 'grace' : ($tenant->subscription_status ?? '');
                $statusColour  = match($displayStatus) {
                    'active'    => 'bg-green-100 text-green-700',
                    'trialing'  => 'bg-blue-100 text-blue-700',
                    'suspended' => 'bg-yellow-100 text-yellow-700',
                    'cancelled' => 'bg-red-100 text-red-600',
                    'grace'     => 'bg-orange-100 text-orange-700',
                    default     => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColour }}">
                {{ $inGrace ? 'Grace Period' : ucfirst($displayStatus ?: 'No subscription') }}
            </span>

            @if($tenant->isOnTrial())
            <p class="text-sm text-blue-600">
                Trial ends {{ $tenant->trial_ends_at->diffForHumans() }}
                ({{ $tenant->trial_ends_at->format('d M Y') }})
            </p>
            @elseif($tenant->subscription_expires_at)
            @php $expired = $tenant->subscription_expires_at->isPast(); @endphp
            <p class="text-sm {{ $expired ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                @if($expired)
                    Expired {{ $tenant->subscription_expires_at->format('d M Y') }}
                @elseif($tenant->subscription_status === 'cancelled')
                    Access until {{ $tenant->subscription_expires_at->format('d M Y') }}
                @else
                    Renews {{ $tenant->subscription_expires_at->format('d M Y') }}
                @endif
            </p>
            @endif

            {{-- Cancel subscription link --}}
            @php
                $canShowCancel = $tenant->subscriptionActive()
                    && !$tenant->isOnTrial()
                    && ($tenant->plan?->price_monthly ?? 0) > 0
                    && $tenant->subscription_status !== 'cancelled'
                    && !$tenant->hasPendingPlanChange();
            @endphp
            @if($canShowCancel)
            <div class="pt-2 border-t border-gray-100">
                <button @click="cancelModal = true"
                        class="text-xs text-red-500 hover:text-red-700 underline">
                    Cancel subscription
                </button>
            </div>
            @endif
        </div>

        {{-- Usage meters --}}
        @php
            $meters = [
                ['label' => 'Invoices this month', 'used' => $usage['invoices_this_month'], 'limit' => $tenant->plan?->limit('invoices_per_month')],
                ['label' => 'Team members',        'used' => $usage['users'],               'limit' => $tenant->plan?->limit('users')],
                ['label' => 'Payroll staff',       'used' => $usage['payroll_staff'],       'limit' => $tenant->plan?->limit('payroll_staff')],
            ];
        @endphp
        @foreach($meters as $meter)
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">{{ $meter['label'] }}</p>
            <div class="mt-2 flex items-end gap-2">
                <span class="text-3xl font-bold text-gray-800">{{ $meter['used'] }}</span>
                <span class="text-sm text-gray-400 mb-1">
                    / {{ $meter['limit'] === null ? '∞' : ($meter['limit'] === 0 ? 'Disabled' : $meter['limit']) }}
                </span>
            </div>
            @if($meter['limit'] !== null && $meter['limit'] > 0)
            @php $pct = min(100, round($meter['used'] / $meter['limit'] * 100)); @endphp
            <div class="mt-3 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-amber-400' : 'bg-green-500') }}"
                     style="width: {{ $pct }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ $pct }}% used</p>
            @elseif($meter['limit'] === 0)
            <p class="text-xs text-red-500 mt-2">Not included in your plan</p>
            @else
            <p class="text-xs text-green-600 mt-2">Unlimited</p>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Plan comparison --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Available Plans</h3>
                <p class="text-sm text-gray-500 mt-0.5">
                    Upgrades take effect immediately (prorated charge). Downgrades apply at end of billing cycle.
                </p>
            </div>
            {{-- Billing cycle toggle --}}
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1 self-start sm:self-auto shrink-0">
                <button @click="cycle = 'monthly'"
                        :class="cycle === 'monthly' ? 'bg-white shadow text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-1.5 rounded-md text-sm transition-all">
                    Monthly
                </button>
                <button @click="cycle = 'yearly'"
                        :class="cycle === 'yearly' ? 'bg-white shadow text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-1.5 rounded-md text-sm transition-all flex items-center gap-1.5">
                    Annual
                    <span class="text-xs font-bold text-green-700 bg-green-100 px-1.5 py-0.5 rounded-full">Save 20%</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-{{ $plans->count() }} divide-y md:divide-y-0 md:divide-x divide-gray-100">
            @foreach($plans as $plan)
            @php
                $isCurrent       = $tenant->plan_id === $plan->id;
                $isPending       = $tenant->next_plan_id === $plan->id;
                $currentPrice    = $tenant->plan?->price_monthly ?? 0;
                $isFree          = $plan->slug === 'free';
                $isEnterprise    = $plan->price_monthly == 0 && !$isFree;
                $isUpgrade       = !$isCurrent && !$isEnterprise && $plan->price_monthly > $currentPrice;
                $isDowngrade     = !$isCurrent && !$isPending && $plan->price_monthly > 0 && $plan->price_monthly < $currentPrice;
                $isFreeDowngrade = $isFree && !$isCurrent && !$isPending
                    && $tenant->subscriptionActive()
                    && !$tenant->isOnTrial()
                    && $currentPrice > 0
                    && $tenant->subscription_status !== 'cancelled';
            @endphp
            <div class="p-6 {{ $isCurrent ? 'bg-green-50' : '' }} flex flex-col">

                {{-- Plan header --}}
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="text-base font-bold text-gray-900">{{ $plan->name }}</h4>
                            @if($isCurrent)
                            <span class="text-xs bg-green-600 text-white px-2 py-0.5 rounded-full font-medium">Current</span>
                            @elseif($isPending)
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">Pending</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $plan->description }}</p>
                    </div>
                    <div class="text-right shrink-0 ml-2">
                        @if($plan->price_monthly > 0 && $plan->price_yearly)
                        {{-- Monthly price --}}
                        <div x-show="cycle === 'monthly'"
                             class="text-xl font-bold {{ $isCurrent ? 'text-green-700' : 'text-gray-800' }}">
                            ₦{{ number_format($plan->price_monthly, 0) }}<span class="text-sm font-normal text-gray-400">/mo</span>
                        </div>
                        {{-- Annual price —  show monthly equivalent --}}
                        <div x-show="cycle === 'yearly'" x-cloak>
                            <div class="text-xl font-bold {{ $isCurrent ? 'text-green-700' : 'text-gray-800' }}">
                                ₦{{ number_format($plan->yearlyMonthlyEquivalent(), 0) }}<span class="text-sm font-normal text-gray-400">/mo</span>
                            </div>
                            <div class="text-xs text-gray-400">₦{{ number_format($plan->price_yearly, 0) }} billed yearly</div>
                        </div>
                        @else
                        <div class="text-xl font-bold {{ $isCurrent ? 'text-green-700' : 'text-gray-800' }}">
                            {{ $plan->priceLabel() }}
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Limits & features --}}
                @php $limits = $plan->limits ?? []; @endphp
                <ul class="space-y-2 text-sm flex-1 mb-5">
                    @php
                        $limitLines = [
                            'invoices_per_month' => 'invoices/month',
                            'users'              => 'team members',
                            'payroll_staff'      => 'payroll staff',
                            'customers'          => 'customers',
                        ];
                        $featureLines = [
                            'payroll'          => 'Payroll & PAYE',
                            'firs'             => 'NRS e-Invoicing',
                            'advanced_reports' => 'Advanced Reports',
                            'api_access'       => 'API Access',
                        ];
                    @endphp
                    @foreach($limitLines as $key => $label)
                    <li class="flex items-center gap-2 text-gray-600">
                        <span class="text-green-500">✓</span>
                        @if(($limits[$key] ?? null) === null)
                            Unlimited {{ $label }}
                        @elseif(($limits[$key] ?? 0) === 0)
                            <span class="line-through text-gray-400">{{ ucfirst($label) }}</span>
                        @else
                            {{ $limits[$key] }} {{ $label }}
                        @endif
                    </li>
                    @endforeach
                    @foreach($featureLines as $key => $label)
                    <li class="flex items-center gap-2 {{ ($limits[$key] ?? false) ? 'text-gray-600' : 'text-gray-300' }}">
                        <span>{{ ($limits[$key] ?? false) ? '✓' : '✗' }}</span>
                        {{ $label }}
                    </li>
                    @endforeach
                    @if($plan->trial_days > 0)
                    <li class="flex items-center gap-2 text-blue-600 text-xs mt-1">
                        <span>🎁</span> {{ $plan->trial_days }}-day free trial
                    </li>
                    @endif
                </ul>

                {{-- CTA --}}
                @if($isCurrent)
                    <button disabled
                            class="w-full py-2 text-sm font-medium rounded-md bg-green-100 text-green-700 cursor-default">
                        Current Plan
                    </button>

                @elseif($isPending)
                    <button disabled
                            class="w-full py-2 text-sm font-medium rounded-md bg-blue-50 text-blue-500 cursor-default">
                        Switching {{ $tenant->subscription_expires_at?->format('d M') ?? 'at expiry' }}
                    </button>

                @elseif($isEnterprise)
                    <a href="mailto:hello@accounttaxng.com?subject=Enterprise+enquiry"
                       class="w-full block text-center py-2 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50 text-gray-700 transition-colors">
                        Contact Sales
                    </a>

                @elseif($isUpgrade)
                    <a :href="'{{ route('billing.checkout', $plan) }}?cycle=' + cycle"
                       class="w-full block text-center py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700 transition-colors">
                        Upgrade to {{ $plan->name }} →
                    </a>

                @elseif($isDowngrade)
                    <form method="POST" action="{{ route('billing.downgrade', $plan) }}"
                          onsubmit="return confirm('Downgrade to {{ addslashes($plan->name) }}? You keep your current features until {{ addslashes($tenant->subscription_expires_at?->format('d M Y') ?? 'end of period') }}.')">
                        @csrf
                        <button type="submit"
                                class="w-full py-2 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50 text-gray-700 transition-colors">
                            Downgrade to {{ $plan->name }}
                        </button>
                    </form>

                @elseif($isFreeDowngrade)
                    <button @click="cancelModal = true"
                            class="w-full py-2 text-sm font-medium rounded-md border border-gray-300 hover:bg-red-50 hover:border-red-200 text-gray-600 hover:text-red-600 transition-colors">
                        Downgrade to Free
                    </button>

                @else
                    <button disabled
                            class="w-full py-2 text-sm font-medium rounded-md bg-gray-50 text-gray-300 cursor-default">
                        —
                    </button>
                @endif

            </div>
            @endforeach
        </div>
    </div>

    {{-- Payment history --}}
    @if($payments->isNotEmpty())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="text-base font-semibold text-gray-800">Payment History</h3>
            <p class="text-sm text-gray-500 mt-0.5">Last 12 payments</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold">Date</th>
                        <th class="px-6 py-3 text-left font-semibold">Plan</th>
                        <th class="px-6 py-3 text-left font-semibold">Type</th>
                        <th class="px-6 py-3 text-right font-semibold">Amount</th>
                        <th class="px-6 py-3 text-center font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-600 whitespace-nowrap">
                            {{ $payment->paid_at?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="px-6 py-3 text-gray-800 font-medium">
                            {{ $payment->plan?->name ?? '—' }}
                        </td>
                        <td class="px-6 py-3 text-gray-500">
                            {{ $payment->typeLabel() }}
                        </td>
                        <td class="px-6 py-3 text-right font-semibold text-gray-800 whitespace-nowrap">
                            ₦{{ number_format($payment->amount, 2) }}
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if($payment->status === 'success')
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Paid</span>
                            @else
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

{{-- Cancel subscription modal --}}
<div x-show="cancelModal" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     @keydown.escape.window="cancelModal = false">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6" @click.outside="cancelModal = false">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Cancel Subscription?</h3>
        <p class="text-sm text-gray-600 mb-1">
            Your subscription will be cancelled at the end of your current billing period
            @if($tenant->subscription_expires_at)
                (<strong>{{ $tenant->subscription_expires_at->format('d M Y') }}</strong>).
            @else
                .
            @endif
        </p>
        <p class="text-sm text-gray-500 mb-5">
            You will keep full access to <strong>{{ $tenant->plan?->name }}</strong> until then,
            after which your account moves to the Free plan.
        </p>
        <div class="flex gap-3 justify-end">
            <button @click="cancelModal = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                Keep Subscription
            </button>
            <form method="POST" action="{{ route('billing.cancel') }}">
                @csrf
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors">
                    Yes, Cancel
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
