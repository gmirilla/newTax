<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In — AccountTaxNG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="min-h-screen bg-[#F5F7FA] font-sans antialiased">

<div class="min-h-screen flex">
    {{-- Left panel (hidden on mobile) --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-[45%] bg-[#0A1A2F] flex-col justify-between p-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20 pointer-events-none"
             style="background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 40px 40px;"></div>
        <div class="absolute bottom-0 right-0 w-80 h-80 rounded-full blur-3xl opacity-10 pointer-events-none"
             style="background:radial-gradient(circle,#D4AF37,transparent)"></div>

        <div class="relative">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#D4AF37,#C9A227)">
                    <span class="text-xs font-black text-[#0A1A2F]">AT</span>
                </div>
                <span class="font-display text-white text-lg font-800">Account<span class="text-[#D4AF37]">Tax</span>NG</span>
            </a>
        </div>

        <div class="relative space-y-8">
            <div>
                <p class="text-3xl font-display font-800 text-white leading-tight">
                    Your accounts.<br>Your taxes.<br>
                    <span style="background:linear-gradient(135deg,#D4AF37,#E8C84A);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">
                        All in one place.
                    </span>
                </p>
            </div>
            <div class="space-y-4">
                @foreach(['VAT auto-calculated on every invoice','Finance Act-aligned tax computations','Payroll & PAYE in minutes','Secure, encrypted cloud storage'] as $feature)
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-full bg-[#D4AF37]/20 border border-[#D4AF37]/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-[#D4AF37]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </div>
                    <span class="text-sm text-slate-300">{{ $feature }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <p class="relative text-xs text-slate-500">
            &copy; {{ date('Y') }} Bytestream Technologies &nbsp;·&nbsp; Made in Nigeria 🇳🇬
        </p>
    </div>

    {{-- Right panel: Login form --}}
    <div class="flex-1 flex flex-col items-center justify-center px-4 py-12 sm:px-10">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-8 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:linear-gradient(135deg,#D4AF37,#C9A227)">
                    <span class="text-xs font-black text-[#0A1A2F]">AT</span>
                </div>
                <span class="font-display text-[#0A1A2F] text-lg font-800">Account<span class="text-[#D4AF37]">Tax</span>NG</span>
            </a>
        </div>

        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="font-display text-2xl font-800 text-[#0A1A2F]">Welcome back</h1>
                <p class="text-sm text-[#64748B] mt-1.5">Sign in to your AccountTaxNG workspace</p>
            </div>

            @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 rounded-xl px-4 py-3.5">
                @foreach($errors->all() as $error)
                <p class="text-sm text-red-700">{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Email address</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com"
                           class="input-field" required autocomplete="email">
                </div>
                <div>
                    <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Password</label>
                    <input type="password" name="password" placeholder="••••••••"
                           class="input-field" required autocomplete="current-password">
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-[#64748B] cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#D4AF37]">
                        Remember me
                    </label>
                </div>
                <button type="submit"
                        class="btn-gold w-full py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 mt-2">
                    Sign In
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-[#64748B]">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-[#D4AF37] font-600 hover:underline ml-1">Start free trial</a>
            </p>

            {{-- Demo credentials --}}
            <div class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-4">
                <p class="text-xs font-700 text-amber-800 mb-2">Demo Credentials</p>
                <p class="text-xs text-amber-700">Admin: admin@adetokunboventures.ng / password</p>
                <p class="text-xs text-amber-700 mt-0.5">Mid-size co: admin@chukwuemekatrading.com / password</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
