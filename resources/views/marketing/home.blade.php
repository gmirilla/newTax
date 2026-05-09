@extends('marketing.layouts.app')

@section('title', 'AccountTaxNG — Accounting & Tax Compliance Built for You, With You')
@section('meta_description', 'Cloud-based accounting and tax compliance platform built for Nigerian SMEs. Automate VAT returns, WHT, PAYE, generate invoices, and stay NRS-compliant with ease.')

@section('content')

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- HERO                                                    --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="gradient-hero grid-bg relative overflow-hidden pt-28 pb-16 lg:pt-36 lg:pb-24">

    {{-- Decorative glow --}}
    <div class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] rounded-full opacity-10 blur-3xl pointer-events-none"
         style="background: radial-gradient(ellipse, #D4AF37, transparent 70%)"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">

            {{-- Left: Copy --}}
            <div class="text-center lg:text-left">
                <div class="section-badge mb-6 mx-auto lg:mx-0">
                     Built for You, With You
                </div>

                <h1 class="font-display text-4xl sm:text-5xl lg:text-[3.5rem] xl:text-[4rem] font-900 leading-[1.1] tracking-tight">
                    Accounting &amp; Tax<br>
                    <span class="gradient-text">Compliance Made</span><br>
                    Simple.
                </h1>

                <p class="mt-6 text-lg leading-relaxed max-w-xl mx-auto lg:mx-0">
                    The platform that combines bookkeeping, VAT automation, payroll PAYE, and NRS compliance in one place — built specifically for Nigerian businesses.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                    <a href="{{ route('register') }}"
                       class="btn-gold inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl text-sm font-700 shadow-lg shadow-[#D4AF37]/20">
                        Start Free Trial
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                    <a href="{{ route('marketing.contact') }}#demo"
                       class="btn-outline-white inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl text-sm font-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/>
                        </svg>
                        Book a Demo
                    </a>
                </div>

                <p class="mt-5 text-xs text-slate-500">
                    14-day free trial &nbsp;·&nbsp; No Debit card required &nbsp;·&nbsp; Cancel anytime
                </p>

                {{-- Mini trust bar --}}
                <div class="mt-8 flex flex-wrap items-center gap-5 justify-center lg:justify-start">
                    <div class="flex items-center gap-1.5 text-xs text-slate-400">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        NRS Compliant
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Finance Act Aligned
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        256-bit Encryption
                    </div>
                </div>
            </div>

            {{-- Right: Dashboard Mockup --}}
            <div class="relative lg:pl-8">
                {{-- Glow behind mockup --}}
                <div class="absolute -inset-6 rounded-3xl opacity-20 blur-2xl pointer-events-none"
                     style="background: radial-gradient(ellipse, #D4AF37, transparent 70%)"></div>
                <img src="{{ asset('images/marketing/dashboard-mockup.png') }}" alt="Dashboard Mockup" class="relative rounded-3xl shadow-lg shadow-[#0A1A2F]/20">
                

                {{-- Floating badge --}}
                <div class="absolute -bottom-4 -left-4 bg-white rounded-xl shadow-xl px-4 py-3 border border-slate-100 hidden lg:flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-800">VAT Return Filed</p>
                        <p class="text-[10px] text-slate-500">Automatically submitted to FIRS</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- STATS BAR                  ['500+', 'Businesses Onboarded', 'and growing every month'],
                ['₦2.1B+', 'Invoices Processed', 'worth of transactions managed'],                                             --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="bg-white border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            @foreach([

                ['99.9%', 'Platform Uptime', 'reliable access, always'],
                ['100%', 'NRS Compliant', 'Nigerian Tax Act 2025 aligned'],
            ] as [$stat, $label, $sub])
            <div class="text-center">
                <p class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F]">{{ $stat }}</p>
                <p class="mt-1 text-sm font-600 text-slate-700">{{ $label }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $sub }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- CORE FEATURES GRID                                      --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="py-20 lg:py-28 bg-[#F5F7FA]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="section-badge mb-4 mx-auto">Everything You Need</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                One platform for all your<br>financial operations
            </h2>
            <p class="mt-4 text-[#64748B] leading-relaxed">
                From daily bookkeeping to quarterly tax filings — AccountTaxNG handles the complexity so you can focus on growing your business.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $features = [
                ['icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','title'=>'Invoicing & Quotes','desc'=>'Generate professional invoices and quotes in seconds. Auto-apply VAT, track payment status, and email directly to clients.','badge'=>'Core'],
                ['icon'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z','title'=>'Expense Tracking','desc'=>'Log business expenses, categorise by type, attach receipts, and track WHT deductions automatically.','badge'=>'Core'],
                ['icon'=>'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M9 14h.01M12 14h.01M15 14h.01M4 3h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1z','title'=>'VAT Automation','desc'=>'Auto-calculate VAT from every invoice and expense. Generate FIRS-ready VAT returns with one click.','badge'=>'Tax'],
                ['icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z','title'=>'Payroll & PAYE','desc'=>'Manage staff salaries, auto-calculate PAYE deductions, generate payslips, and track pension contributions.','badge'=>'HR'],
                ['icon'=>'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','title'=>'Financial Reports','desc'=>'Profit & Loss, Balance Sheet, Cash Flow, and VAT Summary reports. Download as PDF or Excel instantly.','badge'=>'Reports'],
                ['icon'=>'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z','title'=>'Company Income Tax','desc'=>'Finance Act-aligned CIT computation. Track WHT credits, manage tax schedules, and stay audit-ready.','badge'=>'Tax'],
            ];
            @endphp

            @foreach($features as $f)
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm card-hover group">
                <div class="flex items-start justify-between mb-5">
                    <div class="feature-icon-wrap group-hover:bg-[#0A1A2F] transition-colors duration-200">
                        <svg class="w-5 h-5 text-[#0A1A2F] group-hover:text-[#D4AF37] transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-700 px-2.5 py-1 rounded-full
                        {{ $f['badge'] === 'Tax' ? 'bg-amber-50 text-amber-700' : ($f['badge'] === 'HR' ? 'bg-blue-50 text-blue-700' : ($f['badge'] === 'Reports' ? 'bg-purple-50 text-purple-700' : 'bg-green-50 text-green-700')) }}">
                        {{ $f['badge'] }}
                    </span>
                </div>
                <h3 class="font-display font-700 text-[#1E293B] text-lg mb-2">{{ $f['title'] }}</h3>
                <p class="text-sm text-[#64748B] leading-relaxed">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('marketing.features') }}"
               class="inline-flex items-center gap-2 text-sm font-600 text-[#0A1A2F] hover:text-[#D4AF37] transition-colors">
                View all features
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- TAX COMPLIANCE HIGHLIGHT                                --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="py-20 lg:py-28 bg-[#0A1A2F] relative overflow-hidden">
    <div class="absolute inset-0 grid-bg opacity-30 pointer-events-none"></div>
    <div class="absolute right-0 top-1/2 -translate-y-1/2 w-96 h-96 rounded-full blur-3xl opacity-10 pointer-events-none"
         style="background: radial-gradient(circle, #D4AF37, transparent 70%)"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-14 items-center">
            <div>
                <div class="section-badge mb-6">Tax Compliance</div>
                <h2 class="font-display text-3xl lg:text-4xl font-800 text-white leading-tight">
                    Never miss a tax deadline again.
                </h2>
                <p class="mt-5 text-slate-300 leading-relaxed">
                    AccountTaxNG is built around Nigeria's tax framework. Every transaction is automatically classified to generate FIRS-ready returns — so compliance becomes a by-product of doing business, not a quarterly panic.
                </p>

                <div class="mt-8 space-y-4">
                    @foreach([
                        ['VAT Returns', 'Auto-calculated from invoices and purchases. Download or submit directly.'],
                        ['Withholding Tax (WHT)', 'Track WHT deducted on supplier payments. Generate credit notes automatically.'],
                        ['Company Income Tax (CIT)', 'Finance Act-aligned CIT computation with WHT credits applied.'],
                        ['PAYE Remittances', 'Payroll PAYE totals ready for monthly State IRS remittance.'],
                        ['NRS e-Invoice (Coming Soon)', 'NRS NRS integration for digital invoice submission — ready for rollout.'],
                    ] as [$title, $desc])
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-5 h-5 rounded-full bg-[#D4AF37]/20 border border-[#D4AF37]/40 flex items-center justify-center mt-0.5">
                            <svg class="w-3 h-3 text-[#D4AF37]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-600 text-white">{{ $title }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Compliance cards --}}
            <div class="grid grid-cols-2 gap-4">
                @foreach([
                    ['VAT','7.5%','Finance Act 2023','₦372,225 due 21 Apr','amber','Computed'],
                    ['WHT','10%','Supplier Payments','₦48,500 credited','blue','Tracked'],
                    ['CIT','30%','Annual Profit Tax','₦0 payable (WHT offset)','green','Optimised'],
                    ['PAYE','Auto','Per Finance Act','₦156,800 remitted','purple','Filed'],
                ] as [$tax,$rate,$label,$status,$color,$badge])
                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 hover:bg-white/8 transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-2xl font-display font-800 text-white">{{ $tax }}</span>
                        <span class="text-[10px] font-700 px-2 py-0.5 rounded-full
                            {{ $color === 'amber' ? 'bg-amber-500/20 text-amber-400' : ($color === 'blue' ? 'bg-blue-500/20 text-blue-400' : ($color === 'green' ? 'bg-green-500/20 text-green-400' : 'bg-purple-500/20 text-purple-400')) }}">
                            {{ $badge }}
                        </span>
                    </div>
                    <p class="text-sm font-600 text-[#D4AF37]">{{ $rate }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ $label }}</p>
                    <p class="text-xs text-slate-300 mt-2 font-medium">{{ $status }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- HOW IT WORKS                                            --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-14">
            <div class="section-badge mb-4 mx-auto">How It Works</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                Up and running in minutes
            </h2>
        </div>

        <div class="grid md:grid-cols-3 gap-8 relative">
            {{-- Connector line (desktop) --}}
            <div class="hidden md:block absolute top-8 left-1/3 right-1/3 h-px bg-gradient-to-r from-transparent via-[#D4AF37]/40 to-transparent"></div>

            @foreach([
                ['01','Register & Set Up','Create your account, add your business details, and invite your team. Takes less than 3 minutes.','M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                ['02','Connect Your Business','Import transactions, set up your chart of accounts, and configure VAT settings once.','M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                ['03','Stay Compliant Automatically','As you record transactions, AccountTaxNG computes your taxes in the background — ready to file when due.','M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
            ] as [$step,$title,$desc,$icon])
            <div class="relative text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#0A1A2F] mb-5 mx-auto shadow-lg shadow-[#0A1A2F]/20">
                    <svg class="w-7 h-7 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                    </svg>
                </div>
                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-2 text-[11px] font-800 text-[#D4AF37]">{{ $step }}</div>
                <h3 class="font-display font-700 text-lg text-[#1E293B] mb-2">{{ $title }}</h3>
                <p class="text-sm text-[#64748B] leading-relaxed max-w-xs mx-auto">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- FOR SMEs / FOR ACCOUNTANTS                              --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="py-20 lg:py-28 bg-[#F5F7FA]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-8">

            {{-- For SMEs --}}
            <div class="bg-white rounded-3xl p-8 lg:p-10 border border-slate-100 shadow-sm">
                <div class="w-12 h-12 rounded-2xl bg-[#0A1A2F] flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/></svg>
                </div>
                <span class="section-badge mb-4 inline-flex">For Business Owners</span>
                <h3 class="font-display text-2xl font-800 text-[#0A1A2F] mb-4">Run your finances without being an accountant</h3>
                <p class="text-[#64748B] leading-relaxed mb-6">AccountTaxNG speaks plain language. No complex accounting jargon — just a clear picture of your money, your taxes, and your business health.</p>
                <ul class="space-y-3">
                    @foreach(['See exactly what you owe in VAT each month','Know which customers owe you — and for how long','Understand your profit at a glance','Share access with your accountant securely','Pay your staff and handle PAYE automatically','Never be caught off guard at tax time'] as $item)
                    <li class="flex items-start gap-3 text-sm text-[#475569]">
                        <svg class="w-4 h-4 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="mt-8 btn-gold inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-700">
                    Start Your Free Trial
                </a>
            </div>
            <div class="bg-white rounded-3xl p-8 lg:p-10 border border-slate-100 shadow-sm">
                <img src="{{ asset('images/marketing/financeworkspace.webp') }}" alt="For SMEs" class="mx-auto">
                </div> 

            {{-- For Accountants --}}
            <div class="hidden" style="display: none;">
            <div class="bg-[#0A1A2F] rounded-3xl p-8 lg:p-10 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-48 h-48 rounded-full blur-3xl opacity-10 pointer-events-none"
                     style="background: radial-gradient(circle, #D4AF37, transparent)"></div>
                <div class="w-12 h-12 rounded-2xl bg-[#D4AF37]/15 border border-[#D4AF37]/30 flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                </div>
                <span class="section-badge mb-4 inline-flex">For Accountants</span>
                <h3 class="font-display text-2xl font-800 text-white mb-4">Manage multiple clients. One platform.</h3>
                <p class="text-slate-300 leading-relaxed mb-6">Serve more SME clients efficiently. AccountTaxNG gives you a professional collaboration workspace — view financials, prepare filings, and guide clients without back-and-forth emails.</p>
                <ul class="space-y-3">
                    @foreach(['Multi-client dashboard access from one login','Accountant role with full financial access','Generate VAT returns and CIT schedules instantly','Download audit-ready reports in seconds','Guided tax computation reduces manual error','Build your practice around compliant software'] as $item)
                    <li class="flex items-start gap-3 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-[#D4AF37] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('marketing.contact') }}#demo" class="mt-8 btn-outline-white inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-700">
                    Book an Accountant Demo
                </a>
            </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- TESTIMONIALS                                            --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="py-20 lg:py-28 bg-white" style="display: none;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-14">
            <div class="section-badge mb-4 mx-auto">Testimonials</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F]">Trusted by Nigerian businesses</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @php
            $testimonials = [
                [
                    'quote'   => 'AccountTaxNG completely changed how we handle VAT. Before, we spent days preparing returns. Now it literally takes 10 minutes at the end of each quarter. The NRS alignment gives us total peace of mind.',
                    'name'    => 'Amaka Okonkwo',
                    'role'    => 'CEO, Okonkwo Trading Ltd',
                    'city'    => 'Lagos',
                    'initial' => 'AO',
                    'color'   => 'bg-blue-600',
                ],
                [
                    'quote'   => 'As an accountant managing over 20 SME clients, AccountTaxNG has been a game-changer. I can prepare CIT schedules and VAT returns for multiple businesses from one platform. Highly recommended.',
                    'name'    => 'Biodun Adeleke',
                    'role'    => 'Chartered Accountant, Adeleke & Associates',
                    'city'    => 'Abuja',
                    'initial' => 'BA',
                    'color'   => 'bg-[#0A1A2F]',
                ],
                [
                    'quote'   => 'We were using spreadsheets for payroll and kept making PAYE errors. AccountTaxNG automated everything — payslips, PAYE computation, pension deductions. The Finance Act calculations are spot on.',
                    'name'    => 'Chukwudi Obi',
                    'role'    => 'Finance Director, Beta Supplies Ltd',
                    'city'    => 'Port Harcourt',
                    'initial' => 'CO',
                    'color'   => 'bg-emerald-600',
                ],
            ];
            @endphp

            @foreach($testimonials as $t)
            <div class="bg-[#F5F7FA] rounded-2xl p-7 border border-slate-100 flex flex-col">
                {{-- Stars --}}
                <div class="flex gap-1 mb-5">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-4 h-4 text-[#D4AF37]" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <blockquote class="text-sm text-[#475569] leading-relaxed flex-1">"{{ $t['quote'] }}"</blockquote>
                <div class="mt-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full {{ $t['color'] }} flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-700 text-white">{{ $t['initial'] }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-600 text-[#1E293B]">{{ $t['name'] }}</p>
                        <p class="text-xs text-[#64748B]">{{ $t['role'] }} · {{ $t['city'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- PRICING TEASER                                          --}}
{{-- ═══════════════════════════════════════════════════════ --}}
@if($plans->isNotEmpty())
<section class="py-20 lg:py-28 bg-[#F5F7FA]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-14">
            <div class="section-badge mb-4 mx-auto">Pricing</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F]">Plans for every stage of growth</h2>
            <p class="mt-4 text-[#64748B]">Start free. Scale as your business grows. No hidden fees.</p>
        </div>

        <div class="grid md:grid-cols-{{ min($plans->count(), 3) }} gap-6 max-w-5xl mx-auto">
            @foreach($plans as $plan)
            @php $popular = $plan->sort_order === 2 || ($loop->index === 1 && $plans->count() > 1); @endphp
            <div class="relative bg-white rounded-2xl border-2 {{ $popular ? 'border-[#D4AF37] shadow-xl shadow-[#D4AF37]/10' : 'border-slate-100 shadow-sm' }} p-7 flex flex-col">
                @if($popular)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="btn-gold text-[10px] font-700 px-4 py-1 rounded-full shadow-sm">Most Popular</span>
                </div>
                @endif
                <h3 class="font-display font-700 text-xl text-[#1E293B]">{{ $plan->name }}</h3>
                <div class="mt-3 mb-5">
                    <span class="font-display text-4xl font-900 text-[#0A1A2F]">
                        {{ $plan->price_monthly == 0 ? 'Free' : '₦' . number_format($plan->price_monthly, 0) }}
                    </span>
                    @if($plan->price_monthly > 0)
                    <span class="text-sm text-slate-500">/month</span>
                    @endif
                </div>
                <p class="text-sm text-[#64748B] leading-relaxed mb-6">{{ $plan->description }}</p>
                <a href="{{ route('register') }}"
                   class="{{ $popular ? 'btn-gold' : 'btn-outline-dark' }} text-sm font-700 px-5 py-2.5 rounded-xl text-center block mt-auto">
                    {{ $plan->price_monthly == 0 ? 'Get Started Free' : 'Start Free Trial' }}
                </a>
            </div>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('marketing.pricing') }}" class="inline-flex items-center gap-2 text-sm font-600 text-[#0A1A2F] hover:text-[#D4AF37] transition-colors">
                See full pricing & feature comparison
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- FAQ                                                     --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="py-20 lg:py-28 bg-white" x-data="{ open: null }">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <div class="section-badge mb-4 mx-auto">FAQ</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F]">Frequently asked questions</h2>
        </div>

        <div class="space-y-3">
            @php
            $faqs = [
                ['Is AccountTaxNG compliant with NRS regulations?', 'Yes. AccountTaxNG is fully aligned with the Federal Inland Revenue Service (FIRS) requirements and the Finance Act 2023. All VAT calculations use the current 7.5% rate, WHT rates are applied per the correct schedule, and CIT computations follow the applicable tax bands for SMEs.'],
                ['Can I try it for free?', 'Absolutely. We offer a 14-day full-access free trial with no credit card required. You can explore all features, create invoices, track expenses, and see your tax position — completely free for the first two weeks.'],
                ['How does VAT automation work?', 'Every time you create an invoice or record an expense, AccountTaxNG automatically identifies whether VAT applies and calculates the correct amount. At the end of each period, your VAT return is pre-populated and ready to review and file — no manual calculations needed.'],
                ['Can my accountant access my account?', 'Yes. You can invite your accountant as a collaborator with the Accountant role. They get full access to financial records, reports, and tax schedules — without access to billing or team management settings.'],
                ['Is my financial data secure?', 'Your data is encrypted in transit using 256-bit SSL and at rest using AES-256 encryption. We comply with the Nigeria Data Protection Regulation (NDPR) and maintain strict data residency standards. We do not share your data with third parties.'],
                ['Does it handle payroll?', 'Yes. The payroll module covers staff salary management, automatic PAYE computation per the Finance Act income bands, pension deductions (employee and employer), and payslip generation. Monthly payroll reports are available for State IRS remittance.'],
                ['What is NRS integration?', 'NRS is rolling out a National e-Invoice system (NRS) for standardised digital invoicing. AccountTaxNG is building NRS integration so your invoices will be automatically submitted to NRS upon issuance. This feature is currently in development.'],
                ['Can I use it on my phone?', 'Yes. AccountTaxNG is fully responsive and works on any device — mobile, tablet, or desktop. A dedicated mobile app is also on our development roadmap.'],
            ];
            @endphp

            @foreach($faqs as $i => [$question, $answer])
            <div class="border border-slate-100 rounded-xl overflow-hidden"
                 x-data="{ id: {{ $i }} }">
                <button @click="$root.open === id ? $root.open = null : $root.open = id"
                        class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-slate-50 transition-colors">
                    <span class="text-sm font-600 text-[#1E293B] pr-4">{{ $question }}</span>
                    <svg class="w-5 h-5 text-[#64748B] flex-shrink-0 transition-transform duration-200"
                         :class="$root.open === id ? 'rotate-45' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </button>
                <div x-show="$root.open === id" x-collapse x-cloak>
                    <div class="px-6 pb-5 text-sm text-[#64748B] leading-relaxed border-t border-slate-100 pt-4">
                        {{ $answer }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- FINAL CTA BANNER                                        --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="py-20 bg-[#0A1A2F] relative overflow-hidden">
    <div class="absolute inset-0 dot-pattern opacity-30 pointer-events-none"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
        <div class="section-badge mb-6 mx-auto">Get Started Today</div>
        <h2 class="font-display text-3xl lg:text-5xl font-800 text-white leading-tight">
            Start your 14-day free trial.<br>
            <span class="gradient-text">No credit card required.</span>
        </h2>
        <p class="mt-6 text-lg text-slate-300 max-w-xl mx-auto leading-relaxed">
            Join hundreds of Nigerian businesses already using AccountTaxNG to manage their books and stay compliant with confidence.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('register') }}"
               class="btn-gold inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-base font-700 shadow-lg shadow-[#D4AF37]/20">
                Create Free Account
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
            <a href="{{ route('marketing.contact') }}#demo"
               class="btn-outline-white inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-base font-600">
                Talk to Sales
            </a>
        </div>
        <p class="mt-5 text-xs text-slate-500">
            "Built for You, With You" &nbsp;·&nbsp; Bytestream Technologies &nbsp;·&nbsp; Made in Nigeria 🇳🇬
        </p>
    </div>
</section>

@endsection

@push('scripts')
<script>
// x-collapse polyfill for Alpine FAQ
document.addEventListener('alpine:init', () => {
    Alpine.directive('collapse', (el) => {
        el.style.overflow = 'hidden';
        if (el._x_isHidden) {
            el.style.height = '0px';
        }
    });
});
</script>
@endpush
