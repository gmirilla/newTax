@extends('layouts.app')

@section('page-title', 'My Profile')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Profile details --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-base font-semibold mb-4">Personal Details</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <input type="text" value="{{ ucfirst($user->role) }}" disabled
                           class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 shadow-sm text-sm text-gray-500 cursor-not-allowed">
                    <p class="mt-0.5 text-xs text-gray-400">Role is managed by your company admin.</p>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                        class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Change password --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-base font-semibold mb-4">Change Password</h2>
        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700">Current Password <span class="text-red-500">*</span></label>
                <input type="password" name="current_password" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">New Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                    <p class="mt-0.5 text-xs text-gray-400">Min 8 characters, mixed case, numbers.</p>
                    @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm New Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                        class="px-5 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-900">
                    Update Password
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
