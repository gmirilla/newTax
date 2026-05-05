<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email — NaijaBooks</title>
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

    {{-- Card --}}
    <div class="bg-white rounded-lg shadow px-8 py-8 text-center space-y-5">

        <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
        </div>

        <div>
            <h1 class="text-xl font-bold text-gray-900">Check your inbox</h1>
            <p class="text-sm text-gray-500 mt-1.5">
                We sent a verification link to
                <strong class="text-gray-700">{{ $user->email }}</strong>.
                Click it to activate your account.
            </p>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-md px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="w-full py-2.5 bg-green-600 text-white font-semibold text-sm rounded-md hover:bg-green-700 transition-colors">
                Resend verification email
            </button>
        </form>

    </div>

    <div class="text-center text-xs text-gray-400 space-y-1">
        <p>Wrong email address?
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-green-600 underline">Sign out</button>
            </form>
            and register again.
        </p>
    </div>

</div>
</body>
</html>
