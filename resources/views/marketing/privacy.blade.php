@extends('marketing.layouts.app')

@section('title', 'Privacy Policy — AccountTaxNG')
@section('meta_description', 'How AccountTaxNG (a product of Bytestream Technologies) collects, uses, and protects your data, and your rights under the Nigeria Data Protection Act.')

@section('content')

{{-- HERO --}}
<section class="gradient-hero pt-32 pb-16 lg:pt-44 lg:pb-20 relative overflow-hidden">
    <div class="absolute inset-0 grid-bg opacity-30 pointer-events-none"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="section-badge mb-6">Legal</div>
        <h1 class="font-display text-4xl lg:text-5xl font-900 text-white leading-tight">Privacy Policy</h1>
        <p class="mt-6 text-lg text-slate-300 leading-relaxed max-w-2xl">
            Last updated {{ now()->format('d F Y') }}. This explains what data AccountTaxNG collects, why, who we share it with, and the rights you have over it under Nigerian data protection law.
        </p>
    </div>
</section>

{{-- CONTENT --}}
<section class="py-16 lg:py-24 bg-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 prose-dark">

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-12 text-sm text-amber-900">
            This policy is written in plain language to describe, accurately, what this product actually does with data today. It is not a substitute for independent legal advice, and Bytestream Technologies may update it as the service changes.
        </div>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">1. Who we are</h2>
        <p>AccountTaxNG is an accounting and tax compliance platform operated by <strong>Bytestream Technologies</strong> ("we", "us", "our"), a Nigerian technology company. This policy applies to accounttaxng.com, the AccountTaxNG application, and any storefront pages hosted on behalf of our business customers ("tenants").</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">2. What we collect</h2>
        <p>We collect different data depending on how you interact with us:</p>
        <ul class="list-disc pl-6 space-y-2">
            <li><strong>Company/account registration:</strong> company name, Tax Identification Number (TIN), business type, state, annual turnover, and the registering administrator's name, email, and password.</li>
            <li><strong>Financial records you enter:</strong> invoices, quotes, expenses, customers, vendors, bank/transaction records, and — if you use payroll — employee names, salaries, and statutory deduction details. This data belongs to you; we process it to operate the service you've subscribed to.</li>
            <li><strong>Storefront customer data:</strong> if you enable the public storefront feature, your shoppers' name, phone number, email, and delivery address are collected to process their orders.</li>
            <li><strong>Technical data:</strong> IP address, browser/device information, and session cookies needed to keep you securely logged in (see Cookies, below).</li>
            <li><strong>Communications:</strong> anything you send us via the contact form or support channels.</li>
        </ul>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">3. Why we process it, and on what basis</h2>
        <ul class="list-disc pl-6 space-y-2">
            <li><strong>To provide the service</strong> you've signed up for — performance of our contract with you.</li>
            <li><strong>To meet legal and tax obligations</strong> — including Nigerian tax record-keeping requirements that apply to accounting software.</li>
            <li><strong>For security and fraud prevention</strong>, and to keep the service reliable — our legitimate interest, balanced against your rights.</li>
            <li><strong>With your consent</strong>, for anything outside the above (e.g. if we ever introduce optional marketing communications, they will be opt-in and separately consented to).</li>
        </ul>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">4. Who we share data with</h2>
        <p>We do not sell personal data. We share data only with the service providers who help us operate AccountTaxNG, under contract and only for that purpose:</p>
        <ul class="list-disc pl-6 space-y-2">
            <li><strong>Paystack</strong> — processes subscription payments and billing. Paystack receives the billing information necessary to charge and reconcile your subscription.</li>
            <li><strong>Our email delivery provider</strong> — sends transactional email on our behalf (invoice copies, trial/billing notices, team invitations, order confirmations). It receives only the recipient address and message content needed to deliver that email.</li>
            <li><strong>Our cloud hosting provider</strong> — stores the application's data.</li>
        </ul>
        <p>We may also disclose data where required by law or a valid order from a Nigerian regulatory or judicial authority.</p>

        <h2 id="cookies" class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">5. Cookies</h2>
        <p>AccountTaxNG uses only the cookies necessary to run the service: a session cookie that keeps you logged in, and a CSRF token that protects your account from cross-site request forgery. We do not currently use advertising cookies, cross-site tracking pixels, or session-recording/analytics scripts of any kind — if that ever changes, this policy and a cookie-consent prompt will be updated before it does.</p>

        <h2 id="security" class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">6. How we protect your data</h2>
        <p>All connections to AccountTaxNG are encrypted in transit (HTTPS/TLS). Passwords are stored hashed, never in plain text. Access to tenant data is scoped so that one business's data is never visible to another. No system is perfectly secure, and we continue to invest in these protections as the service grows.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">7. How long we keep data</h2>
        <p>We retain your account and financial records for as long as your account is active, and for a reasonable period afterward to meet tax and financial record-keeping obligations under Nigerian law. You can request deletion sooner, subject to those legal retention requirements — see your rights below.</p>

        <h2 id="ndpr" class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">8. Your rights under the Nigeria Data Protection Act</h2>
        <p>Under the NDPA 2023, you have the right to:</p>
        <ul class="list-disc pl-6 space-y-2">
            <li>Access the personal data we hold about you.</li>
            <li>Correct inaccurate or incomplete data.</li>
            <li>Request deletion of your data, subject to our legal retention obligations.</li>
            <li>Restrict or object to certain processing.</li>
            <li>Request a copy of your data in a portable format.</li>
            <li>Lodge a complaint with the <strong>Nigeria Data Protection Commission (NDPC)</strong> if you believe we have mishandled your data.</li>
        </ul>
        <p>To exercise any of these, contact us using the details below.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">9. Children</h2>
        <p>AccountTaxNG is a business tool intended for use by adult business owners, administrators, and staff acting on behalf of a registered company. It is not directed at, and we do not knowingly collect data from, children.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">10. Changes to this policy</h2>
        <p>We may update this policy as the service evolves. Material changes will be reflected by updating the "last updated" date above.</p>

        <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mt-12 mb-4">11. Contact us</h2>
        <p>For any privacy question, data request, or concern, email <a href="mailto:privacy@accounttaxng.com" class="text-[#D4AF37] font-600 hover:underline">privacy@accounttaxng.com</a>, or use our <a href="{{ route('marketing.contact') }}" class="text-[#D4AF37] font-600 hover:underline">contact form</a>.</p>

    </div>
</section>

@endsection
