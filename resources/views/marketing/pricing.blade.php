@extends('marketing.layouts.app')

@section('title', 'Pricing — AccountTaxNG | Simple, Transparent Plans for Nigerian SMEs')
@section('meta_description', 'Affordable accounting and tax compliance plans for Nigerian SMEs. Start free, scale as you grow. Monthly and annual billing with no hidden fees.')

@section('content')

{{-- HERO --}}
<section class="gradient-hero pt-32 pb-16 lg:pt-40 lg:pb-24 text-center relative overflow-hidden">
    <div class="absolute inset-0 dot-pattern opacity-20 pointer-events-none"></div>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="section-badge mb-6 mx-auto">Pricing</div>
        <h1 class="font-display text-white text-4xl lg:text-5xl font-900 leading-tight">
            Simple, honest pricing.
        </h1>
        <p class="mt-4 text-lg text-slate-300 leading-relaxed">
            Start free. Upgrade when you're ready. Cancel anytime. No setup fees, no hidden charges.
        </p>
    </div>
</section>

{{-- PLAN CARDS --}}
<section class="py-20 lg:py-28 bg-[#F5F7FA]" x-data="{ cycle: 'monthly' }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Billing toggle --}}
        <div class="flex justify-center mb-12">
            <div class="bg-white border border-slate-200 rounded-xl p-1 inline-flex shadow-sm">
                <button @click="cycle = 'monthly'"
                        :class="cycle === 'monthly' ? 'bg-[#0A1A2F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-800'"
                        class="px-5 py-2 rounded-lg text-sm font-600 transition-all duration-200">
                    Monthly
                </button>
                <button @click="cycle = 'yearly'"
                        :class="cycle === 'yearly' ? 'bg-[#0A1A2F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-800'"
                        class="px-5 py-2 rounded-lg text-sm font-600 transition-all duration-200 flex items-center gap-2">
                    Annual
                    <span class="text-[10px] font-700 px-1.5 py-0.5 rounded-full"
                          :class="cycle === 'yearly' ? 'bg-[#D4AF37] text-[#0A1A2F]' : 'bg-green-100 text-green-700'">
                        Save up to 20%
                    </span>
                </button>
            </div>
        </div>

        @if($plans->isNotEmpty())
        <div class="grid md:grid-cols-{{ min($plans->count(), 3) }} gap-7 max-w-5xl mx-auto">
            @foreach($plans as $plan)
            @php
                $popular = $plan->sort_order === 2 || ($loop->index === 1 && $plans->count() > 1);
                $monthlyPrice = (float) $plan->price_monthly;
                $yearlyPrice  = (float) ($plan->price_yearly ?? 0);
                $isFree       = $monthlyPrice == 0;
                $discount     = $plan->yearlyDiscount();
            @endphp
            <div class="relative bg-white rounded-2xl border-2 {{ $popular ? 'border-[#D4AF37] shadow-2xl shadow-[#D4AF37]/15' : 'border-slate-100 shadow-sm' }} p-7 lg:p-8 flex flex-col">
                @if($popular)
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                    <span class="btn-gold text-[10px] font-800 px-5 py-1.5 rounded-full shadow-sm whitespace-nowrap">⭐ Most Popular</span>
                </div>
                @endif

                <div class="mb-6">
                    <h3 class="font-display font-800 text-xl text-[#1E293B]">{{ $plan->name }}</h3>
                    <p class="text-sm text-[#64748B] mt-1.5 leading-relaxed">{{ $plan->description }}</p>
                </div>

                <div class="mb-7">
                    @if($isFree)
                    <div class="font-display text-5xl font-900 text-[#0A1A2F]">Free</div>
                    <p class="text-sm text-slate-500 mt-1">Forever — no credit card needed</p>
                    @else
                    {{-- Monthly price display --}}
                    <div x-show="cycle === 'monthly'">
                        <div class="font-display text-5xl font-900 text-[#0A1A2F]">
                            ₦{{ number_format($monthlyPrice, 0) }}
                        </div>
                        <p class="text-sm text-slate-500 mt-1">per month, billed monthly</p>
                    </div>
                    {{-- Annual price display --}}
                    <div x-show="cycle === 'yearly'" x-cloak>
                        <div class="font-display text-5xl font-900 text-[#0A1A2F]">
                            ₦{{ $yearlyPrice > 0 ? number_format($yearlyPrice / 12, 0) : number_format($monthlyPrice * 0.8, 0) }}
                        </div>
                        <p class="text-sm text-slate-500 mt-1">
                            per month · ₦{{ $yearlyPrice > 0 ? number_format($yearlyPrice, 0) : number_format($monthlyPrice * 12 * 0.8, 0) }} billed annually
                        </p>
                        @if($discount > 0)
                        <span class="inline-block mt-2 text-xs font-700 bg-green-50 text-green-700 px-2.5 py-1 rounded-full">
                            You save {{ $discount }}% vs monthly
                        </span>
                        @endif
                    </div>
                    @endif
                </div>

                <a href="{{ route('register') }}"
                   class="{{ $popular ? 'btn-gold' : 'btn-outline-dark' }} text-sm font-700 px-5 py-3 rounded-xl text-center block mb-7">
                    {{ $isFree ? 'Get Started Free' : 'Start 14-Day Free Trial' }}
                </a>

                <div class="border-t border-slate-100 pt-6">
                    <p class="text-[11px] font-700 text-slate-500 uppercase tracking-wider mb-4">What's included</p>
                    <ul class="space-y-3">
                        @foreach([
                            ['invoices_per_month', 'Invoices/month', fn($v) => is_null($v) || $v >= 999 ? 'Unlimited invoices' : "{$v} invoices per month"],
                            ['users', 'Team members', fn($v) => is_null($v) ? 'Unlimited users' : ($v == 1 ? '1 user (owner only)' : "Up to {$v} users")],
                            ['customers', 'Customers', fn($v) => is_null($v) || $v >= 999 ? 'Unlimited customers' : "Up to {$v} customers"],
                            ['payroll', 'Payroll & PAYE', fn($v) => $v ? 'Payroll & PAYE automation' : null],
                            ['payroll_staff', 'Payroll staff', fn($v) => $v > 0 ? "Up to {$v} staff on payroll" : null],
                            ['firs', 'Tax automation', fn($v) => $v ? 'Full tax compliance suite' : null],
                            ['advanced_reports', 'Advanced reports', fn($v) => $v ? 'Advanced financial reports' : null],
                            ['inventory', 'Inventory', fn($v) => $v ? 'Inventory & stock management' : null],
                            ['inventory_reports', 'Inventory reports', fn($v) => $v ? 'Inventory analytics & reports' : null],
                            ['api_access', 'API access', fn($v) => $v ? 'API & integrations access' : null],
                        ] as [$key, $label, $formatter])
                        @php $value = $plan->limit($key); $text = $formatter($value); @endphp
                        @if($text !== null)
                        <li class="flex items-center gap-2.5 text-sm text-[#475569]">
                            <svg class="w-4 h-4 {{ $value ? 'text-green-600' : 'text-slate-300' }} flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                @if($value)
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                @else
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                @endif
                            </svg>
                            {{ $text }}
                        </li>
                        @endif
                        @endforeach
                        {{-- Always show cloud & support --}}
                        @foreach(['Cloud access (all devices)','Email support','Data encryption & backups','NRS-compliant tax calculations'] as $item)
                        <li class="flex items-center gap-2.5 text-sm text-[#475569]">
                            <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endforeach
        </div>

        @else
        {{-- Fallback static pricing when no DB plans --}}
        <div class="grid md:grid-cols-3 gap-7 max-w-5xl mx-auto">
            @foreach([
                ['Starter','Free','For solo founders and micro businesses just getting started with digital bookkeeping.',false,['5 invoices per month','1 user','50 customers','Basic expense tracking','VAT computation','Income & expense reports','Cloud access','Email support']],
                ['Business','₦15,000','For growing SMEs that need payroll, full tax automation, and team collaboration.',true,['Unlimited invoices','3 users','Unlimited customers','Full expense & WHT tracking','VAT + WHT + CIT automation','Payroll & PAYE (up to 10 staff)','Advanced reports','Priority support']],
                ['Professional','₦35,000','For established businesses with complex tax needs, large teams, and API requirements.',false,['Unlimited invoices','10 users','Unlimited customers','Full tax compliance suite','Payroll & PAYE (unlimited staff)','Advanced reports + custom exports','API access','Dedicated account manager']],
            ] as [$name, $price, $desc, $popular, $features])
            <div class="relative bg-white rounded-2xl border-2 {{ $popular ? 'border-[#D4AF37] shadow-2xl shadow-[#D4AF37]/15' : 'border-slate-100 shadow-sm' }} p-8 flex flex-col">
                @if($popular)
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                    <span class="btn-gold text-[10px] font-800 px-5 py-1.5 rounded-full shadow-sm">⭐ Most Popular</span>
                </div>
                @endif
                <h3 class="font-display font-800 text-xl text-[#1E293B] mb-1.5">{{ $name }}</h3>
                <p class="text-xs text-[#64748B] mb-5 leading-relaxed">{{ $desc }}</p>
                <div class="mb-6">
                    <div x-show="cycle === 'monthly'" class="font-display text-5xl font-900 text-[#0A1A2F]">{{ $price }}</div>
                    <div x-show="cycle === 'yearly'" x-cloak>
                        <div class="font-display text-5xl font-900 text-[#0A1A2F]">
                            {{ $price === 'Free' ? 'Free' : '₦' . number_format((int)str_replace(['₦',','], '', $price) * 0.8, 0) }}
                        </div>
                        @if($price !== 'Free')
                        <p class="text-xs text-green-700 mt-1 font-600">Save 20% with annual billing</p>
                        @endif
                    </div>
                    @if($price !== 'Free')
                    <p class="text-sm text-slate-500 mt-1">/month</p>
                    @else
                    <p class="text-sm text-slate-500 mt-1">Forever free</p>
                    @endif
                </div>
                <a href="{{ route('register') }}" class="{{ $popular ? 'btn-gold' : 'btn-outline-dark' }} text-sm font-700 px-5 py-3 rounded-xl text-center block mb-6">
                    {{ $price === 'Free' ? 'Get Started Free' : 'Start Free Trial' }}
                </a>
                <ul class="space-y-2.5 border-t border-slate-100 pt-5">
                    @foreach($features as $f)
                    <li class="flex items-center gap-2.5 text-sm text-[#475569]">
                        <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
        @endif

        <p class="text-center text-sm text-slate-500 mt-8">
            All paid plans include a <strong class="text-[#1E293B]">14-day free trial</strong>. No credit card required. Cancel anytime.
        </p>
    </div>
</section>

{{-- FEATURE COMPARISON TABLE --}}
<section class="py-20 lg:py-24 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-display text-2xl lg:text-3xl font-800 text-[#0A1A2F]">Full feature comparison</h2>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-100 shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-[#0A1A2F] text-white">
                        <th class="text-left px-6 py-4 font-600 text-slate-300 w-1/2">Feature</th>
                        <th class="text-center px-4 py-4 font-700 text-sm">Starter</th>
                        <th class="text-center px-4 py-4 font-700 text-sm relative">
                            <span class="text-[#D4AF37]">Business</span>
                        </th>
                        <th class="text-center px-4 py-4 font-700 text-sm">Professional</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @php
                    $rows = [
                        ['Invoicing & Quotes', 'Category'],
                        ['Invoice generation', '5/month', 'Unlimited', 'Unlimited'],
                        ['Quote to invoice conversion', false, true, true],
                        ['Custom branding on invoices', false, true, true],
                        ['Public payment link', true, true, true],
                        ['Email invoices directly', true, true, true],
                        ['Expenses & Vendors', 'Category'],
                        ['Expense tracking', true, true, true],
                        ['WHT deduction automation', false, true, true],
                        ['Receipt attachments', false, true, true],
                        ['Supplier management', true, true, true],
                        ['Tax Compliance', 'Category'],
                        ['VAT return computation', true, true, true],
                        ['Withholding Tax tracking', false, true, true],
                        ['Company Income Tax (CIT)', false, true, true],
                        ['Finance Act-aligned calculations', false, true, true],
                        ['NRS e-Invoice (Coming Soon)', false, false, true],
                        ['Payroll', 'Category'],
                        ['Staff payroll processing', false, true, true],
                        ['PAYE computation', false, true, true],
                        ['Payslip generation', false, true, true],
                        ['Pension deductions', false, true, true],
                        ['Reports', 'Category'],
                        ['Income & expense summary', true, true, true],
                        ['Profit & Loss statement', false, true, true],
                        ['Balance sheet', false, true, true],
                        ['VAT & WHT summary', false, true, true],
                        ['PDF & Excel export', false, true, true],
                        ['Team & Access', 'Category'],
                        ['Users', '1', '3', '10'],
                        ['Role-based permissions', false, true, true],
                        ['Accountant collaboration', false, true, true],
                        ['Audit trail', false, true, true],
                        ['Support', 'Category'],
                        ['Email support', true, true, true],
                        ['Priority support', false, true, true],
                        ['Dedicated account manager', false, false, true],
                        ['API access', false, false, true],
                    ];
                    $rowIdx = 0;
                    @endphp

                    @foreach($rows as $row)
                    @if(isset($row[1]) && $row[1] === 'Category')
                    <tr class="bg-[#F5F7FA]">
                        <td colspan="4" class="px-6 py-2.5 text-xs font-700 text-[#64748B] uppercase tracking-wider">{{ $row[0] }}</td>
                    </tr>
                    @else
                    @php $rowIdx++; @endphp
                    <tr class="{{ $rowIdx % 2 === 0 ? 'bg-white' : 'bg-[#F5F7FA]/50' }}">
                        <td class="px-6 py-3.5 text-sm text-[#475569]">{{ $row[0] }}</td>
                        @foreach([$row[1], $row[2], $row[3]] as $colIdx => $val)
                        <td class="px-4 py-3.5 text-center {{ $colIdx === 1 ? 'bg-[#D4AF37]/5' : '' }}">
                            @if($val === true)
                            <svg class="w-4.5 h-4.5 text-green-600 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            @elseif($val === false)
                            <svg class="w-4 h-4 text-slate-200 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                            @else
                            <span class="text-xs font-600 text-[#1E293B]">{{ $val }}</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="py-20 lg:py-24 bg-[#F5F7FA]" x-data="{ open: null }">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <div class="section-badge mb-4 mx-auto">FAQ</div>
            <h2 class="font-display text-2xl lg:text-3xl font-800 text-[#0A1A2F]">Pricing questions answered</h2>
        </div>

        <div class="space-y-3">
            @php $pricingFaqs = [
                ['Is there really a free plan?', 'Yes. Our Starter plan is completely free — no credit card, no time limit. It includes 5 invoices per month, basic expense tracking, VAT computation, and cloud access. It\'s designed for very small businesses or those just starting out.'],
                ['What happens after the 14-day trial?', 'After your trial, you can choose a paid plan to continue with full features, or downgrade to the free Starter plan. Your data is never deleted — you stay in control.'],
                ['Can I switch plans at any time?', 'Yes. You can upgrade or downgrade at any time. Upgrades take effect immediately. Downgrades take effect at the end of your current billing period.'],
                ['Is there a discount for annual billing?', 'Yes — you save up to 20% when you choose annual billing over monthly. The discount is applied automatically and shown on the pricing page.'],
                ['Can I get a custom plan for my business?', 'For businesses with special requirements — more users, custom integrations, or dedicated support — please contact us at hello@accounttaxng.com and we\'ll work out a plan that fits.'],
                ['Do you offer discounts for accountants or bookkeepers?', 'We are developing a partner programme for accountants serving multiple SME clients. Contact us to discuss collaboration and partner pricing.'],
                ['What payment methods do you accept?', 'We accept all major Nigerian cards (Mastercard, Visa) via Paystack. Bank transfer payment will be available for annual plans on request.'],
            ]; @endphp

            @foreach($pricingFaqs as $i => [$q, $a])
            <div class="bg-white border border-slate-100 rounded-xl overflow-hidden"
                 x-data="{ id: {{ $i }} }">
                <button @click="$root.open === id ? $root.open = null : $root.open = id"
                        class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-slate-50 transition-colors">
                    <span class="text-sm font-600 text-[#1E293B] pr-4">{{ $q }}</span>
                    <svg class="w-5 h-5 text-[#64748B] flex-shrink-0 transition-transform duration-200"
                         :class="$root.open === id ? 'rotate-45' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </button>
                <div x-show="$root.open === id" x-collapse x-cloak>
                    <div class="px-6 pb-5 text-sm text-[#64748B] leading-relaxed border-t border-slate-100 pt-4">{{ $a }}</div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-10 text-center bg-white rounded-2xl p-8 border border-slate-100 shadow-sm">
            <h3 class="font-display font-700 text-lg text-[#0A1A2F] mb-2">Still have questions?</h3>
            <p class="text-sm text-[#64748B] mb-5">Talk to our team — we're happy to walk you through the right plan for your business.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('marketing.contact') }}#demo" class="btn-gold inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-700">Book a Demo</a>
                <a href="mailto:hello@accounttaxng.com" class="btn-outline-dark inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-700">Email Us</a>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 bg-[#0A1A2F]">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="font-display text-2xl lg:text-3xl font-800 text-white">Start for free today</h2>
        <p class="mt-3 text-slate-300">No credit card. No setup fees. Full access for 14 days.</p>
        <a href="{{ route('register') }}" class="mt-6 btn-gold inline-flex items-center gap-2 px-8 py-3.5 rounded-xl text-sm font-700 shadow-lg shadow-[#D4AF37]/20">
            Create Free Account
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
        </a>
    </div>
</section>

@endsection
