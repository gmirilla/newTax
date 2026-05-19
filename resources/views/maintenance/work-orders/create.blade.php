@extends('layouts.app')

@section('title', 'New Work Order')

@section('content')
<div class="p-6 max-w-3xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('maintenance.work-orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Work Orders</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Create Work Order</h1>
    </div>

    <form method="POST" action="{{ route('maintenance.work-orders.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                    <select name="source_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <option value="preventive" @selected(old('source_type') === 'preventive')>Preventive</option>
                        <option value="corrective" @selected(old('source_type') === 'corrective')>Corrective</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asset <span class="text-red-500">*</span></label>
                    <select name="asset_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 @error('asset_id') border-red-500 @enderror">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $a)
                        <option value="{{ $a->id }}" @selected(old('asset_id', $preAsset) == $a->id)>{{ $a->asset_code }} — {{ $a->asset_name }}</option>
                        @endforeach
                    </select>
                    @error('asset_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required maxlength="200"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 @error('title') border-red-500 @enderror">
                    @error('title')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority <span class="text-red-500">*</span></label>
                    <select name="priority" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <option value="low" @selected(old('priority') === 'low')>Low</option>
                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
                        <option value="high" @selected(old('priority') === 'high')>High</option>
                        <option value="critical" @selected(old('priority') === 'critical')>Critical</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                    <select name="assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <option value="">— Unassigned —</option>
                        @foreach($technicians as $t)
                        <option value="{{ $t->id }}" @selected(old('assigned_to') == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled Date</label>
                    <input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Hours</label>
                    <input type="number" name="estimated_hours" value="{{ old('estimated_hours') }}" min="0" max="9999" step="0.5"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="4" maxlength="3000"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800">
                Create Work Order
            </button>
            <a href="{{ route('maintenance.work-orders.index') }}" class="px-6 py-2.5 border border-gray-300 text-sm text-gray-700 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
