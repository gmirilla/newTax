@extends('layouts.app')
@section('page-title', 'Vendors')

@section('content')
<div x-data="vendorIndex()" class="max-w-5xl space-y-5">

    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Vendors you buy goods or services from — used for expenses and Withholding Tax (WHT).</p>
        <button @click="showNewVendor = true"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + New Vendor
        </button>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">WHT</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($vendors as $vendor)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-800">
                        {{ $vendor->name }}
                        @if($vendor->email || $vendor->phone)
                        <span class="block text-xs text-gray-400">{{ $vendor->email }} {{ $vendor->email && $vendor->phone ? '·' : '' }} {{ $vendor->phone }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 capitalize">{{ $vendor->vendor_type }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if($vendor->wht_exempt)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Exempt</span>
                        @else
                            <span class="text-gray-500">{{ rtrim(rtrim(number_format((float) $vendor->wht_rate, 2), '0'), '.') }}%</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">₦{{ number_format((float) $vendor->current_balance, 2) }}</td>
                    <td class="px-4 py-3">
                        @if($vendor->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                        @can('update', $vendor)
                        <a href="{{ route('vendors.edit', $vendor) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                        @endcan
                        @can('delete', $vendor)
                        <form method="POST" action="{{ route('vendors.destroy', $vendor) }}" class="inline"
                              onsubmit="return confirm('Delete vendor {{ addslashes($vendor->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">
                        No vendors yet.
                        <button @click="showNewVendor = true" class="text-green-600 hover:underline">Add your first one →</button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- New Vendor Modal --}}
    <template x-teleport="body">
        <div x-show="showNewVendor" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
             @click.self="showNewVendor = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-base font-semibold">Add New Vendor</h3>
                    <button type="button" @click="showNewVendor = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700">Vendor Name *</label>
                        <input type="text" x-model="newVendor.name" placeholder="e.g. Zenith Supplies Ltd"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Email</label>
                        <input type="email" x-model="newVendor.email"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Phone</label>
                        <input type="text" x-model="newVendor.phone" placeholder="+234..."
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">TIN</label>
                        <input type="text" x-model="newVendor.tin" placeholder="1234567-0001"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <div x-show="!newVendor.wht_exempt">
                        <label class="block text-xs font-medium text-gray-700">Vendor Type</label>
                        <select x-model="newVendor.vendor_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="services">Services (WHT 5%)</option>
                            <option value="goods">Goods (WHT 5%)</option>
                            <option value="rent">Rent (WHT 10%)</option>
                            <option value="mixed">Mixed (WHT 5%)</option>
                        </select>
                    </div>
                    {{-- WHT Exempt toggle --}}
                    <div class="col-span-2">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="newVendor.wht_exempt"
                                   class="rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                            <span class="text-xs font-medium text-gray-700">WHT Not Applicable</span>
                        </label>
                        <p class="text-xs text-gray-400 mt-0.5 ml-5">
                            e.g. foreign vendor, income not earned in Nigeria, diplomatic, govt entity
                        </p>
                    </div>
                    {{-- Exempt reason (shown when exempt is checked) --}}
                    <div class="col-span-2" x-show="newVendor.wht_exempt" x-cloak>
                        <label class="block text-xs font-medium text-gray-700">Reason *</label>
                        <select x-model="newVendor.wht_exempt_reason"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">— Select reason —</option>
                            @foreach(\App\Models\Vendor::WHT_EXEMPT_REASONS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p x-show="newVendorError" x-text="newVendorError" class="text-xs text-red-600"></p>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showNewVendor = false"
                            class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button type="button" @click="saveNewVendor()"
                            :disabled="savingVendor"
                            class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
                        <span x-text="savingVendor ? 'Saving…' : 'Save Vendor'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
function vendorIndex() {
    return {
        showNewVendor: false,
        savingVendor: false,
        newVendorError: '',
        newVendor: { name: '', email: '', phone: '', tin: '', vendor_type: 'services', wht_exempt: false, wht_exempt_reason: '' },

        async saveNewVendor() {
            if (!this.newVendor.name.trim()) {
                this.newVendorError = 'Vendor name is required.';
                return;
            }
            this.savingVendor = true;
            this.newVendorError = '';
            try {
                const res = await fetch('{{ route('vendors.quick-store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.newVendor),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.newVendorError = data.message || 'Failed to save vendor.';
                    return;
                }
                window.location.reload();
            } catch (e) {
                this.newVendorError = 'Network error. Please try again.';
            } finally {
                this.savingVendor = false;
            }
        },
    };
}
</script>
@endpush
@endsection
