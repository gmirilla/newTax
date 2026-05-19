@extends('layouts.app')

@section('title', 'New PM Schedule')

@section('content')
<div class="p-6 max-w-2xl mx-auto" x-data="{ freqType: '{{ old('frequency_type', 'monthly') }}' }">

    <div class="mb-6">
        <a href="{{ route('maintenance.schedules.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Schedules</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Create PM Schedule</h1>
    </div>

    <form method="POST" action="{{ route('maintenance.schedules.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Schedule Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="150"
                           placeholder="e.g. Monthly Oil Change"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 @error('name') border-red-500 @enderror">
                    @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asset <span class="text-red-500">*</span></label>
                    <select name="asset_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 @error('asset_id') border-red-500 @enderror">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $a)
                        <option value="{{ $a->id }}" @selected(old('asset_id', $preAsset) == $a->id)>{{ $a->asset_name }}</option>
                        @endforeach
                    </select>
                    @error('asset_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Type <span class="text-red-500">*</span></label>
                    <select name="maintenance_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        @foreach(\App\Models\MaintenanceSchedule::MAINTENANCE_TYPES as $val => $label)
                        <option value="{{ $val }}" @selected(old('maintenance_type') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequency <span class="text-red-500">*</span></label>
                    <select name="frequency_type" x-model="freqType" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="custom_interval">Custom Interval</option>
                    </select>
                </div>

                <div x-show="freqType === 'custom_interval'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Interval (days) <span class="text-red-500">*</span></label>
                    <input type="number" name="frequency_days" value="{{ old('frequency_days', 30) }}" min="1" max="3650"
                           :required="freqType === 'custom_interval'"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Due Date <span class="text-red-500">*</span></label>
                    <input type="date" name="next_due_date" value="{{ old('next_due_date') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Hours</label>
                    <input type="number" name="estimated_hours" value="{{ old('estimated_hours', 1) }}" min="0.1" max="999" step="0.5"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Technician</label>
                    <select name="assigned_technician_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <option value="">— Auto-assign —</option>
                        @foreach($technicians as $t)
                        <option value="{{ $t->id }}" @selected(old('assigned_technician_id') == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Checklist
                        <span class="text-gray-400 font-normal ml-1">(one task per line — auto-copied to each work order)</span>
                    </label>
                    <textarea name="checklist" rows="5" placeholder="Check oil level&#10;Inspect belts&#10;Lubricate bearings"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-green-500">{{ old('checklist') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800">
                Create Schedule
            </button>
            <a href="{{ route('maintenance.schedules.index') }}" class="px-6 py-2.5 border border-gray-300 text-sm text-gray-700 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
