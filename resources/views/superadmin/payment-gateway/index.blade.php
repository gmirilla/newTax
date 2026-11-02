@extends('superadmin.layout')

@section('page-title', 'Payment Gateway')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Paystack Credentials</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Keys stored here override the <code class="font-mono">.env</code> values platform-wide.
                All values are encrypted at rest.
            </p>
        </div>
        @if($hasSecretKey)
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
            ● Configured
        </span>
        @else
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
            ○ Using .env
        </span>
        @endif
    </div>

    @if(session('success'))
    <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6 space-y-5">
        <form method="POST" action="{{ route('superadmin.payment-gateway.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Secret Key @if(!$hasSecretKey)<span class="text-red-500">*</span>@endif
                </label>
                <input type="password" name="secret_key"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="sk_live_... or sk_test_..."
                       autocomplete="new-password">
                @error('secret_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-gray-400">
                    @if($hasSecretKey) Currently set. @else Not set — falling back to <code class="font-mono">PAYSTACK_SECRET_KEY</code> in .env. @endif
                    Leave blank to keep the existing value.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Public Key <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="password" name="public_key"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="pk_live_... or pk_test_..."
                       autocomplete="new-password">
                @error('public_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-gray-400">
                    @if($hasPublicKey) Currently set. @else Not set — falling back to <code class="font-mono">PAYSTACK_PUBLIC_KEY</code> in .env. @endif
                    Leave blank to keep the existing value.
                </p>
            </div>

            @if($credentialsSetAt)
            <p class="text-xs text-gray-400">
                Last updated {{ $credentialsSetAt->diffForHumans() }}
                @if($updatedBy) by {{ $updatedBy->name }} @endif
            </p>
            @endif

            <div class="flex items-center justify-end pt-2">
                <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    Save Credentials
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
