@extends('layouts.app')

@section('page-title', 'NRS e-Invoicing Setup')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">NRS e-Invoicing Credentials</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Configure your NRS API credentials to enable e-Invoice submission.
                All values are encrypted at rest.
            </p>
            <p class="text-sm text-gray-500 mt-0.5">
                Please note that the NRS Form submission may involve addittional charges as levied from your chosen Access Provider
            </p>
        </div>
        @if($hasActive)
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
            ● Active
        </span>
        @else
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
            ○ Not configured
        </span>
        @endif
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
        {{ session('error') }}
    </div>
    @endif

    {{-- Credentials form --}}
    <div class="bg-white rounded-lg shadow p-6 space-y-5">
        <form method="POST" action="{{ route('settings.firs.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">Service ID <span class="text-red-500">*</span></label>
                <input type="text" name="service_id"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                       placeholder="Your NRS Service ID"
                       value="{{ old('service_id') }}"
                       autocomplete="off">
                @error('service_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @if($credential)
                <p class="mt-1 text-xs text-gray-400">Currently set. Leave blank fields to keep existing values — or re-enter all to replace.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">API Key <span class="text-red-500">*</span></label>
                <input type="password" name="api_key"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                       placeholder="Your NRS API Key"
                       autocomplete="new-password">
                @error('api_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Secret Key <span class="text-red-500">*</span></label>
                <input type="password" name="secret_key"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500"
                       placeholder="Your NRS Secret Key"
                       autocomplete="new-password">
                @error('secret_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Public Key <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea name="public_key" rows="4"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm font-mono focus:ring-green-500 focus:border-green-500"
                          placeholder="Paste Base64-decoded PEM public key from crypto_keys.txt">{{ old('public_key') }}</textarea>
                @error('public_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Certificate <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea name="certificate" rows="4"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm font-mono focus:ring-green-500 focus:border-green-500"
                          placeholder="Paste Base64-decoded certificate from crypto_keys.txt">{{ old('certificate') }}</textarea>
                @error('certificate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-between pt-2">
                @if($hasActive)
                <form method="POST" action="{{ route('settings.firs.deactivate') }}" class="inline">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Deactivate NRS credentials? Invoices will no longer be submitted until you re-activate.')"
                            class="text-sm text-red-600 hover:text-red-800">
                        Deactivate credentials
                    </button>
                </form>
                @else
                <span></span>
                @endif

                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Save Credentials
                </button>
            </div>
        </form>
    </div>

    {{-- Info box --}}
    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-xs text-blue-700 space-y-1">
        <p class="font-semibold text-blue-800">About NRS e-Invoicing</p>
        <ul class="list-disc list-inside space-y-0.5">
            <li>Credentials are obtained from the <strong>NRS TaxPro Max portal</strong> under e-Invoicing → API Keys.</li>
            <li>The sandbox base URL is <code class="font-mono">https://sandbox.einvoice.firs.gov.ng</code>. Set <code class="font-mono">FIRS_BASE_URL</code> in your <code>.env</code> to switch to production.</li>
            <li>All credential fields are <strong>encrypted at rest</strong> using your application key.</li>
            <li>After saving, use the <strong>Submit to FIRS</strong> button on any sent/paid invoice to initiate e-Invoicing.</li>
        </ul>
    </div>

</div>
@endsection
