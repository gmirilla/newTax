@php
    $limits   = $plan->limits ?? \App\Models\Plan::LIMIT_DEFAULTS;
    $isEdit   = isset($plan->id);

    // Helper: null → '' for optional numeric limits
    $lv = fn(string $key) => array_key_exists($key, $limits) && $limits[$key] !== null ? $limits[$key] : '';
    $fv = fn(string $key) => !empty($limits[$key]);
@endphp

<div class="max-w-2xl space-y-6">

    {{-- Identity --}}
    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 border-b pb-2">Plan Identity</h3>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Plan Name *</label>
                <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}" required
                       placeholder="e.g. Growth"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Slug * <span class="text-gray-400 font-normal">(machine name)</span></label>
                <input type="text" name="slug" value="{{ old('slug', $plan->slug ?? '') }}" required
                       placeholder="e.g. growth"
                       {{ $isEdit ? 'readonly class="w-full rounded-md border-gray-200 bg-gray-50 shadow-sm text-sm text-gray-500"' : 'class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"' }}>
                @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @if($isEdit)<p class="mt-1 text-xs text-gray-400">Slug cannot be changed after creation.</p>@endif
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
            <textarea name="description" rows="2"
                      placeholder="Short description shown to customers..."
                      class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $plan->description ?? '') }}</textarea>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Monthly Price (₦)</label>
                <input type="number" name="price_monthly" min="0" step="0.01"
                       value="{{ old('price_monthly', $plan->price_monthly ?? 0) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('price_monthly')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Yearly Price (₦) <span class="text-gray-400 font-normal">optional</span></label>
                <input type="number" name="price_yearly" min="0" step="0.01"
                       value="{{ old('price_yearly', $plan->price_yearly ?? '') }}"
                       placeholder="Leave blank if N/A"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Trial Days</label>
                <input type="number" name="trial_days" min="0" max="365"
                       value="{{ old('trial_days', $plan->trial_days ?? 14) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
                Paystack Plan Code
                <span class="text-gray-400 font-normal">(e.g. PLN_xxxxxxxxxx — enables auto-renewing subscriptions)</span>
            </label>
            <input type="text" name="paystack_plan_code"
                   value="{{ old('paystack_plan_code', $plan->paystack_plan_code ?? '') }}"
                   placeholder="Leave blank for one-off monthly payments"
                   class="w-full rounded-md border-gray-300 shadow-sm text-sm font-mono focus:ring-indigo-500 focus:border-indigo-500">
            @error('paystack_plan_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-gray-400">Create the plan in your Paystack dashboard first, then paste the Plan Code here.</p>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sort Order</label>
                <input type="number" name="sort_order" min="0"
                       value="{{ old('sort_order', $plan->sort_order ?? 0) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="flex flex-col gap-2 pt-4">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600">
                    <span class="text-gray-700">Active</span>
                </label>
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="hidden" name="is_public" value="0">
                    <input type="checkbox" name="is_public" value="1"
                           {{ old('is_public', $plan->is_public ?? true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600">
                    <span class="text-gray-700">Show on pricing page</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Usage Limits --}}
    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">Usage Limits</h3>
            <p class="text-xs text-gray-400 mt-0.5">Leave blank for unlimited. Set to 0 to disable entirely.</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Invoices per Month</label>
                <input type="number" name="limit_invoices" min="0"
                       value="{{ old('limit_invoices', $lv('invoices_per_month')) }}"
                       placeholder="blank = unlimited"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Users (seats)</label>
                <input type="number" name="limit_users" min="0"
                       value="{{ old('limit_users', $lv('users')) }}"
                       placeholder="blank = unlimited"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Payroll Staff</label>
                <input type="number" name="limit_payroll_staff" min="0"
                       value="{{ old('limit_payroll_staff', $lv('payroll_staff')) }}"
                       placeholder="blank = unlimited, 0 = disabled"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Customers</label>
                <input type="number" name="limit_customers" min="0"
                       value="{{ old('limit_customers', $lv('customers')) }}"
                       placeholder="blank = unlimited"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
    </div>

    {{-- Feature Flags --}}
    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">Feature Access</h3>

        <div class="grid grid-cols-2 gap-3">
            @php
                $featureList = [
                    'feature_payroll'          => ['label' => 'Payroll Module',       'desc' => 'Access to payroll processing and payslips'],
                    'feature_firs'             => ['label' => 'NRS e-Invoicing',     'desc' => 'Submit invoices directly to FIRS'],
                    'feature_advanced_reports' => ['label' => 'Advanced Reports',     'desc' => 'General Ledger, Balance Sheet exports'],
                    'feature_api_access'       => ['label' => 'API Access',           'desc' => 'Programmatic access via API keys (future)'],
                ];
                $featureKeyMap = [
                    'feature_payroll'          => 'payroll',
                    'feature_firs'             => 'firs',
                    'feature_advanced_reports' => 'advanced_reports',
                    'feature_api_access'       => 'api_access',
                ];
            @endphp
            @foreach($featureList as $inputName => $meta)
            <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50
                          {{ old($inputName, $fv($featureKeyMap[$inputName])) ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200' }}">
                <input type="hidden" name="{{ $inputName }}" value="0">
                <input type="checkbox" name="{{ $inputName }}" value="1"
                       {{ old($inputName, $fv($featureKeyMap[$inputName])) ? 'checked' : '' }}
                       class="mt-0.5 rounded border-gray-300 text-indigo-600">
                <div>
                    <div class="text-sm font-medium text-gray-800">{{ $meta['label'] }}</div>
                    <div class="text-xs text-gray-400">{{ $meta['desc'] }}</div>
                </div>
            </label>
            @endforeach
        </div>
    </div>

    {{-- Submit --}}
    <div class="flex gap-3">
        <button type="submit"
                class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
            {{ $isEdit ? 'Save Changes' : 'Create Plan' }}
        </button>
        <a href="{{ route('superadmin.plans.index') }}"
           class="px-6 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">
            Cancel
        </a>
    </div>

</div>
