@extends('marketing.layouts.app')

@section('title', 'Frequently Asked Questions — AccountTaxNG')
@section('meta_description', 'Answers to common questions about AccountTaxNG — NRS compliance, VAT automation, payroll, security, pricing, and more.')

@section('content')

{{-- HERO --}}
<section class="gradient-hero pt-32 pb-16 lg:pt-44 lg:pb-20 relative overflow-hidden">
    <div class="absolute inset-0 grid-bg opacity-30 pointer-events-none"></div>
    <div class="absolute right-0 top-0 w-96 h-96 rounded-full blur-3xl opacity-10 pointer-events-none"
         style="background:radial-gradient(circle,#D4AF37,transparent)"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center">
        <div class="section-badge mb-6 mx-auto">FAQ</div>
        <h1 class="font-display text-4xl lg:text-5xl font-900 text-white leading-tight">
            Frequently asked <span class="gradient-text">questions</span>
        </h1>
        <p class="mt-6 text-lg text-slate-300 leading-relaxed max-w-2xl mx-auto">
            Everything you need to know about AccountTaxNG. Can't find what you're looking for?
            <a href="{{ route('marketing.contact') }}" class="text-[#D4AF37] font-600 hover:underline">Talk to our team</a>.
        </p>
    </div>
</section>

{{-- FAQ LIST --}}
<section class="py-20 lg:py-28 bg-white" x-data="{ open: null }">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="space-y-3">
            @php
            $faqs = [
                ['Is AccountTaxNG compliant with NRS regulations?', 'Yes. AccountTaxNG is built around Nigeria\'s current tax framework — the Nigeria Tax Act 2025, Nigeria Tax Administration Act 2025, and NRS (Establishment) Act 2025 — and is updated as new regulations are published. All VAT calculations use the current 7.5% rate, WHT rates follow the applicable schedule, and CIT computations follow the current tax bands for SMEs. See our Tax Rules page for the full breakdown.'],
                ['Can I try it for free?', 'Absolutely. Every account starts with a 14-day full-access trial — no credit card required. You can explore all features, create invoices, track expenses, and see your tax position. If you don\'t upgrade to a paid plan before the trial ends, your account continues automatically on our Starter plan, which is free forever.'],
                ['How does VAT automation work?', 'Every time you create an invoice or record an expense, AccountTaxNG automatically identifies whether VAT applies and calculates the correct amount. At the end of each period, your VAT return is pre-populated and ready to review and file — no manual calculations needed.'],
                ['Can my accountant access my account?', 'Yes. You can invite your accountant as a collaborator with the Accountant role. They get full access to financial records, reports, and tax schedules — without access to billing or team management settings.'],
                ['Is my financial data secure?', 'Your data is encrypted in transit using 256-bit SSL and at rest using AES-256 encryption. We comply with the Nigeria Data Protection Regulation (NDPR) and maintain strict data residency standards. We do not share your data with third parties.'],
                ['Does it handle payroll?', 'Yes. The payroll module covers staff salary management, automatic PAYE computation per current income tax bands, pension deductions (employee and employer), and payslip generation. Monthly payroll reports are available for State IRS remittance.'],
                ['What is NRS e-Invoice integration?', 'The NRS is rolling out a national e-Invoice system for standardised digital invoicing. AccountTaxNG is building native integration so your invoices will be automatically submitted upon issuance. This feature is currently in development.'],
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

{{-- CTA --}}
<section class="py-20 bg-[#0A1A2F] relative overflow-hidden">
    <div class="absolute inset-0 dot-pattern opacity-30 pointer-events-none"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
        <h2 class="font-display text-3xl lg:text-4xl font-800 text-white leading-tight">
            Still have questions?
        </h2>
        <p class="mt-4 text-slate-300 max-w-xl mx-auto leading-relaxed">
            Our team is happy to help you understand how AccountTaxNG fits your business.
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
