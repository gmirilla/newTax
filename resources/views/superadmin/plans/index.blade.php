@extends('superadmin.layout')

@section('page-title', 'Subscription Plans')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $plans->count() }} plan(s) configured</p>
        <a href="{{ route('superadmin.plans.create') }}"
           class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
            + New Plan
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($plans as $plan)
        <div class="bg-white rounded-lg shadow flex flex-col {{ !$plan->is_active ? 'opacity-60' : '' }}">

            {{-- Header --}}
            <div class="px-5 py-4 border-b flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-gray-900">{{ $plan->name }}</h3>
                        @if(!$plan->is_public)
                            <span class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-medium">Private</span>
                        @endif
                        @if(!$plan->is_active)
                            <span class="text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded font-medium">Inactive</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $plan->slug }}</p>
                    @if($plan->description)
                        <p class="text-sm text-gray-500 mt-1">{{ $plan->description }}</p>
                    @endif
                </div>
                <div class="text-right shrink-0 ml-3">
                    <div class="text-2xl font-bold text-indigo-600">{{ $plan->priceLabel() }}</div>
                    @if($plan->price_yearly)
                        <div class="text-xs text-gray-400">₦{{ number_format($plan->price_yearly, 0) }}/yr</div>
                    @endif
                </div>
            </div>

            {{-- Limits --}}
            <div class="px-5 py-3 space-y-2 flex-1">
                @php $limits = $plan->limits ?? []; @endphp
                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
                    <div class="text-gray-500">Invoices/mo</div>
                    <div class="font-medium">{{ $limits['invoices_per_month'] === null ? 'Unlimited' : $limits['invoices_per_month'] }}</div>

                    <div class="text-gray-500">Users</div>
                    <div class="font-medium">{{ $limits['users'] === null ? 'Unlimited' : $limits['users'] }}</div>

                    <div class="text-gray-500">Payroll staff</div>
                    <div class="font-medium">
                        @if(($limits['payroll_staff'] ?? 0) === 0)
                            <span class="text-red-500">Disabled</span>
                        @elseif($limits['payroll_staff'] === null)
                            Unlimited
                        @else
                            {{ $limits['payroll_staff'] }}
                        @endif
                    </div>

                    <div class="text-gray-500">Customers</div>
                    <div class="font-medium">{{ $limits['customers'] === null ? 'Unlimited' : $limits['customers'] }}</div>
                </div>

                {{-- Feature flags --}}
                <div class="flex flex-wrap gap-1.5 pt-2 border-t mt-2">
                    @php
                        $features = [
                            'payroll'          => 'Payroll',
                            'firs'             => 'FIRS e-Invoice',
                            'advanced_reports' => 'Adv. Reports',
                            'api_access'       => 'API Access',
                        ];
                    @endphp
                    @foreach($features as $key => $label)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ ($limits[$key] ?? false) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400 line-through' }}">
                            {{ $label }}
                        </span>
                    @endforeach
                </div>

                <p class="text-xs text-gray-400 pt-1">
                    {{ $plan->trial_days }} day trial &middot;
                    <span class="font-medium text-gray-600">{{ $plan->tenants_count }}</span> tenants
                </p>
            </div>

            {{-- Actions --}}
            <div class="px-5 py-3 border-t flex gap-2">
                <a href="{{ route('superadmin.plans.edit', $plan) }}"
                   class="flex-1 text-center py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50">
                    Edit
                </a>
                <form method="POST" action="{{ route('superadmin.plans.destroy', $plan) }}"
                      onsubmit="return confirm('Delete plan {{ addslashes($plan->name) }}? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-3 py-1.5 text-sm border border-red-200 text-red-600 rounded-md hover:bg-red-50"
                            {{ $plan->tenants_count > 0 ? 'title=Cannot delete — tenants exist' : '' }}>
                        Delete
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-3 bg-white rounded-lg shadow p-12 text-center">
            <p class="text-gray-500 text-sm mb-4">No plans configured yet.</p>
            <a href="{{ route('superadmin.plans.create') }}"
               class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                Create your first plan
            </a>
        </div>
        @endforelse
    </div>

</div>
@endsection
