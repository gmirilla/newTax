@extends('superadmin.layout')

@section('page-title', 'Companies')

@section('content')
<div class="space-y-4">

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Name, email or TIN…"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 w-60">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="deleted"  {{ request('status') === 'deleted'  ? 'selected' : '' }}>Deleted</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Plan</label>
                <select name="plan" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All Plans</option>
                    @foreach(['free','starter','pro','enterprise'] as $plan)
                    <option value="{{ $plan }}" {{ request('plan') === $plan ? 'selected' : '' }}>{{ ucfirst($plan) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Subscription</label>
                <select name="expiry" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All</option>
                    <option value="expiring" {{ request('expiry') === 'expiring' ? 'selected' : '' }}>Expiring (14 days)</option>
                    <option value="expired"  {{ request('expiry') === 'expired'  ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-1.5 bg-gray-800 text-white text-sm rounded-md">Filter</button>
            @if(request()->hasAny(['search','status','plan','expiry']))
            <a href="{{ route('superadmin.companies') }}" class="text-xs text-gray-500 hover:underline self-center">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-sm font-semibold">
                {{ $companies->total() }} {{ Str::plural('Company', $companies->total()) }}
            </h3>
            <form method="POST" action="{{ route('superadmin.bulk-reminder') }}">
                @csrf
                <button type="submit"
                        class="px-3 py-1.5 text-xs bg-yellow-500 text-white rounded-md hover:bg-yellow-600">
                    Send Reminders to Expiring
                </button>
            </form>
        </div>

        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">TIN / RC</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Users</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Plan</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Expires</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Joined</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($companies as $company)
                @php
                    $isExpired = $company->subscription_expires_at && $company->subscription_expires_at->isPast();
                    $isExpiring = $company->subscription_expires_at && !$isExpired && $company->subscription_expires_at->diffInDays(now()) <= 14;
                @endphp
                <tr class="hover:bg-gray-50 {{ $company->trashed() ? 'opacity-50' : '' }}">
                    <td class="px-4 py-3">
                        <a href="{{ route('superadmin.companies.show', $company) }}"
                           class="font-medium text-gray-900 hover:text-indigo-600">
                            {{ $company->name }}
                        </a>
                        <p class="text-xs text-gray-400">{{ $company->email }}</p>
                        <p class="text-xs text-gray-400">{{ $company->state }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 font-mono">
                        <div>TIN: {{ $company->tin ?? '—' }}</div>
                        <div>RC: {{ $company->rc_number ?? '—' }}</div>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-700">{{ $company->users_count }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 text-xs rounded-full font-medium
                            {{ $company->subscription_plan === 'free'       ? 'bg-gray-100 text-gray-600' :
                               ($company->subscription_plan === 'starter'   ? 'bg-blue-100 text-blue-700' :
                               ($company->subscription_plan === 'pro'       ? 'bg-green-100 text-green-700' :
                                                                              'bg-purple-100 text-purple-700')) }}">
                            {{ ucfirst($company->subscription_plan) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-xs">
                        @if($company->subscription_expires_at)
                            <span class="{{ $isExpired ? 'text-red-600 font-semibold' : ($isExpiring ? 'text-yellow-600 font-semibold' : 'text-gray-500') }}">
                                {{ $company->subscription_expires_at->format('d M Y') }}
                                @if($isExpired) (Expired)
                                @elseif($isExpiring) ({{ $company->subscription_expires_at->diffForHumans() }})
                                @endif
                            </span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($company->trashed())
                            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">Deleted</span>
                        @else
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $company->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $company->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-xs text-gray-500">
                        {{ $company->created_at->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('superadmin.companies.show', $company) }}"
                               class="text-xs text-indigo-600 hover:underline">View</a>
                            @if(!$company->trashed())
                            <form method="POST" action="{{ route('superadmin.companies.toggle', $company) }}">
                                @csrf
                                <button type="submit"
                                        class="text-xs {{ $company->is_active ? 'text-red-600' : 'text-green-600' }} hover:underline">
                                    {{ $company->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('superadmin.companies.remind', $company) }}">
                                @csrf
                                <button type="submit" class="text-xs text-yellow-600 hover:underline">Remind</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">No companies match your filters.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3 border-t">
            {{ $companies->links() }}
        </div>
    </div>
</div>
@endsection
