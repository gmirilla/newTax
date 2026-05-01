<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Invitation — NaijaBooks</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 py-12">

<div class="max-w-md w-full space-y-6">

    {{-- Logo --}}
    <div class="text-center">
        <div class="inline-flex items-center gap-2">
            <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">N</span>
            </div>
            <span class="text-xl font-bold text-gray-900">NaijaBooks</span>
        </div>
    </div>

    {{-- Invite info --}}
    <div class="bg-green-50 border border-green-200 rounded-lg px-5 py-4 text-sm text-green-800">
        <p class="font-semibold">You've been invited to join <strong>{{ $invite->tenant?->name }}</strong></p>
        <p class="mt-0.5 text-green-700">Role: <strong>{{ ucfirst($invite->role) }}</strong> &middot; Invited as: {{ $invite->email }}</p>
    </div>

    {{-- Flash errors --}}
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg px-5 py-4">
        <ul class="text-sm text-red-700 list-disc pl-4">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Form --}}
    <div class="bg-white rounded-lg shadow px-8 py-8 space-y-5">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Create your account</h1>
            <p class="text-sm text-gray-500 mt-0.5">Your email is pre-set to <strong>{{ $invite->email }}</strong>.</p>
        </div>

        <form method="POST" action="{{ route('invite.accept', $invite->token) }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
                <input type="text" id="name" name="name" required
                       value="{{ old('name') }}"
                       placeholder="Your full name"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="Min 8 chars, mixed case + number"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       placeholder="Re-enter your password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
            </div>

            <button type="submit"
                    class="w-full py-2.5 bg-green-600 text-white font-semibold text-sm rounded-md hover:bg-green-700 transition-colors">
                Create Account & Join Team
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-400">
        This invitation expires {{ $invite->expires_at->diffForHumans() }}.
        Already have an account? <a href="{{ route('login') }}" class="text-green-600 underline">Log in</a>.
    </p>

</div>
</body>
</html>
