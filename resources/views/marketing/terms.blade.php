@extends('marketing.layouts.app')

@section('title', 'Terms of Service — AccountTaxNG')
@section('meta_description', 'The terms governing use of AccountTaxNG, a product of Bytestream Technologies.')

@section('content')

{{-- HERO --}}
<section class="gradient-hero pt-32 pb-16 lg:pt-44 lg:pb-20 relative overflow-hidden">
    <div class="absolute inset-0 grid-bg opacity-30 pointer-events-none"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="section-badge mb-6">Legal</div>
        <h1 class="font-display text-4xl lg:text-5xl font-900 text-white leading-tight">Terms of Service</h1>
        <p class="mt-6 text-lg text-slate-300 leading-relaxed max-w-2xl">
            Last updated {{ now()->format('d F Y') }}. These terms govern your use of AccountTaxNG. By creating an account, you agree to them.
        </p>
    </div>
</section>

{{-- CONTENT --}}
<section class="py-16 lg:py-24 bg-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 prose-dark">

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-12 text-sm text-amber-900">
            This is a plain-language draft grounded in how the service actually works today. It is not independent legal advice — have it reviewed before relying on it as a binding contract.
        </div>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">1. Who this agreement is with</h2>
        <p>AccountTaxNG is operated by <strong>Bytestream Technologies</strong> ("we", "us"). These Terms are an agreement between us and the business ("you", the "tenant") registering for the service, acting through the individual who creates the account.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">2. Eligibility</h2>
        <p>By registering, you represent that you are at least 18 years old and that you have the authority to register your business and bind it to these Terms. AccountTaxNG is a business tool and is not directed at, or intended for use by, individual consumers acting in a personal capacity, or by children.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">3. The service</h2>
        <p>AccountTaxNG provides cloud-based accounting, invoicing, expense tracking, payroll, tax computation, and related tools for Nigerian businesses. Some features (e.g. FIRS/NRS e-invoicing submission, advanced reports, payroll) may be limited to specific subscription plans.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">4. Your account</h2>
        <p>You're responsible for the accuracy of the information you provide, for keeping your login credentials confidential, and for all activity under your account, including that of staff you invite. Tell us promptly if you suspect unauthorized access.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">5. Trial, subscriptions & billing</h2>
        <p>New accounts start on a free trial. If you don't upgrade before it ends, your account continues on our free plan with reduced limits, rather than being suspended. Paid subscriptions are billed through <strong>Paystack</strong>; by subscribing you authorize us to charge your chosen payment method on a recurring basis until you cancel. You can cancel at any time from your billing settings; cancellation takes effect at the end of the current billing period, and we don't provide partial refunds for unused time except where required by law.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">6. Acceptable use</h2>
        <p>You agree not to use AccountTaxNG to: break the law; infringe anyone's rights; attempt to gain unauthorized access to the service or other tenants' data; upload malicious code; or use the storefront feature to sell anything illegal under Nigerian law.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">7. Your data</h2>
        <p>You own the financial and business data you enter into AccountTaxNG. We process it to provide the service to you, as described in our <a href="{{ route('marketing.privacy') }}" class="text-[#D4AF37] font-600 hover:underline">Privacy Policy</a>. You're responsible for the accuracy of the data you enter, including for tax filing purposes — AccountTaxNG helps you compute and track your obligations, but you remain responsible for your filings with FIRS/NRS and other authorities.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">8. Intellectual property</h2>
        <p>AccountTaxNG, its design, and underlying software belong to Bytestream Technologies. These Terms don't grant you any rights to our trademarks or branding beyond what's needed to use the service.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">9. Availability</h2>
        <p>We aim to keep AccountTaxNG available and reliable, but we don't guarantee uninterrupted access — maintenance, third-party outages, or unforeseen issues can affect availability. We'll aim to give notice of planned maintenance where practical.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">10. Limitation of liability</h2>
        <p>To the extent permitted by Nigerian law, AccountTaxNG is provided "as is." We are not liable for indirect, incidental, or consequential damages arising from your use of the service, including errors in tax computations that you have not independently verified before filing. Nothing in these Terms limits liability that cannot be limited under applicable law.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">11. Termination</h2>
        <p>You may close your account at any time. We may suspend or terminate accounts that breach these Terms, misuse the service, or fail to pay for a subscribed plan, after reasonable notice where practical.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">12. Governing law</h2>
        <p>These Terms are governed by the laws of the Federal Republic of Nigeria, and any dispute is subject to the exclusive jurisdiction of the Nigerian courts.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">13. Changes to these Terms</h2>
        <p>We may update these Terms as the service evolves. Continued use after an update means you accept the revised Terms; material changes will be reflected by updating the "last updated" date above.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">14. Contact</h2>
        <p>Questions about these Terms? Email <a href="mailto:support@accounttaxng.com" class="text-[#D4AF37] font-600 hover:underline">support@accounttaxng.com</a> or use our <a href="{{ route('marketing.contact') }}" class="text-[#D4AF37] font-600 hover:underline">contact form</a>.</p>

    </div>
</section>

@endsection
