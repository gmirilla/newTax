@extends('marketing.layouts.app')

@section('title', 'Tax Rules — AccountTaxNG | Nigeria\'s Current Tax Framework')
@section('meta_description', 'The regulatory framework behind AccountTaxNG: the Nigeria Tax Act 2025, Nigeria Tax Administration Act 2025, NRS Establishment Act 2025, and current VAT, CIT, WHT, and PAYE rates.')

@section('content')

{{-- HERO --}}
<section class="gradient-hero pt-32 pb-20 lg:pt-44 lg:pb-28 relative overflow-hidden">
    <div class="absolute inset-0 grid-bg opacity-30 pointer-events-none"></div>
    <div class="absolute right-0 top-0 w-96 h-96 rounded-full blur-3xl opacity-10 pointer-events-none"
         style="background:radial-gradient(circle,#D4AF37,transparent)"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="section-badge mb-6">Tax Rules</div>
        <h1 class="font-display text-4xl lg:text-5xl xl:text-6xl font-900 text-white leading-tight max-w-3xl">
            Built on Nigeria's <span class="gradient-text">current tax framework.</span>
        </h1>
        <p class="mt-6 text-lg text-slate-300 leading-relaxed max-w-2xl">
            Nigeria's tax system was overhauled in 2025. Rather than hard-coding one law's name into our product, we built a tax engine designed around the framework itself — so when the rules change, AccountTaxNG changes with them.
        </p>
    </div>
</section>

{{-- THE REGULATORY FRAMEWORK --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="section-badge mb-4 mx-auto">The Regulatory Framework</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                Four pieces of law, one tax engine
            </h2>
            <p class="mt-4 text-[#64748B] leading-relaxed">
                In June 2025, Nigeria signed a package of tax reform laws that took effect 1 January 2026 — replacing decades of amendments to separate acts with a single, consolidated framework. This is what AccountTaxNG is built around.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            @foreach([
                ['Nigeria Tax Act, 2025', 'The substantive tax law — consolidates Companies Income Tax, Personal Income Tax (PAYE), Value Added Tax, Withholding Tax, and the new Development Levy into one act. This is where our VAT, CIT, WHT, and PAYE calculations come from.', 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
                ['Nigeria Tax Administration Act, 2025', 'Governs how tax is registered, assessed, filed, and collected across federal and state authorities — the procedural rules behind filing deadlines, penalties, and remittance schedules our platform tracks for you.', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z'],
                ['NRS (Establishment) Act, 2025', 'Establishes the Nigeria Revenue Service (NRS) as the federal tax authority, replacing the Federal Inland Revenue Service (FIRS). Every "NRS-ready" feature in AccountTaxNG refers to this body.', 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918'],
                ['Applicable regulations & guidelines', 'Implementing regulations, public notices, and circulars issued by the NRS and State Internal Revenue Services as the new framework is rolled out. We track these as they\'re published and reflect them in the platform.', 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z'],
            ] as [$title, $desc, $icon])
            <div class="bg-[#F5F7FA] rounded-2xl p-6 border border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-[#0A1A2F] flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                    </svg>
                </div>
                <h3 class="font-display font-700 text-lg text-[#1E293B] mb-2">{{ $title }}</h3>
                <p class="text-sm text-[#64748B] leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CURRENT RATES --}}
<section class="py-20 lg:py-28 bg-[#F5F7FA]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="section-badge mb-4 mx-auto">Current Rates</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                What's actually computed today
            </h2>
            <p class="mt-4 text-[#64748B] leading-relaxed">
                These are the exact rates and bands AccountTaxNG's tax engine applies right now, under the Nigeria Tax Act 2025.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mb-6">
            {{-- VAT --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                <h3 class="font-display font-700 text-[#1E293B] mb-1">Value Added Tax (VAT)</h3>
                <p class="text-xs text-[#94A3B8] mb-4">Nigeria Tax Act 2025</p>
                <div class="flex items-baseline gap-2">
                    <span class="font-display text-3xl font-900 text-[#0A1A2F]">7.5%</span>
                    <span class="text-sm text-[#64748B]">standard rate on taxable invoices and purchases</span>
                </div>
            </div>

            {{-- CIT --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                <h3 class="font-display font-700 text-[#1E293B] mb-1">Company Income Tax (CIT)</h3>
                <p class="text-xs text-[#94A3B8] mb-4">Nigeria Tax Act 2025 — two-bracket structure</p>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-[#64748B]">Small companies (turnover ≤ ₦50M)</span><span class="font-700 text-green-600">0%</span></div>
                    <div class="flex justify-between"><span class="text-[#64748B]">Large companies (turnover &gt; ₦50M)</span><span class="font-700 text-[#0A1A2F]">30%</span></div>
                </div>
                <p class="text-xs text-[#94A3B8] mt-3">Professional services firms (legal, accounting, engineering, etc.) are always taxed as "large," regardless of turnover.</p>
            </div>

            {{-- Development Levy + Minimum Tax --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                <h3 class="font-display font-700 text-[#1E293B] mb-1">Development Levy</h3>
                <p class="text-xs text-[#94A3B8] mb-4">Replaces TETFund, IT Levy, NASENI &amp; Police Trust Fund levies</p>
                <div class="flex items-baseline gap-2">
                    <span class="font-display text-3xl font-900 text-[#0A1A2F]">4%</span>
                    <span class="text-sm text-[#64748B]">of assessable profit — all companies except small (non-professional) firms</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                <h3 class="font-display font-700 text-[#1E293B] mb-1">Minimum Tax</h3>
                <p class="text-xs text-[#94A3B8] mb-4">Applies when computed CIT falls below this floor</p>
                <div class="flex items-baseline gap-2">
                    <span class="font-display text-3xl font-900 text-[#0A1A2F]">0.5%</span>
                    <span class="text-sm text-[#64748B]">of gross turnover, or ₦200,000 — whichever is higher</span>
                </div>
            </div>
        </div>

        {{-- WHT --}}
        <div class="bg-white rounded-2xl p-6 lg:p-8 border border-slate-100 shadow-sm mb-6">
            <h3 class="font-display font-700 text-[#1E293B] mb-1">Withholding Tax (WHT)</h3>
            <p class="text-xs text-[#94A3B8] mb-5">Deducted at source — the exact schedule applied to every qualifying expense</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[#94A3B8] text-xs uppercase tracking-wide border-b border-slate-100">
                            <th class="pb-2 font-600">Transaction Type</th>
                            <th class="pb-2 font-600 text-right">Company Rate</th>
                            <th class="pb-2 font-600 text-right">Individual Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach([
                            ['Contracts (construction/supply)', '5%', '5%'],
                            ['Professional services', '5%', '10%'],
                            ['Management / technical services', '5%', '10%'],
                            ['Rent / lease', '10%', '10%'],
                            ['Dividends', '10%', '10%'],
                            ['Interest / royalties', '10%', '10%'],
                        ] as [$type, $co, $ind])
                        <tr>
                            <td class="py-2.5 text-[#475569]">{{ $type }}</td>
                            <td class="py-2.5 text-right font-600 text-[#0A1A2F]">{{ $co }}</td>
                            <td class="py-2.5 text-right font-600 text-[#0A1A2F]">{{ $ind }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAYE --}}
        <div class="bg-white rounded-2xl p-6 lg:p-8 border border-slate-100 shadow-sm">
            <h3 class="font-display font-700 text-[#1E293B] mb-1">PAYE (Personal Income Tax)</h3>
            <p class="text-xs text-[#94A3B8] mb-5">Progressive annual bands, applied after the ₦800,000 tax-free amount</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[#94A3B8] text-xs uppercase tracking-wide border-b border-slate-100">
                            <th class="pb-2 font-600">Annual Income Band</th>
                            <th class="pb-2 font-600 text-right">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach([
                            ['First ₦800,000', '0%'],
                            ['₦800,000 – ₦3,000,000', '15%'],
                            ['₦3,000,000 – ₦12,000,000', '18%'],
                            ['₦12,000,000 – ₦25,000,000', '21%'],
                            ['₦25,000,000 – ₦50,000,000', '23%'],
                            ['Above ₦50,000,000', '25%'],
                        ] as [$band, $rate])
                        <tr>
                            <td class="py-2.5 text-[#475569]">{{ $band }}</td>
                            <td class="py-2.5 text-right font-600 text-[#0A1A2F]">{{ $rate }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-[#94A3B8] mt-4">Plus reliefs introduced under the Nigeria Tax Act 2025: rent relief (20% of annual rent, capped at ₦500,000), and full deductions for home loan interest and life insurance premiums.</p>
        </div>
    </div>
</section>

{{-- HOW WE STAY CURRENT --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-[#0A1A2F] rounded-3xl p-8 lg:p-12 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 rounded-full blur-3xl opacity-10 pointer-events-none"
                 style="background: radial-gradient(circle, #D4AF37, transparent)"></div>
            <div class="relative flex items-start gap-5">
                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-[#D4AF37]/15 border border-[#D4AF37]/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                </div>
                <div>
                    <h3 class="font-display text-xl font-800 text-white mb-2">How we keep this page — and your calculations — current</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Tax law changes. Our approach doesn't: when the NRS or National Assembly amends a rate, band, or filing rule, we update the tax engine within 30 days and this page along with it. Nothing in AccountTaxNG's interface hard-codes a specific year or act by name — it references whatever the current framework is, so a future amendment doesn't leave stale claims sitting in the product.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 bg-[#F5F7FA]">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="font-display text-2xl lg:text-3xl font-800 text-[#0A1A2F]">See it compute your numbers</h2>
        <p class="mt-3 text-[#64748B]">14-day free trial. No credit card required.</p>
        <a href="{{ route('register') }}" class="mt-6 btn-gold inline-flex items-center gap-2 px-8 py-3.5 rounded-xl text-sm font-700 shadow-lg shadow-[#D4AF37]/20">
            Start Free Trial
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
        </a>
    </div>
</section>

@endsection
