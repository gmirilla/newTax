@extends('layouts.app')
@section('page-title', 'Edit Vendor')

@section('content')
<div class="max-w-2xl space-y-5">

    <a href="{{ route('vendors.index') }}" class="text-sm text-green-600 hover:underline">← Back to Vendors</a>

    <div x-data="{ whtExempt: {{ old('wht_exempt', $vendor->wht_exempt) ? 'true' : 'false' }} }"
         class="bg-white rounded-lg shadow p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ $vendor->name }}</h2>

        <form method="POST" action="{{ route('vendors.update', $vendor) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Vendor Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $vendor->name) }}" required maxlength="255"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ old('email', $vendor->email) }}" maxlength="255"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}" maxlength="30"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" name="address" value="{{ old('address', $vendor->address) }}" maxlength="255"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" name="city" value="{{ old('city', $vendor->city) }}" maxlength="100"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">State</label>
                    <select name="state" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        <option value="">— Select —</option>
                        @foreach(config('nigeria_states') as $s)
                        <option value="{{ $s }}" {{ old('state', $vendor->state) === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('state')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">TIN</label>
                    <input type="text" name="tin" value="{{ old('tin', $vendor->tin) }}" maxlength="50"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('tin')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">RC Number</label>
                    <input type="text" name="rc_number" value="{{ old('rc_number', $vendor->rc_number) }}" maxlength="50"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('rc_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4 space-y-4">
                <h3 class="text-sm font-semibold text-gray-700">Withholding Tax (WHT)</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div x-show="!whtExempt">
                        <label class="block text-sm font-medium text-gray-700">Vendor Type</label>
                        <select name="vendor_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                            <option value="services" {{ old('vendor_type', $vendor->vendor_type) === 'services' ? 'selected' : '' }}>Services</option>
                            <option value="goods" {{ old('vendor_type', $vendor->vendor_type) === 'goods' ? 'selected' : '' }}>Goods</option>
                            <option value="rent" {{ old('vendor_type', $vendor->vendor_type) === 'rent' ? 'selected' : '' }}>Rent</option>
                            <option value="mixed" {{ old('vendor_type', $vendor->vendor_type) === 'mixed' ? 'selected' : '' }}>Mixed</option>
                        </select>
                    </div>
                    <div x-show="!whtExempt">
                        <label class="block text-sm font-medium text-gray-700">WHT Rate (%)</label>
                        <input type="number" name="wht_rate" value="{{ old('wht_rate', $vendor->wht_rate) }}" min="0" max="100" step="0.01"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        @error('wht_rate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="hidden" name="wht_exempt" value="0">
                    <input type="checkbox" name="wht_exempt" value="1" x-model="whtExempt"
                           class="rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                    <span class="text-sm font-medium text-gray-700">WHT Not Applicable</span>
                </label>

                <div x-show="whtExempt" x-cloak>
                    <label class="block text-sm font-medium text-gray-700">Exemption Reason</label>
                    <select name="wht_exempt_reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        <option value="">— Select reason —</option>
                        @foreach(\App\Models\Vendor::WHT_EXEMPT_REASONS as $key => $label)
                            <option value="{{ $key }}" {{ old('wht_exempt_reason', $vendor->wht_exempt_reason) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('wht_exempt_reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4 flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $vendor->is_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </label>
                <p class="text-sm text-gray-400">Current balance: ₦{{ number_format((float) $vendor->current_balance, 2) }}</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('vendors.index') }}" class="px-4 py-2 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">Save Vendor</button>
            </div>
        </form>
    </div>
</div>
@endsection
