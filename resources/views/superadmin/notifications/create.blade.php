@extends('superadmin.layout')

@section('page-title', 'New Notification')

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="notifForm('{{ old('target_type', $preTargetType) }}')">

    <div>
        <h1 class="text-xl font-bold text-gray-900">New Notification</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Sending as <strong>draft</strong> saves without broadcasting. <strong>Send now</strong> makes it immediately visible in tenants' apps.
        </p>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('superadmin.notifications.store') }}" class="space-y-5">
        @csrf

        {{-- Title --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title') }}" required maxlength="150"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm"
                   placeholder="e.g. Scheduled Maintenance – 25 May 2026">
        </div>

        {{-- Message --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
            <textarea name="message" rows="5" required maxlength="5000"
                      class="w-full rounded-lg border-gray-300 shadow-sm text-sm"
                      placeholder="Write your message here…">{{ old('message') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Plain text. Line breaks are preserved.</p>
        </div>

        {{-- Type --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
            <select name="type" class="rounded-lg border-gray-300 shadow-sm text-sm w-full">
                @foreach(['info' => 'Info (blue)', 'warning' => 'Warning (amber)', 'critical' => 'Critical (red) — also sends email', 'success' => 'Success (green)'] as $val => $label)
                <option value="{{ $val }}" {{ old('type', 'info') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Target --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Target <span class="text-red-500">*</span></label>
            <select name="target_type" x-model="targetType" class="rounded-lg border-gray-300 shadow-sm text-sm w-full">
                <option value="all"      {{ old('target_type', $preTargetType) === 'all'      ? 'selected' : '' }}>All tenants</option>
                <option value="plan"     {{ old('target_type', $preTargetType) === 'plan'     ? 'selected' : '' }}>By plan</option>
                <option value="specific" {{ old('target_type', $preTargetType) === 'specific' ? 'selected' : '' }}>Specific tenants</option>
            </select>
        </div>

        {{-- By Plan --}}
        <div x-show="targetType === 'plan'" x-cloak class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Select plans</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach($plans as $plan)
                <label class="flex items-center gap-2 text-sm cursor-pointer bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                    <input type="checkbox" name="target_ids[]" value="{{ $plan->id }}"
                           {{ in_array($plan->id, old('target_ids', [])) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600">
                    <span>{{ $plan->name }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Specific tenants --}}
        <div x-show="targetType === 'specific'" x-cloak class="space-y-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Select tenants</label>
            <div class="max-h-52 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100">
                @foreach($tenants as $tenant)
                <label class="flex items-center gap-2 text-sm cursor-pointer px-3 py-2 hover:bg-gray-50">
                    <input type="checkbox" name="target_ids[]" value="{{ $tenant->id }}"
                           {{ (in_array($tenant->id, old('target_ids', [])) || $tenant->id === $preTenantId) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600">
                    <span class="font-medium text-gray-800">{{ $tenant->name }}</span>
                    <span class="text-gray-400 text-xs">{{ $tenant->email }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Expires at --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Expires at <span class="text-gray-400 font-normal">(optional — leave blank to never expire)</span></label>
            <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"
                   class="rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        {{-- Actions --}}
        <div class="flex gap-3 pt-2">
            <button type="submit" name="status" value="sent"
                    class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                Send Now
            </button>
            <button type="submit" name="status" value="draft"
                    class="px-5 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                Save as Draft
            </button>
            <a href="{{ route('superadmin.notifications.index') }}"
               class="px-5 py-2 text-gray-500 text-sm hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>

<script>
function notifForm(initial) {
    return { targetType: initial || 'all' };
}
</script>
@endsection
