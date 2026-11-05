@extends('layouts.app')

@section('page-title', 'Create a New Client')

@section('content')
<div class="max-w-lg mx-auto space-y-6">

    <div>
        <a href="{{ route('partners.clients.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Clients</a>
        <h1 class="text-xl font-bold text-gray-900 mt-2">Create a New Client</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Sets up a brand-new company and links it to your firm immediately — no approval needed.
        </p>
    </div>

    @if($errors->any())
    <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc pl-4">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('partners.clients.store') }}" class="space-y-4">
            @csrf

            <h2 class="text-sm font-semibold text-gray-700">Company</h2>
            <div>
                <label class="block text-xs font-medium text-gray-700">Company name</label>
                <input type="text" name="company_name" value="{{ old('company_name') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Company email</label>
                <input type="email" name="company_email" value="{{ old('company_email') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
            </div>

            <h2 class="text-sm font-semibold text-gray-700 pt-2">The Client's Admin Login</h2>
            <p class="text-xs text-gray-400">
                This becomes the client's own admin account — they'll use it to log in directly if they ever want to.
            </p>
            <div>
                <label class="block text-xs font-medium text-gray-700">Admin name</label>
                <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Admin email</label>
                <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700">Password</label>
                    <input type="password" name="admin_password" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Confirm password</label>
                    <input type="password" name="admin_password_confirmation" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full py-2.5 bg-green-600 text-white font-semibold text-sm rounded-md hover:bg-green-700">
                    Create Client &amp; Link Access
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
