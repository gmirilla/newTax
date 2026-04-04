@extends('superadmin.layout')

@section('page-title', 'Platform Dashboard')

@section('content')
<div class="space-y-6">

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_companies'] }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase font-medium">Total Companies</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-3xl font-bold text-green-700">{{ $stats['active_companies'] }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase font-medium">Active</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-3xl font-bold text-gray-400">{{ $stats['inactive_companies'] }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase font-medium">Deactivated</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-3xl font-bold text-blue-700">{{ $stats['new_this_month'] }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase font-medium">New This Month</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 text-center border-t-4 border-gray-300">
            <p class="text-2xl font-bold text-gray-500">{{ $stats['free_plan'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Free</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-t-4 border-blue-400">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['starter_plan'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Starter</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-t-4 border-green-500">
            <p class="text-2xl font-bold text-green-700">{{ $stats['pro_plan'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Pro</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-t-4 border-purple-500">
            <p class="text-2xl font-bold text-purple-700">{{ $stats['enterprise_plan'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Enterprise</p>
        </div>
    </div>

    @if($stats['expired'] > 0 || $stats['expiring_soon'] > 0)
    <div class="grid grid-cols-2 gap-4">
        @if($stats['expired'] > 0)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold text-red-700">{{ $stats['expired'] }}</p>
                <p class="text-sm text-red-600">Subscriptions Expired</p>
            </div>
            <form method="POST" action="{{ route('superadmin.bulk-reminder') }}">
                @csrf
                <button type="submit"
                        class="px-3 py-1.5 bg-red-600 text-white text-xs rounded-md hover:bg-red-700">
                    Send All Reminders
                </button>
            </form>
        </div>
        @endif
        @if($stats['expiring_soon'] > 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold text-yellow-700">{{ $stats['expiring_soon'] }}</p>
                <p class="text-sm text-yellow-600">Expiring in 14 Days</p>
            </div>
            <a href="{{ route('superadmin.companies', ['expiry' => 'expiring']) }}"
               class="px-3 py-1.5 bg-yellow-600 text-white text-xs rounded-md hover:bg-yellow-700">
                View Companies
            </a>
        </div>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Companies --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-sm font-semibold">Recently Onboarded</h3>
                <a href="{{ route('superadmin.companies') }}" class="text-xs text-indigo-600 hover:underline">View all →</a>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse($recentCompanies as $company)
                <li class="px-6 py-3 flex items-center justify-between">
                    <div>
                        <a href="{{ route('superadmin.companies.show', $company) }}"
                           class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                            {{ $company->name }}
                        </a>
                        <p class="text-xs text-gray-400">{{ $company->email }} · {{ $company->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ $company->subscription_plan === 'free' ? 'bg-gray-100 text-gray-600' :
                               ($company->subscription_plan === 'enterprise' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700') }}">
                            {{ ucfirst($company->subscription_plan) }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $company->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $company->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </li>
                @empty
                <li class="px-6 py-4 text-sm text-gray-400 text-center">No companies yet.</li>
                @endforelse
            </ul>
        </div>

        {{-- Expiring Soon --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-sm font-semibold text-yellow-700">Subscriptions Expiring Soon</h3>
                <form method="POST" action="{{ route('superadmin.bulk-reminder') }}">
                    @csrf
                    <button type="submit" class="text-xs bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700">
                        Remind All
                    </button>
                </form>
            </div>
            @if($expiringSoon->isEmpty())
            <div class="px-6 py-6 text-sm text-gray-400 text-center">No subscriptions expiring in the next 14 days.</div>
            @else
            <ul class="divide-y divide-gray-100">
                @foreach($expiringSoon as $company)
                <li class="px-6 py-3 flex items-center justify-between">
                    <div>
                        <a href="{{ route('superadmin.companies.show', $company) }}"
                           class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                            {{ $company->name }}
                        </a>
                        <p class="text-xs text-gray-400">Expires: {{ $company->subscription_expires_at->format('d M Y') }}</p>
                    </div>
                    <form method="POST" action="{{ route('superadmin.companies.remind', $company) }}">
                        @csrf
                        <button type="submit" class="text-xs text-indigo-600 hover:underline">Send Reminder</button>
                    </form>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

</div>
@endsection
