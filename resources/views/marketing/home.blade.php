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
                    The platform that combines Accounting, Sales, VAT automation, payroll PAYE, and NRS compliance in one place — built specifically for Nigerian businesses.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                    <a href="{{ route('register') }}"
                       class="btn-gold inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl text-sm font-700 shadow-lg shadow-[#D4AF37]/20">
                        Start Free Trial
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                    <a href="{{ route('marketing.contact') }}"
                       class="btn-outline-white inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl text-sm font-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/>
                        </svg>
                        Talk to Sales
                    </a>
                </div>

                <p class="mt-5 text-xs text-slate-500">
                    14-day free trial &nbsp;·&nbsp; No credit card required &nbsp;·&nbsp; Cancel anytime
                </p>

                <button type="button" onclick="startMarketingTour()"
                        class="mt-4 inline-flex items-center gap-1.5 text-sm font-600 text-[#D4AF37] hover:text-[#E8C84A] transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/></svg>
                    See it in action
                </button>

                {{-- Mini trust bar --}}
                <div class="mt-8 flex flex-wrap items-center gap-5 justify-center lg:justify-start">
                    <div class="flex items-center gap-1.5 text-xs text-slate-400">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Designed for NRS compliance
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Current Tax Law Aligned
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
                        <p class="text-[10px] text-slate-500">Automatically Calculated</p>
                    </div>
                </div>
            </div>
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
                ['icon'=>'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M9 14h.01M12 14h.01M15 14h.01M4 3h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1z','title'=>'VAT Automation','desc'=>'Auto-calculate VAT from every invoice and expense. Generate NRS-ready VAT returns with one click.','badge'=>'Tax'],
                ['icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z','title'=>'Payroll & PAYE','desc'=>'Manage staff salaries, auto-calculate PAYE deductions, generate payslips, and track pension contributions.','badge'=>'HR'],
                ['icon'=>'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','title'=>'Financial Reports','desc'=>'Profit & Loss, Balance Sheet, Cash Flow, and VAT Summary reports. Download as PDF or Excel instantly.','badge'=>'Reports'],
                ['icon'=>'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z','title'=>'Company Income Tax','desc'=>'CIT computed under Nigeria\'s current tax law. Track WHT credits, manage tax schedules, and stay audit-ready.','badge'=>'Tax'],
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
                    AccountTaxNG is built around Nigeria's current tax framework and applicable NRS requirements. Every transaction is automatically classified to generate NRS-ready returns — so compliance becomes a by-product of doing business, not a quarterly panic.
                    <a href="{{ route('marketing.tax-rules') }}" class="text-[#D4AF37] hover:underline">See the full framework →</a>
                </p>

                <div class="mt-8 space-y-4">
                    @foreach([
                        ['VAT Returns', 'Auto-calculated from invoices and purchases. Download or submit directly.'],
                        ['Withholding Tax (WHT)', 'Track WHT deducted on supplier payments. Generate credit notes automatically.'],
                        ['Company Income Tax (CIT)', 'Computed under current tax law, with WHT credits applied automatically.'],
                        ['PAYE Remittances', 'Payroll PAYE totals ready for monthly State IRS remittance.']

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
                    ['VAT','7.5%','Standard Rate','₦372,225 due 21 Apr','amber','Computed'],
                    ['WHT','10%','Supplier Payments','₦48,500 credited','blue','Tracked'],
                    ['CIT','30%','Annual Profit Tax','₦0 payable (WHT offset)','green','Computed'],
                    ['PAYE','Auto','Progressive Bands','₦156,800 remitted','purple','Filed'],
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
{{-- FOR SMEs                                                --}}
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
                    Start Free Trial
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
            <a href="{{ route('marketing.contact') }}"
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
function startMarketingTour() {
    if (!window.ImageTour) return;
    var base = '{{ asset('images/marketing/tour') }}/';
    var slides = [
        { src: base + '01-login.png', title: 'Sign in to your workspace', body: 'A branded sign-in for your business — set up in minutes.' },
        { src: base + '02-dashboard.png', title: 'Your Dashboard', body: 'Cash position, VAT/CIT status, and payroll — all at a glance, updated automatically as you work.' },
        { src: base + '03-invoices.png', title: 'Invoices', body: 'Every invoice tracked — paid, outstanding, or overdue — with totals rolled up at the top.' },
        { src: base + '04-invoice-create.png', title: 'Create an invoice', body: 'VAT is calculated automatically as you add line items — no manual math.' },
        { src: base + '05-tax-dashboard.png', title: 'Tax Compliance Dashboard', body: 'VAT, WHT, and CIT organised in one place, with due dates and current Nigerian tax rates.' },
        { src: base + '06-vat-computation.png', title: 'Compute your VAT return', body: 'One click computes output VAT, input VAT, and what\'s payable — ready to file.' },
        { src: base + '07-expenses.png', title: 'Expenses & WHT', body: 'Record an expense and the correct WHT rate is deducted automatically based on the vendor.' },
        { src: base + '08-payroll-employees.png', title: 'Payroll & PAYE', body: 'Manage your team and run payroll with PAYE computed per current income tax bands.' },
        { src: base + '09-reports.png', title: 'Financial Reports', body: 'P&L, Balance Sheet, Trial Balance, and tax reports — generated instantly, exportable to PDF or Excel.' },
        { src: base + '10-profit-loss.png', title: 'Profit & Loss Statement', body: 'Real profit, calculated automatically from your invoices, expenses, and payroll.' },
    ];
    new window.ImageTour(slides, { ctaHref: '{{ route('register') }}', ctaLabel: 'Start Free Trial' }).start();
}
</script>
@endpush
