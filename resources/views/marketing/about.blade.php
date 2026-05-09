@extends('marketing.layouts.app')

@section('title', 'About Us — AccountTaxNG | Bytestream Technologies')
@section('meta_description', 'AccountTaxNG is a product of Bytestream Technologies — a Nigerian technology company building reliable business software to solve the real financial challenges of Nigerian SMEs.')

@section('content')

{{-- HERO --}}
<section class="gradient-hero pt-32 pb-20 lg:pt-44 lg:pb-28 relative overflow-hidden">
    <div class="absolute inset-0 grid-bg opacity-30 pointer-events-none"></div>
    <div class="absolute right-0 top-0 w-96 h-96 rounded-full blur-3xl opacity-10 pointer-events-none"
         style="background:radial-gradient(circle,#D4AF37,transparent)"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="section-badge mb-6">About Us</div>
        <h1 class="font-display text-4xl lg:text-5xl xl:text-6xl font-900 text-white leading-tight max-w-3xl">
            Built for You,<br>
            <span class="gradient-text">With You.</span>
        </h1>
        <p class="mt-6 text-lg text-slate-300 leading-relaxed max-w-2xl">
            AccountTaxNG is a product of Bytestream Technologies — a Nigerian technology company on a mission to make accounting and tax compliance as natural as sending a WhatsApp message.
        </p>
    </div>
</section>

{{-- MISSION STATEMENT --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <div class="section-badge mb-5">Our Mission</div>
                <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                    Closing the compliance gap for Nigerian SMEs
                </h2>
                <p class="mt-5 text-[#64748B] leading-relaxed">
                    Nigeria has over 40 million micro, small, and medium enterprises. The vast majority manage their finances on paper ledgers or basic spreadsheets — making tax compliance an annual ordeal, and financial visibility nearly impossible.
                </p>
                <p class="mt-4 text-[#64748B] leading-relaxed">
                    AccountTaxNG exists to change that. We believe every Nigerian business — no matter its size — deserves access to professional-grade accounting tools that are affordable, easy to use, and built around Nigeria's actual tax laws.
                </p>
                <p class="mt-4 text-[#64748B] leading-relaxed">
                    Our tagline isn't just a slogan. <strong class="text-[#1E293B]">"Built for You, With You"</strong> means we build features based on real feedback from real Nigerian business owners and accountants. Our community forum is open, and our roadmap is shaped by the people who use the platform every day.
                </p>
            </div>

            {{-- Values grid --}}
            <div class="grid grid-cols-2 gap-5">
                @foreach([
                    ['Compliance First','Every calculation, every report, every form — aligned with NRS regulations and the latest Finance Act.','M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
                    ['Radical Simplicity','Complex tax law, simple interface. We take the hard work out of compliance so you can focus on your business.','M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18'],
                    ['Built for Nigeria','We don\'t adapt foreign tools for Nigeria. We build Nigerian-first — with Naira, FIRS, State IRS, NHF, and PFAs at the core.','M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3'],
                    ['Community Driven','Our users shape our roadmap. Feature requests from the community are triaged and voted on — the best ideas ship next.','M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
                ] as [$title, $desc, $icon])
                <div class="bg-[#F5F7FA] rounded-2xl p-5 border border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-[#0A1A2F] flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                        </svg>
                    </div>
                    <h3 class="font-display font-700 text-sm text-[#1E293B] mb-1.5">{{ $title }}</h3>
                    <p class="text-xs text-[#64748B] leading-relaxed">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- BYTESTREAM TECHNOLOGIES --}}
<section class="py-20 lg:py-28 bg-[#F5F7FA]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">

            {{-- Company stats --}}
            <div class="grid grid-cols-2 gap-5">
                @foreach([
                    ['🇳🇬', 'Nigerian Company', 'Registered in Nigeria, built by Nigerians, for Nigeria'],
                    ['🏗️', 'SME-Focused', 'Purpose-built for the Nigerian SME market'],
                    ['🔒', 'NDPR Compliant', 'Full compliance with Nigerian data protection laws'],
                    ['⚡', 'Continuously Updated', 'Finance Act changes reflected automatically in the platform'],
                ] as [$emoji, $title, $desc])
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm text-center">
                    <div class="text-3xl mb-3">{{ $emoji }}</div>
                    <h3 class="font-display font-700 text-sm text-[#1E293B] mb-1">{{ $title }}</h3>
                    <p class="text-xs text-[#64748B] leading-relaxed">{{ $desc }}</p>
                </div>
                @endforeach
            </div>

            <div>
                <div class="section-badge mb-5">Bytestream Technologies</div>
                <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                    A Nigerian tech company solving Nigerian problems
                </h2>
                <p class="mt-5 text-[#64748B] leading-relaxed">
                    Bytestream Technologies was founded with a singular vision: to build business software that actually works for Nigerian companies — not software built for the US or UK market and awkwardly adapted for local use.
                </p>
                <p class="mt-4 text-[#64748B] leading-relaxed">
                    Nigerian SMEs face unique challenges. The tax framework is complex and evolving. The Finance Act changes regularly. NRS is digitising. Currency volatility affects planning. And most SME owners are not accountants.
                </p>
                <p class="mt-4 text-[#64748B] leading-relaxed">
                    AccountTaxNG is our answer. A platform that respects the intelligence of the Nigerian business owner, speaks their language, uses Naira natively, and stays current with NRS regulations without requiring the user to be a tax expert.
                </p>

                <div class="mt-8 p-5 bg-[#0A1A2F] rounded-2xl">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#D4AF37]/15 border border-[#D4AF37]/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-600 text-white mb-1">Our commitment to you</p>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                We update AccountTaxNG within 30 days of any Finance Act change. If NRS modifies a VAT rate, WHT schedule, or CIT band, your calculations update automatically — no action needed from you.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- WHY NIGERIA NEEDS THIS --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="section-badge mb-4 mx-auto">The Problem We're Solving</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                Nigerian SMEs deserve better tools
            </h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['The Compliance Burden','Over 70% of Nigerian SMEs are non-compliant with tax obligations — not from dishonesty, but from complexity. VAT, WHT, CIT, and PAYE each have their own rules, deadlines, and filing requirements. Most SMEs lack the in-house capacity to manage all four correctly.','text-red-600 bg-red-50'],
                ['The Cost of Manual Work','The average SME owner or finance manager spends 8–12 hours per month on manual bookkeeping and tax preparation. That\'s time away from the business. AccountTaxNG reduces that to under 2 hours — without a large accounting team.','text-amber-600 bg-amber-50'],
                ['Our Approach','We automate the mechanics of compliance — VAT calculation, WHT tracking, payroll PAYE — so that doing business correctly is the natural default, not an extra effort. Every transaction recorded is a step towards a filed return.','text-green-600 bg-green-50'],
            ] as [$title, $desc, $class])
            <div class="rounded-2xl p-6 border border-slate-100">
                <div class="w-10 h-10 rounded-xl {{ $class }} flex items-center justify-center mb-5">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <h3 class="font-display font-700 text-lg text-[#1E293B] mb-3">{{ $title }}</h3>
                <p class="text-sm text-[#64748B] leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CONTACT / CTA --}}
<section class="py-20 bg-[#0A1A2F] relative overflow-hidden">
    <div class="absolute inset-0 dot-pattern opacity-20 pointer-events-none"></div>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
        <div class="section-badge mb-6 mx-auto">Get in Touch</div>
        <h2 class="font-display text-3xl lg:text-4xl font-800 text-white">We'd love to hear from you</h2>
        <p class="mt-4 text-slate-300 leading-relaxed max-w-xl mx-auto">
            Whether you have a question, want to book a demo, or want to share feedback — our team is available and ready to talk.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('marketing.contact') }}" class="btn-gold inline-flex items-center gap-2 px-7 py-3.5 rounded-xl text-sm font-700">
                Contact Us
            </a>
            <a href="{{ route('register') }}" class="btn-outline-white inline-flex items-center gap-2 px-7 py-3.5 rounded-xl text-sm font-600">
                Start Free Trial
            </a>
        </div>
        <p class="mt-6 text-sm text-slate-400">
            Or email us directly at
            <a href="mailto:hello@accounttaxng.com" class="text-[#D4AF37] hover:underline">hello@accounttaxng.com</a>
        </p>
    </div>
</section>

@endsection
