@extends('layouts.app')

@section('title', 'Report Breakdown')

@section('content')
<div class="p-6 max-w-2xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('maintenance.breakdowns.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Breakdowns</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Report Machine Breakdown</h1>
        <p class="text-sm text-gray-500 mt-0.5">Reporting a breakdown will mark the asset as unavailable and create a corrective work order.</p>
    </div>

    <form method="POST" action="{{ route('maintenance.breakdowns.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asset <span class="text-red-500">*</span></label>
                    <select name="asset_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 @error('asset_id') border-red-500 @enderror">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $a)
                        @php $color = \App\Models\MaintenanceAsset::STATUS_COLORS[$a->status] ?? 'gray'; @endphp
                        <option value="{{ $a->id }}" @selected(old('asset_id', $preAsset) == $a->id)>
                            {{ $a->asset_code }} — {{ $a->asset_name }} ({{ \App\Models\MaintenanceAsset::STATUSES[$a->status] }})
                        </option>
                        @endforeach
                    </select>
                    @error('asset_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Severity <span class="text-red-500">*</span></label>
                    <select name="severity" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <option value="low" @selected(old('severity') === 'low')>Low — Minor issue, partial operation</option>
                        <option value="medium" @selected(old('severity', 'medium') === 'medium')>Medium — Reduced capacity</option>
                        <option value="high" @selected(old('severity') === 'high')>High — Stopped, urgent repair needed</option>
                        <option value="critical" @selected(old('severity') === 'critical')>Critical — Safety risk / total failure</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Breakdown Start Time <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="downtime_start" value="{{ old('downtime_start', now()->format('Y-m-d\TH:i')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    @error('downtime_start')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Issue Description <span class="text-red-500">*</span></label>
                    <textarea name="issue_description" rows="5" required maxlength="2000"
                              placeholder="Describe what happened, observed symptoms, sounds, error messages…"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 @error('issue_description') border-red-500 @enderror">{{ old('issue_description') }}</textarea>
                    @error('issue_description')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="create_work_order" value="1" @checked(old('create_work_order', true))
                               class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700">Automatically create a corrective work order</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                Report Breakdown
            </button>
            <a href="{{ route('maintenance.breakdowns.index') }}" class="px-6 py-2.5 border border-gray-300 text-sm text-gray-700 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
