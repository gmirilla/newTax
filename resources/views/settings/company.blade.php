@extends('layouts.app')

@section('page-title', 'Company Settings')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- ─── Logo Upload ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-4">
            <h2 class="text-base font-semibold">Company Logo</h2>
            <p class="text-sm text-gray-500 mt-0.5">Appears on PDF invoices and quotations. Recommended: PNG or SVG, max 2 MB.</p>
        </div>

        <div class="flex items-start gap-6">
            {{-- Current logo preview --}}
            <div class="flex-shrink-0">
                @if($tenant->logo)
                    <img src="{{ Storage::url($tenant->logo) }}" alt="Company logo"
                         class="h-20 w-auto object-contain border border-gray-200 rounded p-1 bg-gray-50">
                @else
                    <div class="h-20 w-40 border-2 border-dashed border-gray-300 rounded flex items-center justify-center text-gray-400 text-xs text-center bg-gray-50">
                        No logo<br>uploaded
                    </div>
                @endif
            </div>

            <div class="flex-1 space-y-3">
                {{-- Upload form --}}
                <form method="POST" action="{{ route('settings.company.logo.upload') }}"
                      enctype="multipart/form-data" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload new logo</label>
                        <input type="file" name="logo" accept="image/*" required
                               class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-4
                                      file:rounded file:border-0 file:text-sm file:font-medium
                                      file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                        @error('logo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                        Upload
                    </button>
                </form>

                {{-- Remove logo --}}
                @if($tenant->logo)
                <form method="POST" action="{{ route('settings.company.logo.delete') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            onclick="return confirm('Remove the company logo?')"
                            class="text-sm text-red-600 hover:text-red-800 underline">
                        Remove logo
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-5">
        <div>
            <h2 class="text-base font-semibold">Company Details</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                These details appear on invoices, tax returns, and reports.
                Updating annual turnover will automatically recalculate your CIT bracket.
            </p>
        </div>

        <form method="POST" action="{{ route('settings.company.update') }}" class="space-y-5">
            @csrf
            @method('PATCH')

            {{-- Core identity --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Company / Business Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Business Email</label>
                    <input type="email" name="email" value="{{ old('email', $tenant->email) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Address --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Street Address</label>
                    <input type="text" name="address" value="{{ old('address', $tenant->address) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" name="city" value="{{ old('city', $tenant->city) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">State</label>
                    <select name="state" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        <option value="">— Select —</option>
                        @foreach(['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'] as $s)
                        <option value="{{ $s }}" {{ old('state', $tenant->state) === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Business Type</label>
                    <input type="text" name="business_type" value="{{ old('business_type', $tenant->business_type) }}"
                           placeholder="e.g. Retail, Consulting"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
            </div>

            {{-- Tax identifiers --}}
            <div class="border-t pt-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Tax Registration</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tax Identification Number (TIN)</label>
                        <input type="text" name="tin" value="{{ old('tin', $tenant->tin) }}"
                               placeholder="e.g. 12345678-0001"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        @error('tin')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CAC Registration Number (RC No.)</label>
                        <input type="text" name="rc_number" value="{{ old('rc_number', $tenant->rc_number) }}"
                               placeholder="e.g. RC-123456"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Annual Turnover (₦)</label>
                        <input type="number" name="annual_turnover" min="0" step="1"
                               value="{{ old('annual_turnover', $tenant->annual_turnover) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        <p class="mt-0.5 text-xs text-gray-400">Used to determine CIT bracket (≤₦50M = 0%, >₦50M = 30%).</p>
                        @error('annual_turnover')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="vat_registered" value="0">
                                <input type="checkbox" name="vat_registered" value="1"
                                       {{ old('vat_registered', $tenant->vat_registered) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                <span class="text-sm font-medium text-gray-700">VAT Registered</span>
                            </label>
                            <input type="text" name="vat_number" value="{{ old('vat_number', $tenant->vat_number) }}"
                                   placeholder="VAT Registration Number"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_professional_firm" value="0">
                            <input type="checkbox" name="is_professional_firm" value="1"
                                   {{ old('is_professional_firm', $tenant->is_professional_firm) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="text-sm font-medium text-gray-700">Professional Services Firm</span>
                        </label>
                        <p class="text-xs text-gray-400 -mt-1">Professional firms pay 30% CIT regardless of turnover (NTA 2025).</p>
                    </div>
                </div>
            </div>

            {{-- Current tax status (read-only summary) --}}
            <div class="rounded-md bg-gray-50 border border-gray-200 p-4 text-xs text-gray-600 space-y-1">
                <p class="font-semibold text-gray-700">Current Tax Status</p>
                <p>CIT Bracket: <strong>{{ ucfirst($tenant->tax_category ?? '—') }}</strong>
                   @if($tenant->is_professional_firm) <span class="text-indigo-600">(Professional Firm)</span> @endif
                </p>
                <p>VAT: <strong>{{ $tenant->vat_registered ? 'Registered — ' . ($tenant->vat_number ?: 'No VRN') : 'Not registered' }}</strong></p>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Save Company Details
                </button>
            </div>
        </form>
    </div>

    {{-- ─── Invoice Appearance ──────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-lg shadow p-6" x-data="colorPicker('{{ $tenant->accentColor() }}')">
        <div class="mb-5">
            <h2 class="text-base font-semibold">Invoice &amp; Quote Appearance</h2>
            <p class="text-sm text-gray-500 mt-0.5">Choose an accent colour that appears on PDF invoice and quote headers, table rows, and dividers.</p>
        </div>

        <form method="POST" action="{{ route('settings.company.update') }}" class="space-y-5">
            @csrf
            @method('PATCH')
            <input type="hidden" name="invoice_accent_color" x-bind:value="selected">

            {{-- Preset swatches --}}
            <div>
                <p class="text-sm font-medium text-gray-700 mb-3">Preset themes</p>
                <div class="flex flex-wrap gap-3">
                    @php
                    $presets = [
                        ['#008751', 'Naija Green'],
                        ['#1e3a5f', 'Navy'],
                        ['#5b21b6', 'Purple'],
                        ['#881337', 'Burgundy'],
                        ['#0f766e', 'Teal'],
                        ['#9a3412', 'Burnt Orange'],
                        ['#334155', 'Slate'],
                        ['#1d4ed8', 'Royal Blue'],
                    ];
                    @endphp
                    @foreach($presets as [$hex, $label])
                    <button type="button"
                            @click="select('{{ $hex }}')"
                            title="{{ $label }}"
                            class="w-9 h-9 rounded-full border-2 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :class="selected === '{{ $hex }}' ? 'border-gray-900 scale-110 shadow-md' : 'border-transparent hover:border-gray-400'"
                            style="background-color: {{ $hex }}">
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Custom hex --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Custom colour</label>
                <div class="flex items-center gap-3">
                    <input type="color" x-model="selected" @input="selected = $event.target.value"
                           class="h-10 w-14 cursor-pointer rounded border border-gray-300 p-0.5 bg-white">
                    <input type="text" x-model="selected" @input="onHexInput($event.target.value)"
                           maxlength="7" placeholder="#008751"
                           class="w-28 rounded-md border-gray-300 shadow-sm text-sm font-mono focus:ring-green-500 focus:border-green-500">
                    <span class="text-xs text-gray-400">e.g. #1e3a5f</span>
                </div>
                @error('invoice_accent_color')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Live preview --}}
            <div>
                <p class="text-sm font-medium text-gray-700 mb-2">Preview</p>
                <div class="border border-gray-200 rounded-lg overflow-hidden max-w-sm shadow-sm">
                    <div class="px-4 py-3 flex items-center justify-between"
                         :style="'background-color:' + selected + '; color:' + textColor">
                        <span class="text-xs font-bold uppercase tracking-wide">Description</span>
                        <span class="text-xs font-bold uppercase tracking-wide">Amount (₦)</span>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div class="flex justify-between px-4 py-2 text-xs text-gray-700 bg-white">
                            <span>Consulting Services — Q1</span><span>150,000.00</span>
                        </div>
                        <div class="flex justify-between px-4 py-2 text-xs text-gray-700 bg-gray-50">
                            <span>Annual Software Licence</span><span>75,000.00</span>
                        </div>
                        <div class="flex justify-between px-4 py-2 text-xs font-bold"
                             :style="'background-color:' + selected + '; color:' + textColor">
                            <span>TOTAL DUE</span><span>225,000.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <button type="button" @click="reset()" class="text-sm text-gray-500 hover:underline">
                    Reset to Naija Green
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Save Appearance
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function colorPicker(initial) {
    return {
        selected: initial || '#008751',
        get textColor() {
            const hex = this.selected.replace('#', '');
            if (hex.length !== 6) return '#ffffff';
            const r = parseInt(hex.slice(0,2), 16);
            const g = parseInt(hex.slice(2,4), 16);
            const b = parseInt(hex.slice(4,6), 16);
            const lum = (0.299*r + 0.587*g + 0.114*b) / 255;
            return lum > 0.5 ? '#111827' : '#ffffff';
        },
        select(hex) { this.selected = hex; },
        reset()     { this.selected = '#008751'; },
        onHexInput(val) {
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) this.selected = val;
        },
    };
}
</script>
@endsection
