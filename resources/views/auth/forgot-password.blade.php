<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — AccountTaxNG</title>
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    @if(config('turnstile.site_key'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    <script>
    tailwind.config = {
        theme: { extend: { fontFamily: { sans: ['Inter','system-ui','sans-serif'], display: ['Manrope','Inter','sans-serif'] } } }
    }
    </script>
    <style>
        .btn-gold { background: linear-gradient(135deg, #D4AF37, #C9A227); color: #0A1A2F; font-weight: 700; transition: all 0.2s; }
        .btn-gold:hover { background: linear-gradient(135deg, #E8C84A, #D4AF37); box-shadow: 0 8px 20px rgba(212,175,55,0.35); }
        .input-field { width:100%; padding: 12px 16px; border-radius: 12px; border: 1.5px solid #E2E8F0; font-size: 14px; color: #1E293B; transition: border-color 0.2s, box-shadow 0.2s; outline: none; font-family: inherit; }
        .input-field:focus { border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.15); }
    </style>
</head>
<body class="min-h-screen bg-[#F5F7FA] font-sans antialiased flex items-center justify-center px-4 py-12">

<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#D4AF37,#C9A227)">
                <span class="text-xs font-black text-[#0A1A2F]">AT</span>
            </div>
            <span class="font-display text-[#0A1A2F] text-lg font-800">Account<span class="text-[#D4AF37]">Tax</span>NG</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-8">
        <h1 class="font-display text-xl font-800 text-[#0A1A2F]">Forgot your password?</h1>
        <p class="text-sm text-[#64748B] mt-1.5">
            Enter the email address on your account and we'll send you a link to reset it.
        </p>

        @if(session('status'))
        <div class="mt-5 bg-green-50 border border-green-200 rounded-xl px-4 py-3.5 text-sm text-green-700">
            {{ session('status') }}
        </div>
        @endif

        @if($errors->any())
        <div class="mt-5 bg-red-50 border border-red-200 rounded-xl px-4 py-3.5">
            @foreach($errors->all() as $error)
            <p class="text-sm text-red-700">{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4 mt-6">
            @csrf
            {{-- Honeypot: left blank by humans, hidden off-screen so it's invisible to real users --}}
            <div style="position:absolute; left:-9999px; top:-9999px;" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Email address</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com"
                       class="input-field" required autocomplete="email" autofocus>
            </div>

            @if(config('turnstile.site_key'))
            <div class="cf-turnstile" data-sitekey="{{ config('turnstile.site_key') }}"></div>
            @endif

            <button type="submit"
                    class="btn-gold w-full py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 mt-2">
                Send Reset Link
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-[#64748B]">
            <a href="{{ route('login') }}" class="text-[#D4AF37] font-600 hover:underline">&larr; Back to login</a>
        </p>
    </div>
</div>
</body>
</html>
