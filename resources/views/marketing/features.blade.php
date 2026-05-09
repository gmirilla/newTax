@extends('marketing.layouts.app')

@section('title', 'Features — AccountTaxNG | Accounting, Tax & Payroll for Nigerian SMEs')
@section('meta_description', 'Explore all AccountTaxNG features: VAT automation, payroll PAYE, invoicing, expense tracking, Company Income Tax computation, NRS compliance, and more.')

@section('content')

{{-- HERO --}}
<section class="gradient-hero pt-32 pb-16 lg:pt-40 lg:pb-24 text-center relative overflow-hidden">
    <div class="absolute inset-0 dot-pattern opacity-20 pointer-events-none"></div>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="section-badge mb-6 mx-auto">Platform Features</div>
        <h1 class="font-display text-white text-4xl lg:text-5xl font-900 leading-tight">
            Everything your business needs.<br>
            <span class="gradient-text">Built for Nigeria.</span>
        </h1>
        <p class="mt-5 text-lg text-slate-300 leading-relaxed">
            A complete financial operations platform — from the invoice you send on Monday to the VAT return you file at quarter-end.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('register') }}" class="btn-gold inline-flex items-center gap-2 px-7 py-3.5 rounded-xl text-sm font-700">
                Start Free Trial
            </a>
            <a href="{{ route('marketing.pricing') }}" class="btn-outline-white inline-flex items-center gap-2 px-7 py-3.5 rounded-xl text-sm font-600">
                View Pricing
            </a>
        </div>
    </div>
</section>

{{-- ═══ INVOICING & QUOTES ═══ --}}
<section id="invoicing" class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-14 items-center">
            <div>
                <div class="section-badge mb-5">Invoicing & Billing</div>
                <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                    Professional invoices in seconds
                </h2>
                <p class="mt-4 text-[#64748B] leading-relaxed">
                    Create branded invoices and quotes that reflect your business. Auto-apply VAT, send directly to clients, and track every payment — all from one screen.
                </p>
                <div class="mt-8 grid sm:grid-cols-2 gap-4">
                    @foreach([
                        ['Professional invoice templates','Branded with your logo and business colours'],
                        ['Quote → Invoice conversion','Turn accepted quotes into invoices with one click'],
                        ['Automatic VAT calculation','7.5% VAT applied and displayed on every invoice'],
                        ['Payment tracking','Know which invoices are paid, pending, or overdue'],
                        ['Email invoices directly','Send PDFs straight from the platform to your client'],
                        ['Public payment link','Share a link for clients to view and confirm payment'],
                    ] as [$title, $desc])
                    <div class="bg-[#F5F7FA] rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <div>
                                <p class="text-sm font-600 text-[#1E293B]">{{ $title }}</p>
                                <p class="text-xs text-[#64748B] mt-0.5">{{ $desc }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Invoice visual --}}
            <div class="relative">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <div class="w-8 h-8 rounded-lg bg-[#0A1A2F] flex items-center justify-center mb-2">
                                <span class="text-[9px] font-black text-[#D4AF37]">AT</span>
                            </div>
                            <p class="text-xs font-700 text-[#0A1A2F]">Adetokunbo Ventures Ltd</p>
                            <p class="text-[10px] text-slate-500">15 Marina Street, Lagos Island</p>
                            <p class="text-[10px] text-slate-500">RC: 1234567 · TIN: 1234567-0001</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-900 text-[#0A1A2F]">INVOICE</p>
                            <p class="text-xs text-[#D4AF37] font-700">#INV-2025-047</p>
                            <p class="text-[10px] text-slate-500 mt-1">Date: 1 May 2025</p>
                            <p class="text-[10px] text-slate-500">Due: 15 May 2025</p>
                        </div>
                    </div>
                    <div class="bg-[#0A1A2F] text-white rounded-lg px-4 py-2.5 grid grid-cols-4 text-[9px] font-700 mb-1">
                        <span>Description</span><span class="text-right">Qty</span><span class="text-right">Unit Price</span><span class="text-right">Amount</span>
                    </div>
                    @foreach([['IT Consulting Services — Apr 2025','1','₦350,000','₦350,000'],['Software Licensing Fee','3','₦25,000','₦75,000'],['Technical Support Retainer','1','₦80,000','₦80,000']] as [$desc,$qty,$price,$total])
                    <div class="grid grid-cols-4 px-4 py-2 text-[9px] border-b border-slate-50">
                        <span class="text-slate-700">{{ $desc }}</span><span class="text-right text-slate-600">{{ $qty }}</span><span class="text-right text-slate-600">{{ $price }}</span><span class="text-right font-600 text-slate-800">{{ $total }}</span>
                    </div>
                    @endforeach
                    <div class="mt-4 space-y-1.5 px-4">
                        <div class="flex justify-between text-[9px] text-slate-500"><span>Subtotal</span><span>₦505,000</span></div>
                        <div class="flex justify-between text-[9px] text-slate-500"><span>VAT (7.5%)</span><span>₦37,875</span></div>
                        <div class="flex justify-between text-sm font-800 text-[#0A1A2F] pt-2 border-t border-slate-100"><span>Total Due</span><span>₦542,875</span></div>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-[9px] bg-amber-50 text-amber-700 border border-amber-200 font-600 px-2 py-1 rounded-full">⏳ Awaiting Payment</span>
                        <button class="text-[9px] bg-[#D4AF37] text-[#0A1A2F] font-700 px-3 py-1 rounded-lg">Send Invoice</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ TAX COMPLIANCE ═══ --}}
<section id="tax" class="py-20 lg:py-28 bg-[#F5F7FA]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="section-badge mb-4 mx-auto">Tax Compliance</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                FIRS-ready tax computation — automatically
            </h2>
            <p class="mt-4 text-[#64748B] leading-relaxed">
                Nigerian tax compliance is complex. AccountTaxNG handles every layer — VAT, WHT, CIT, and PAYE — so you're always prepared for filing deadlines.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php $taxFeatures = [
                ['VAT Returns (VAT Act)','7.5% auto-applied on invoices and purchases. Pre-populated VAT returns ready for review and NRS submission at end of each tax period.','bg-amber-50 text-amber-700','7.5%'],
                ['Withholding Tax (WHT)','Track WHT deducted on every qualifying supplier payment. Auto-generate WHT credit notes receivable for use against CIT liability.','bg-blue-50 text-blue-700','5–15%'],
                ['Company Income Tax (CIT)','Finance Act-aligned CIT computation for SMEs and large companies. Applies correct tax bands, deducts allowable expenses, applies WHT credits.','bg-purple-50 text-purple-700','20–30%'],
                ['Minimum Tax','Automated minimum tax calculation for businesses with small or nil profits — ensuring compliance even in difficult years.','bg-slate-100 text-slate-700','0.5%'],
                ['PAYE Computation','Monthly PAYE for all staff computed per current Finance Act income bands. Totals ready for State IRS remittance with audit trail.','bg-green-50 text-green-700','7–24%'],
                ['NRS e-Invoice (Coming Soon)','NRS National Revenue Service e-Invoice integration. Every invoice generated in AccountTaxNG will be submitted directly to NRS upon issuance.','bg-rose-50 text-rose-600','Soon'],
            ]; @endphp

            @foreach($taxFeatures as [$title, $desc, $badgeClass, $rate])
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="feature-icon-wrap">
                        <svg class="w-5 h-5 text-[#0A1A2F]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    </div>
                    <span class="text-xs font-700 px-2.5 py-1 rounded-full {{ $badgeClass }}">{{ $rate }}</span>
                </div>
                <h3 class="font-display font-700 text-[#1E293B] mb-2">{{ $title }}</h3>
                <p class="text-sm text-[#64748B] leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ EXPENSE TRACKING ═══ --}}
<section id="expenses" class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-14 items-center">

            {{-- Expense card visual --}}
            <div class="space-y-3">
                @foreach([
                    ['Office Rent — April 2025','Facilities','₦180,000','WHT: ₦18,000 (10%)','2025-04-01','Reconciled','green'],
                    ['Internet & Telecoms','Utilities','₦45,000','No WHT','2025-04-05','Pending','amber'],
                    ['External Audit Fees','Professional','₦320,000','WHT: ₦48,000 (15%)','2025-04-10','Reconciled','green'],
                    ['Staff Welfare & Canteen','HR','₦28,500','No WHT','2025-04-12','Pending','amber'],
                ] as [$desc,$cat,$amount,$wht,$date,$status,$color])
                <div class="bg-[#F5F7FA] rounded-xl px-4 py-3.5 border border-slate-100 flex items-center gap-4">
                    <div class="w-9 h-9 rounded-lg bg-[#0A1A2F]/5 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[#0A1A2F]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-600 text-[#1E293B] truncate">{{ $desc }}</p>
                        <p class="text-xs text-slate-400">{{ $cat }} · {{ $wht }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-700 text-[#0A1A2F]">{{ $amount }}</p>
                        <span class="text-[10px] font-600 text-{{ $color }}-600">{{ $status }}</span>
                    </div>
                </div>
                @endforeach
                <div class="bg-[#0A1A2F] rounded-xl px-4 py-3.5 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-400">Total WHT Deducted (Apr)</p>
                        <p class="text-sm font-700 text-white">₦66,000 <span class="text-[#D4AF37]">→ CIT Credit</span></p>
                    </div>
                    <button class="text-[10px] btn-gold px-3 py-1.5 rounded-lg font-700">Download WHT Schedule</button>
                </div>
            </div>

            <div>
                <div class="section-badge mb-5">Expense Management</div>
                <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                    Track expenses & WHT deductions effortlessly
                </h2>
                <p class="mt-4 text-[#64748B] leading-relaxed">
                    Every expense recorded in AccountTaxNG automatically triggers the correct WHT deduction schedule, builds your CIT credit pool, and feeds your management accounts in real time.
                </p>
                <ul class="mt-8 space-y-4">
                    @foreach([
                        ['Log expenses by category and supplier','Classify purchases for accurate P&L reporting'],
                        ['Auto WHT deduction tracking','NRS WHT rates applied per supplier service type'],
                        ['Receipt attachment','Upload receipts for audit trail and proof of expenditure'],
                        ['Supplier management','Maintain a supplier directory with payment history'],
                        ['Expense approval workflow','Admin approval before expenses hit the books'],
                    ] as [$title, $desc])
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-[#D4AF37]/15 border border-[#D4AF37]/30 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 text-[#D4AF37]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-600 text-[#1E293B]">{{ $title }}</p>
                            <p class="text-xs text-[#64748B] mt-0.5">{{ $desc }}</p>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ═══ PAYROLL ═══ --}}
<section id="payroll" class="py-20 lg:py-28 bg-[#0A1A2F] relative overflow-hidden">
    <div class="absolute inset-0 grid-bg opacity-30 pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="grid lg:grid-cols-2 gap-14 items-center">
            <div>
                <div class="section-badge mb-5">Payroll & PAYE</div>
                <h2 class="font-display text-3xl lg:text-4xl font-800 text-white leading-tight">
                    Payroll that computes PAYE automatically
                </h2>
                <p class="mt-4 text-slate-300 leading-relaxed">
                    Managing payroll in Nigeria means navigating the Finance Act income tax bands, pension deductions, and State IRS filings. AccountTaxNG automates every step.
                </p>
                <div class="mt-8 grid sm:grid-cols-2 gap-4">
                    @foreach([
                        ['Staff salary management','Set gross salary, allowances, and deduction rules per employee'],
                        ['Finance Act PAYE bands','Correct tax rates applied per current income bands automatically'],
                        ['Pension (PFA) deductions','8% employee + 10% employer pension auto-computed'],
                        ['Monthly payslip generation','Professional PDF payslips for every staff member'],
                        ['NHF contributions','National Housing Fund deductions tracked and reported'],
                        ['Payroll journal entries','Automatic posting to your chart of accounts on payroll run'],
                    ] as [$title, $desc])
                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <p class="text-sm font-600 text-white">{{ $title }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $desc }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Payslip visual --}}
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex justify-between items-center mb-5 pb-4 border-b border-slate-100">
                    <div>
                        <p class="text-xs font-700 text-[#0A1A2F]">PAYSLIP</p>
                        <p class="text-[10px] text-slate-500">April 2025</p>
                    </div>
                    <span class="text-[10px] bg-green-50 text-green-700 border border-green-200 rounded-full px-2 py-0.5 font-600">Processed ✓</span>
                </div>
                <div class="mb-4">
                    <p class="text-sm font-700 text-[#1E293B]">Ngozi Okonkwo</p>
                    <p class="text-[10px] text-slate-500">Senior Accountant · Staff ID: EMP-012</p>
                </div>
                <div class="space-y-1.5 text-[10px]">
                    <div class="flex justify-between font-600 text-slate-600 border-b border-slate-50 pb-1.5 mb-2">
                        <span>EARNINGS</span><span>AMOUNT</span>
                    </div>
                    @foreach([['Basic Salary','₦280,000'],['Housing Allowance','₦70,000'],['Transport Allowance','₦30,000'],['Medical Allowance','₦20,000']] as [$e,$a])
                    <div class="flex justify-between text-slate-700"><span>{{ $e }}</span><span class="font-600">{{ $a }}</span></div>
                    @endforeach
                    <div class="flex justify-between font-700 text-[#0A1A2F] pt-2 border-t border-slate-100 mt-2">
                        <span>Gross Pay</span><span>₦400,000</span>
                    </div>
                    <div class="flex justify-between font-600 text-slate-600 border-b border-slate-50 pb-1.5 mb-2 mt-4 pt-2">
                        <span>DEDUCTIONS</span><span></span>
                    </div>
                    @foreach([['PAYE (Finance Act)','₦62,500'],['Pension (8%)','₦32,000'],['NHF (2.5%)','₦10,000']] as [$d,$a])
                    <div class="flex justify-between text-slate-700"><span>{{ $d }}</span><span class="font-600 text-red-600">-{{ $a }}</span></div>
                    @endforeach
                    <div class="flex justify-between text-lg font-900 text-[#0A1A2F] pt-3 border-t-2 border-[#D4AF37] mt-3">
                        <span>Net Pay</span><span>₦295,500</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ REPORTS ═══ --}}
<section id="reports" class="py-20 lg:py-28 bg-[#F5F7FA]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="section-badge mb-4 mx-auto">Financial Reports</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                Reports that tell the full story
            </h2>
            <p class="mt-4 text-[#64748B] leading-relaxed">
                Every financial statement your business, your bank, or NRS needs — generated in seconds, downloaded as PDF or Excel.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach([
                ['Profit & Loss Statement','Full P&L by period with revenue, COGS, and expense breakdowns. Monthly, quarterly, or annual.','M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['Balance Sheet','Assets, liabilities, and equity snapshot at any date. IFRS-structured for audit and banking.','M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
                ['VAT Summary Report','VAT output, input, and net position. Formatted for NRS VAT return (Form 002).','M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'],
                ['Accounts Receivable','Aged debtors report — who owes you, for how long, and how much. Filterable by client.','M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                ['Cash Flow Statement','Operating, investing, and financing activities. See exactly where cash is moving.','M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['WHT Credit Schedule','All WHT deductions received from customers — structured for CIT offset with FIRS.','M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['Payroll Summary','Monthly payroll register with gross, deductions, and net for every employee.','M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['Trial Balance','Full debit/credit trial balance for any period. Ready for external auditors or your accountant.','M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4'],
            ] as [$title, $desc, $icon])
            <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm card-hover">
                <div class="feature-icon-wrap mb-4">
                    <svg class="w-5 h-5 text-[#0A1A2F]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                    </svg>
                </div>
                <h3 class="font-display font-700 text-sm text-[#1E293B] mb-1.5">{{ $title }}</h3>
                <p class="text-xs text-[#64748B] leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ USER ROLES ═══ --}}
<section id="roles" class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-14">
            <div class="section-badge mb-4 mx-auto">Multi-User Access</div>
            <h2 class="font-display text-3xl lg:text-4xl font-800 text-[#0A1A2F] leading-tight">
                The right access for every role
            </h2>
            <p class="mt-4 text-[#64748B]">Invite your team with confidence. Every role has precisely scoped permissions — nothing more, nothing less.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
            @foreach([
                ['Admin','Full platform access. Manage billing, team members, settings, and all financial data. Only admins can invite users or change plan.','bg-[#0A1A2F] text-white','M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',['Full financial access','Team management','Billing & subscriptions','Settings & configuration','Reports & tax filings']],
                ['Accountant','Financial read/write access. Create invoices, record expenses, run payroll, prepare tax returns, and download all reports. No billing access.','bg-[#D4AF37]/10 border border-[#D4AF37]/30','M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0',['Invoices & quotes','Expense management','Tax returns & reports','Payroll processing','No billing access']],
                ['Staff','Limited operational access. Staff can view their own payslips, submit expense claims, and access the staff dashboard only.','bg-slate-50 border border-slate-200','M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0',['View own payslips','Submit expense claims','Staff dashboard only','No financial read access','No reports or tax data']],
            ] as [$role, $desc, $class, $icon, $perms])
            <div class="rounded-2xl p-6 {{ $class }} {{ str_contains($class, 'bg-[#0A1A2F]') ? '' : 'bg-white' }}">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-display font-800 text-lg {{ str_contains($class, 'bg-[#0A1A2F]') ? 'text-white' : 'text-[#1E293B]' }}">{{ $role }}</h3>
                    <div class="w-9 h-9 rounded-xl {{ str_contains($class, 'bg-[#0A1A2F]') ? 'bg-white/10' : 'bg-[#0A1A2F]/8' }} flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 {{ str_contains($class, 'bg-[#0A1A2F]') ? 'text-[#D4AF37]' : 'text-[#0A1A2F]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </div>
                </div>
                <p class="text-xs leading-relaxed {{ str_contains($class, 'bg-[#0A1A2F]') ? 'text-slate-300' : 'text-[#64748B]' }} mb-5">{{ $desc }}</p>
                <ul class="space-y-2">
                    @foreach($perms as $perm)
                    <li class="flex items-center gap-2 text-xs {{ str_contains($class, 'bg-[#0A1A2F]') ? 'text-slate-300' : 'text-[#475569]' }}">
                        <svg class="w-3.5 h-3.5 {{ str_contains($class, 'bg-[#0A1A2F]') ? 'text-[#D4AF37]' : 'text-green-600' }} flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ $perm }}
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ NRS + CLOUD ═══ --}}
<section class="py-20 lg:py-28 bg-[#F5F7FA]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-8">
            <div class="bg-[#0A1A2F] rounded-3xl p-8 lg:p-10 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-40 h-40 rounded-full blur-3xl opacity-10"
                     style="background:radial-gradient(circle,#D4AF37,transparent)"></div>
                <div class="inline-flex items-center gap-2 bg-rose-500/20 border border-rose-500/30 text-rose-400 text-[11px] font-700 px-3 py-1 rounded-full mb-5">
                    Coming Soon
                </div>
                <h3 class="font-display text-2xl font-800 text-white mb-4">NRS NRS e-Invoice Integration</h3>
                <p class="text-slate-300 leading-relaxed mb-6">The Federal Inland Revenue Service is mandating digital invoice submission through the National Revenue Service (NRS) platform. AccountTaxNG is building native NRS integration — every invoice you generate will be automatically submitted to FIRS.</p>
                <ul class="space-y-3">
                    @foreach(['Real-time invoice submission to NRS NRS','Digital tax receipt generation per transaction','NRS validation before invoice is finalised','Full audit trail of all e-invoice submissions','Automatic integration — no extra steps for users'] as $item)
                    <li class="flex items-center gap-3 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-[#D4AF37] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white rounded-3xl p-8 lg:p-10 border border-slate-100 shadow-sm">
                <div class="w-12 h-12 rounded-2xl bg-[#0A1A2F] flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918"/></svg>
                </div>
                <h3 class="font-display text-2xl font-800 text-[#0A1A2F] mb-4">Cloud-Based. Secure. Always Available.</h3>
                <p class="text-[#64748B] leading-relaxed mb-6">AccountTaxNG runs entirely in the cloud — no installation, no local backups, no IT headaches. Access your full accounting system from any device, anywhere in Nigeria.</p>
                <div class="grid grid-cols-2 gap-4">
                    @foreach([['99.9% Uptime','Enterprise-grade infrastructure with SLA'],['256-bit Encryption','Data encrypted in transit and at rest'],['NDPR Compliant','Nigerian Data Protection Regulation adherence'],['Automatic Backups','Your data backed up every hour, never lost'],['Role-based Access','Granular permissions per user role'],['Audit Trail','Every action logged with user, date, and time']] as [$title,$desc])
                    <div>
                        <p class="text-sm font-600 text-[#1E293B]">{{ $title }}</p>
                        <p class="text-xs text-[#64748B] mt-0.5">{{ $desc }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ COMMUNITY FORUM ═══ --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-br from-[#0A1A2F] to-[#0d2444] rounded-3xl p-10 lg:p-14 text-center relative overflow-hidden">
            <div class="absolute inset-0 dot-pattern opacity-20 pointer-events-none"></div>
            <div class="relative max-w-2xl mx-auto">
                <div class="inline-flex items-center gap-2 bg-[#D4AF37]/15 border border-[#D4AF37]/30 text-[#D4AF37] text-xs font-700 px-3 py-1.5 rounded-full mb-5">
                    Community Forum
                </div>
                <h3 class="font-display text-2xl lg:text-3xl font-800 text-white mb-4">Shape the product with your feedback</h3>
                <p class="text-slate-300 leading-relaxed mb-6">AccountTaxNG is built with Nigerian SMEs — not just for them. Our community forum is where users discuss challenges, request features, and vote on the product roadmap. Your voice directly influences what we build next.</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="{{ route('register') }}" class="btn-gold inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-700">
                        Join the Community
                    </a>
                    <a href="{{ route('marketing.contact') }}" class="btn-outline-white inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-600">
                        Suggest a Feature
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 bg-[#F5F7FA]">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="font-display text-2xl lg:text-3xl font-800 text-[#0A1A2F]">Ready to simplify your accounting?</h2>
        <p class="mt-3 text-[#64748B]">14-day free trial. All features. No credit card required.</p>
        <a href="{{ route('register') }}" class="mt-6 btn-gold inline-flex items-center gap-2 px-8 py-3.5 rounded-xl text-sm font-700 shadow-lg shadow-[#D4AF37]/20">
            Start Your Free Trial
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
        </a>
    </div>
</section>

@endsection
