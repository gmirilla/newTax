@php $isEdit = isset($agreement); @endphp

<div class="max-w-2xl space-y-6">

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Enterprise Plan *</label>
                @if($plans->isEmpty())
                <p class="text-sm text-red-600">No enterprise plans exist. <a href="{{ route('superadmin.plans.create') }}" class="underline">Create one</a> first.</p>
                @else
                <select name="plan_id" required
                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">— Select plan —</option>
                    @foreach($plans as $p)
                    <option value="{{ $p->id }}" {{ old('plan_id', $agreement->plan_id ?? '') == $p->id ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                    @endforeach
                </select>
                @error('plan_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @endif
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Negotiated Price (₦) *</label>
                <input type="number" name="negotiated_price" min="0" step="0.01"
                       value="{{ old('negotiated_price', $agreement->negotiated_price ?? '') }}" required
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('negotiated_price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Billing Cycle *</label>
                <select name="billing_cycle" required
                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach(['monthly', 'quarterly', 'annually'] as $cycle)
                    <option value="{{ $cycle }}" {{ old('billing_cycle', $agreement->billing_cycle ?? 'monthly') === $cycle ? 'selected' : '' }}>
                        {{ ucfirst($cycle) }}
                    </option>
                    @endforeach
                </select>
                @error('billing_cycle')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Payment Terms (days) *</label>
                <input type="number" name="payment_terms_days" min="7" max="90"
                       value="{{ old('payment_terms_days', $agreement->payment_terms_days ?? 30) }}" required
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('payment_terms_days')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Start Date *</label>
                <input type="date" name="start_date"
                       value="{{ old('start_date', isset($agreement) ? $agreement->start_date->format('Y-m-d') : today()->format('Y-m-d')) }}" required
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('start_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">End Date <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="date" name="end_date"
                       value="{{ old('end_date', isset($agreement) && $agreement->end_date ? $agreement->end_date->format('Y-m-d') : '') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('end_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            @if($isEdit)
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select name="status" required
                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach(['active', 'expired', 'terminated'] as $s)
                    <option value="{{ $s }}" {{ old('status', $agreement->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Internal Notes</label>
            <textarea name="notes" rows="3"
                      placeholder="Custom terms, renewal conditions, contact person..."
                      class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('notes', $agreement->notes ?? '') }}</textarea>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit"
                class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
            {{ $isEdit ? 'Save Changes' : 'Create Agreement' }}
        </button>
        <a href="{{ route('superadmin.enterprises.agreements.index', $tenant) }}"
           class="px-6 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">Cancel</a>
    </div>
</div>
