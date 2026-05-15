@extends('superadmin.layout')

@section('page-title', $tenant->name)

@section('content')
<div class="space-y-6" x-data="{ showReminder: false, showExtendTrial: false }">

    <div class="flex items-center justify-between">
        <a href="{{ route('superadmin.companies') }}" class="text-sm text-gray-500 hover:text-gray-700">← All Companies</a>
        <div class="flex gap-2">
            {{-- Impersonate --}}
            <form method="POST" action="{{ route('superadmin.companies.impersonate', $tenant) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Log in as this company\'s admin?')"
                        class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    View as Company
                </button>
            </form>
            {{-- Toggle Active --}}
            <form method="POST" action="{{ route('superadmin.companies.toggle', $tenant) }}">
                @csrf
                <button type="submit"
                        class="px-3 py-1.5 text-xs {{ $tenant->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-md">
                    {{ $tenant->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
            <button type="button" @click="showExtendTrial = true"
                    class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Extend Trial
            </button>
            <button type="button" @click="showReminder = true"
                    class="px-3 py-1.5 text-xs bg-yellow-500 text-white rounded-md hover:bg-yellow-600">
                Send Reminder
            </button>
        </div>
    </div>

    {{-- Company Overview --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Details card --}}
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6 space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $tenant->name }}</h2>
                    <p class="text-sm text-gray-500">{{ $tenant->email }} · {{ $tenant->phone }}</p>
                </div>
                <span class="px-2 py-1 text-xs rounded-full font-medium
                    {{ $tenant->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                    {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-medium">TIN (FIRS)</p>
                    <p class="font-medium">{{ $tenant->tin ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-medium">RC Number (CAC)</p>
                    <p class="font-medium">{{ $tenant->rc_number ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-medium">Business Type</p>
                    <p class="font-medium">{{ ucwords(str_replace('_', ' ', $tenant->business_type)) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-medium">Tax Category</p>
                    <p class="font-medium">{{ ucfirst($tenant->tax_category) }}
                        <span class="text-xs text-gray-400">(CIT {{ $tenant->tax_category === 'small' ? '0%' : ($tenant->tax_category === 'medium' ? '20%' : '30%') }})</span>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-medium">VAT Registered</p>
                    <p class="font-medium {{ $tenant->vat_registered ? 'text-green-700' : 'text-gray-500' }}">
                        {{ $tenant->vat_registered ? 'Yes — ' . $tenant->vat_number : 'No' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-medium">Annual Turnover</p>
                    <p class="font-medium">₦{{ number_format($tenant->annual_turnover, 0) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-medium">State</p>
                    <p class="font-medium">{{ $tenant->state ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-medium">Onboarded</p>
                    <p class="font-medium">{{ $tenant->created_at->format('d M Y') }}</p>
                </div>
            </div>

            <div class="text-sm">
                <p class="text-xs text-gray-400 uppercase font-medium">Address</p>
                <p>{{ $tenant->address }}, {{ $tenant->city }}</p>
            </div>
        </div>

        {{-- Subscription card --}}
        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">Subscription</h3>

            {{-- Current plan display --}}
            <div class="text-center py-2">
                @if($tenant->plan)
                    <div class="text-2xl font-bold text-indigo-600">{{ $tenant->plan->name }}</div>
                    <div class="text-sm text-gray-400">{{ $tenant->plan->priceLabel() }}</div>
                @else
                    <div class="text-2xl font-bold text-gray-400">No Plan</div>
                @endif

                @php
                    $inGrace      = $tenant->isInGracePeriod();
                    $displayStatus = $inGrace ? 'grace' : ($tenant->subscription_status ?? 'none');
                    $statusColour = match($displayStatus) {
                        'active'    => 'bg-green-100 text-green-700',
                        'trialing'  => 'bg-blue-100 text-blue-700',
                        'suspended' => 'bg-yellow-100 text-yellow-700',
                        'cancelled' => 'bg-red-100 text-red-600',
                        'grace'     => 'bg-orange-100 text-orange-700',
                        default     => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold rounded-full {{ $statusColour }}">
                    {{ ucfirst($displayStatus) }}
                    @if($inGrace)({{ $tenant->graceDaysLeft() }}d left)@endif
                </span>

                @if($tenant->subscription_expires_at)
                    @php $expired = $tenant->subscription_expires_at->isPast(); @endphp
                    <p class="text-sm mt-1 {{ $expired ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                        {{ $expired ? 'Expired' : 'Expires' }}: {{ $tenant->subscription_expires_at->format('d M Y') }}
                        @if(!$expired)<span class="text-xs text-gray-400">({{ $tenant->subscription_expires_at->diffForHumans() }})</span>@endif
                    </p>
                @endif
                @if($tenant->trial_ends_at)
                    @php $trialExpired = $tenant->trial_ends_at->isPast(); @endphp
                    <p class="text-xs mt-1 {{ $trialExpired ? 'text-red-500' : 'text-blue-500' }}">
                        Trial {{ $trialExpired ? 'ended' : 'ends' }}: {{ $tenant->trial_ends_at->format('d M Y') }}
                    </p>
                @endif
                @if($tenant->reminder_sent_at)
                    <p class="text-xs text-gray-400 mt-1">Last reminder: {{ $tenant->reminder_sent_at->diffForHumans() }}</p>
                @endif
            </div>

            {{-- Update Subscription Form --}}
            <form method="POST" action="{{ route('superadmin.companies.subscription', $tenant) }}" class="space-y-3 pt-2 border-t">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-xs font-medium text-gray-600">Plan</label>
                    <select name="plan_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">— No Plan —</option>
                        @foreach($plans as $p)
                        <option value="{{ $p->id }}" {{ $tenant->plan_id == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} — {{ $p->priceLabel() }}
                            @if(!$p->is_active) (inactive)@endif
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Status</label>
                    <select name="subscription_status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        @foreach(['active','trialing','suspended','cancelled'] as $s)
                        <option value="{{ $s }}" {{ ($tenant->subscription_status ?? 'trialing') === $s ? 'selected' : '' }}>
                            {{ ucfirst($s) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Subscription Expires</label>
                    <input type="date" name="subscription_expires_at"
                           value="{{ $tenant->subscription_expires_at?->toDateString() }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">Leave blank for no expiry (perpetual).</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Trial Ends</label>
                    <input type="date" name="trial_ends_at"
                           value="{{ $tenant->trial_ends_at?->toDateString() }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <button type="submit"
                        class="w-full py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                    Update Subscription
                </button>
            </form>
        </div>
    </div>

    {{-- Users --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-sm font-semibold">Users ({{ $tenant->users->count() }})</h3>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Email</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Role</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Status</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Joined</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tenant->users as $user)
                <tr>
                    <td class="px-4 py-2 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $user->email }}</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 text-xs rounded-full
                            {{ $user->role === 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-center text-gray-400 text-xs">{{ $user->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400">No users.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Recent Activity --}}
    @if($activityLog->isNotEmpty())
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-sm font-semibold">Recent Activity</h3>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach($activityLog as $log)
            <li class="px-6 py-2 text-xs text-gray-600 flex justify-between">
                <span>
                    <span class="font-medium text-gray-800">{{ $log->user_name ?? 'System' }}</span>
                    {{ $log->action }} {{ $log->auditable_type ? class_basename($log->auditable_type) : '' }}
                </span>
                <span class="text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Extend Trial Modal --}}
    <div x-show="showExtendTrial" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
         @click.self="showExtendTrial = false" @keydown.escape.window="showExtendTrial = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-semibold">Extend Trial</h3>
                <button type="button" @click="showExtendTrial = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <p class="text-sm text-gray-500">
                Current trial ends: <strong>{{ $tenant->trial_ends_at?->format('d M Y') ?? 'Not on trial' }}</strong>
            </p>
            <form method="POST" action="{{ route('superadmin.companies.extend-trial', $tenant) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700">Days to extend</label>
                    <input type="number" name="days" value="14" min="1" max="90"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showExtendTrial = false"
                            class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Extend Trial
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Send Reminder Modal --}}
    <div x-show="showReminder" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
         @click.self="showReminder = false" @keydown.escape.window="showReminder = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-semibold">Send Subscription Reminder</h3>
                <button type="button" @click="showReminder = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <p class="text-sm text-gray-500">Sending to: <strong>{{ $tenant->email }}</strong></p>
            <form method="POST" action="{{ route('superadmin.companies.remind', $tenant) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700">Custom Message (optional)</label>
                    <textarea name="message" rows="4"
                              placeholder="Add a personalised note to the standard reminder email…"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showReminder = false"
                            class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                            class="px-4 py-2 text-sm bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                        Send Reminder
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
