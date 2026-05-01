@extends('superadmin.layout')

@section('page-title', 'Edit Plan: ' . $plan->name)

@section('content')
<div class="space-y-4">
    <a href="{{ route('superadmin.plans.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Plans</a>

    <div class="bg-amber-50 border border-amber-200 rounded-md px-4 py-3 text-sm text-amber-800">
        <strong>{{ $plan->tenants_count ?? 0 }} tenant(s)</strong> are currently on this plan.
        Changes to limits and features take effect immediately for all of them.
    </div>

    <form method="POST" action="{{ route('superadmin.plans.update', $plan) }}">
        @csrf
        @method('PUT')
        @include('superadmin.plans._form')
    </form>
</div>
@endsection
