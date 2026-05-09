<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — AccountTaxNG</title>
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
        .input-field { width:100%; padding: 11px 16px; border-radius: 12px; border: 1.5px solid #E2E8F0; font-size: 14px; color: #1E293B; transition: border-color 0.2s, box-shadow 0.2s; outline: none; font-family: inherit; background: white; }
        .input-field:focus { border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.15); }
        .section-label { display: block; font-size: 11px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.07em; padding: 0 0 8px 0; border-bottom: 1px solid #F1F5F9; margin-bottom: 16px; margin-top: 8px; }
    </style>
</head>
<body class="min-h-screen bg-[#F5F7FA] font-sans antialiased">

<div class="min-h-screen flex">
    {{-- Left panel --}}
    <div class="hidden lg:flex lg:w-[40%] bg-[#0A1A2F] flex-col justify-between p-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20 pointer-events-none"
             style="background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 40px 40px;"></div>
        <div class="absolute top-0 right-0 w-80 h-80 rounded-full blur-3xl opacity-10 pointer-events-none"
             style="background:radial-gradient(circle,#D4AF37,transparent)"></div>

        <div class="relative">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#D4AF37,#C9A227)">
                    <span class="text-xs font-black text-[#0A1A2F]">AT</span>
                </div>
                <span class="font-display text-white text-lg font-800">Account<span class="text-[#D4AF37]">Tax</span>NG</span>
            </a>
        </div>

        <div class="relative space-y-6">
            <p class="text-2xl font-display font-800 text-white leading-tight">
                Start managing your business finances the right way.
            </p>
            <div class="space-y-4">
                @foreach([
                    ['14-day free trial','Full access, no credit card required'],
                    ['FIRS-compliant','Finance Act 2023 aligned calculations'],
                    ['VAT automation','Returns computed as you trade'],
                    ['Payroll & PAYE','Automated staff payment calculations'],
                    ['Multi-user access','Invite your accountant or team'],
                ] as [$title,$desc])
                <div class="flex items-start gap-3">
                    <div class="w-5 h-5 rounded-full bg-[#D4AF37]/20 border border-[#D4AF37]/40 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-3 h-3 text-[#D4AF37]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-600 text-white">{{ $title }}</p>
                        <p class="text-xs text-slate-400">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <p class="relative text-xs text-slate-500">
            &copy; {{ date('Y') }} Bytestream Technologies &nbsp;·&nbsp; 🇳🇬 Made in Nigeria
        </p>
    </div>

    {{-- Right panel: Register form --}}
    <div class="flex-1 flex flex-col items-center justify-center px-4 py-10 sm:px-8 overflow-y-auto">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-6 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:linear-gradient(135deg,#D4AF37,#C9A227)">
                    <span class="text-xs font-black text-[#0A1A2F]">AT</span>
                </div>
                <span class="font-display text-[#0A1A2F] text-lg font-800">Account<span class="text-[#D4AF37]">Tax</span>NG</span>
            </a>
        </div>

        <div class="w-full max-w-lg">
            <div class="text-center mb-8">
                <h1 class="font-display text-2xl font-800 text-[#0A1A2F]">Create your free account</h1>
                <p class="text-sm text-[#64748B] mt-1.5">14-day full access trial. No credit card required.</p>
            </div>

            @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 rounded-xl px-4 py-3.5">
                @foreach($errors->all() as $error)
                <p class="text-sm text-red-700">{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-7">
                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <span class="section-label">Company Information</span>

                    <div>
                        <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Okonkwo Trading Ltd"
                               class="input-field" required>
                    </div>

                    <div>
                        <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Company Email <span class="text-red-500">*</span></label>
                        <input type="email" name="company_email" value="{{ old('company_email') }}" placeholder="admin@company.com"
                               class="input-field" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-600 text-[#1E293B] mb-1.5">TIN (FIRS)</label>
                            <input type="text" name="tin" value="{{ old('tin') }}" placeholder="1234567-0001"
                                   class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm font-600 text-[#1E293B] mb-1.5">State <span class="text-red-500">*</span></label>
                            <select name="state" class="input-field">
                                <option value="">— Select —</option>
                                @foreach(['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'] as $state)
                                <option value="{{ $state }}" {{ old('state') === $state ? 'selected' : '' }}>{{ $state }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Business Type</label>
                            <select name="business_type" class="input-field">
                                <option value="limited_liability">Limited Liability</option>
                                <option value="sole_proprietorship">Sole Proprietorship</option>
                                <option value="partnership">Partnership</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Annual Turnover (₦)</label>
                            <input type="number" name="annual_turnover" value="{{ old('annual_turnover') }}"
                                   placeholder="e.g. 25000000" min="0"
                                   class="input-field">
                        </div>
                    </div>

                    <span class="section-label">Your Account</span>

                    <div>
                        <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Your Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Amaka Okonkwo"
                               class="input-field" required>
                    </div>

                    <div>
                        <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Your Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="amaka@company.com"
                               class="input-field" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password" placeholder="8+ characters"
                                   class="input-field" required minlength="8">
                        </div>
                        <div>
                            <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password_confirmation" placeholder="••••••••"
                                   class="input-field" required>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn-gold w-full py-3.5 rounded-xl text-sm flex items-center justify-center gap-2">
                            Create Free Account
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </button>
                        <p class="text-xs text-center text-slate-400 mt-3">
                            By registering, you agree to our <a href="#" class="underline">Terms of Service</a> and <a href="#" class="underline">Privacy Policy</a>.
                        </p>
                    </div>
                </form>
            </div>

            <p class="mt-5 text-center text-sm text-[#64748B]">
                Already have an account?
                <a href="{{ route('login') }}" class="text-[#D4AF37] font-600 hover:underline ml-1">Sign in</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
