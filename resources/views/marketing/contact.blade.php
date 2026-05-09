@extends('marketing.layouts.app')

@section('title', 'Contact Us — AccountTaxNG | Get in Touch or Book a Demo')
@section('meta_description', 'Contact AccountTaxNG for support, demos, or partnership enquiries. Our Nigerian-based team is ready to help you get started with accounting and tax compliance.')

@section('content')

{{-- HERO --}}
<section class="gradient-hero pt-32 pb-16 lg:pt-40 lg:pb-20 relative overflow-hidden">
    <div class="absolute inset-0 dot-pattern opacity-20 pointer-events-none"></div>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
        <div class="section-badge mb-6 mx-auto">Contact Us</div>
        <h1 class="font-display text-4xl lg:text-5xl font-900 text-white leading-tight">
            Let's talk.
        </h1>
        <p class="mt-4 text-lg text-slate-300 leading-relaxed">
            Whether you have a question about features, pricing, need a product demo, or want to explore a partnership — we're here to help.
        </p>
    </div>
</section>

{{-- CONTACT SECTION --}}
<section id="contact" class="py-20 lg:py-28 bg-[#F5F7FA]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-5 gap-12">

            {{-- Contact info sidebar --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- Intro --}}
                <div>
                    <h2 class="font-display text-2xl font-800 text-[#0A1A2F] mb-3">Get in touch</h2>
                    <p class="text-sm text-[#64748B] leading-relaxed">
                        Our team is based in Nigeria and typically responds within one business day. For urgent matters, use the phone number or WhatsApp below.
                    </p>
                </div>

                {{-- Contact details --}}
                <div class="space-y-5">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-[#0A1A2F] flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-700 text-[#1E293B]">Email</p>
                            <a href="mailto:hello@accounttaxng.com" class="text-sm text-[#64748B] hover:text-[#D4AF37] transition-colors">hello@accounttaxng.com</a>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-[#0A1A2F] flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-700 text-[#1E293B]">Phone & WhatsApp</p>
                            <a href="tel:+2348000000000" class="text-sm text-[#64748B] hover:text-[#D4AF37] transition-colors">+234 800 000 0000</a>
                            <p class="text-xs text-slate-400 mt-0.5">Mon–Fri, 8am–6pm WAT</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-[#0A1A2F] flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-700 text-[#1E293B]">Office</p>
                            <p class="text-sm text-[#64748B]">Bytestream Technologies</p>
                            <p class="text-sm text-[#64748B]">Lagos, Nigeria 🇳🇬</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-[#0A1A2F] flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-700 text-[#1E293B]">Support Portal</p>
                            <a href="#" class="text-sm text-[#64748B] hover:text-[#D4AF37] transition-colors">support.accounttaxng.com</a>
                            <p class="text-xs text-slate-400 mt-0.5">Knowledge base & ticketing</p>
                        </div>
                    </div>
                </div>

                {{-- Social links --}}
                <div>
                    <p class="text-xs font-700 text-[#64748B] uppercase tracking-wider mb-3">Follow Us</p>
                    <div class="flex gap-3">
                        <a href="#" class="w-9 h-9 rounded-lg bg-[#0A1A2F] hover:bg-[#D4AF37] group flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4 text-slate-400 group-hover:text-[#0A1A2F] transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-[#0A1A2F] hover:bg-[#D4AF37] group flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4 text-slate-400 group-hover:text-[#0A1A2F] transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-[#0A1A2F] hover:bg-[#D4AF37] group flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4 text-slate-400 group-hover:text-[#0A1A2F] transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Demo CTA card --}}
                <div id="demo" class="bg-[#0A1A2F] rounded-2xl p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 rounded-full blur-2xl opacity-20"
                         style="background:radial-gradient(circle,#D4AF37,transparent)"></div>
                    <div class="relative">
                        <div class="w-8 h-8 rounded-lg bg-[#D4AF37]/15 border border-[#D4AF37]/30 flex items-center justify-center mb-3">
                            <svg class="w-4 h-4 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/></svg>
                        </div>
                        <h3 class="font-display font-700 text-white text-sm mb-1.5">Book a Live Demo</h3>
                        <p class="text-xs text-slate-400 mb-4 leading-relaxed">See AccountTaxNG in action with a 30-minute personalised demo. We'll walk through your specific use case.</p>
                        <a href="{{ route('register') }}" class="btn-gold text-xs font-700 px-4 py-2 rounded-lg inline-block">
                            Schedule Demo →
                        </a>
                    </div>
                </div>
            </div>

            {{-- Contact form --}}
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8 lg:p-10">
                    <h2 class="font-display text-xl font-800 text-[#0A1A2F] mb-2">Send us a message</h2>
                    <p class="text-sm text-[#64748B] mb-7">We typically reply within one business day.</p>

                    @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-4 mb-6 flex items-start gap-3">
                        <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <div>
                            <p class="text-sm font-600">Message received!</p>
                            <p class="text-sm">{{ session('success') }}</p>
                        </div>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('marketing.contact.submit') }}" class="space-y-5">
                        @csrf

                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" placeholder="Amaka Okonkwo"
                                       class="w-full px-4 py-3 rounded-xl border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-slate-200' }} text-sm text-[#1E293B] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/40 focus:border-[#D4AF37] transition-colors"
                                       required>
                                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="amaka@company.com"
                                       class="w-full px-4 py-3 rounded-xl border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-slate-200' }} text-sm text-[#1E293B] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/40 focus:border-[#D4AF37] transition-colors"
                                       required>
                                @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Phone Number</label>
                                <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+234 801 000 0000"
                                       class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm text-[#1E293B] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/40 focus:border-[#D4AF37] transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Company Name</label>
                                <input type="text" name="company" value="{{ old('company') }}" placeholder="Okonkwo Trading Ltd"
                                       class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm text-[#1E293B] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/40 focus:border-[#D4AF37] transition-colors">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-600 text-[#1E293B] mb-1.5">How can we help? <span class="text-red-500">*</span></label>
                            <select name="subject"
                                    class="w-full px-4 py-3 rounded-xl border {{ $errors->has('subject') ? 'border-red-400 bg-red-50' : 'border-slate-200' }} text-sm text-[#1E293B] focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/40 focus:border-[#D4AF37] transition-colors bg-white"
                                    required>
                                <option value="" disabled {{ !old('subject') ? 'selected' : '' }}>Select a topic</option>
                                <option value="general"     {{ old('subject') === 'general' ? 'selected' : '' }}>General enquiry</option>
                                <option value="demo"        {{ old('subject') === 'demo' ? 'selected' : '' }}>Book a demo</option>
                                <option value="support"     {{ old('subject') === 'support' ? 'selected' : '' }}>Technical support</option>
                                <option value="billing"     {{ old('subject') === 'billing' ? 'selected' : '' }}>Billing & pricing</option>
                                <option value="partnership" {{ old('subject') === 'partnership' ? 'selected' : '' }}>Partnership / accountant programme</option>
                            </select>
                            @error('subject')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-600 text-[#1E293B] mb-1.5">Message <span class="text-red-500">*</span></label>
                            <textarea name="message" rows="5" placeholder="Tell us more about your business and what you're looking for..."
                                      class="w-full px-4 py-3 rounded-xl border {{ $errors->has('message') ? 'border-red-400 bg-red-50' : 'border-slate-200' }} text-sm text-[#1E293B] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/40 focus:border-[#D4AF37] transition-colors resize-none"
                                      required>{{ old('message') }}</textarea>
                            @error('message')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                    class="w-full btn-gold py-3.5 rounded-xl text-sm font-700 flex items-center justify-center gap-2">
                                Send Message
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </button>
                            <p class="text-xs text-center text-slate-400 mt-3">
                                By submitting, you agree to our <a href="#" class="underline hover:text-[#0A1A2F]">Privacy Policy</a>.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- QUICK LINKS --}}
<section class="py-16 bg-white border-t border-slate-100">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm font-600 text-[#64748B] mb-8">Or get started right away</p>
        <div class="grid sm:grid-cols-3 gap-5">
            <a href="{{ route('register') }}" class="bg-[#0A1A2F] rounded-2xl p-6 text-center hover:bg-[#0d2444] transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-[#D4AF37]/15 border border-[#D4AF37]/30 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-5 h-5 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <p class="text-sm font-700 text-white mb-1">Start Free Trial</p>
                <p class="text-xs text-slate-400">14 days, no card required</p>
            </a>
            <a href="{{ route('marketing.pricing') }}" class="bg-[#F5F7FA] border border-slate-100 rounded-2xl p-6 text-center hover:border-[#D4AF37]/40 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-[#0A1A2F]/8 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-5 h-5 text-[#0A1A2F]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-sm font-700 text-[#1E293B] mb-1">View Pricing</p>
                <p class="text-xs text-[#64748B]">Transparent, SME-friendly plans</p>
            </a>
            <a href="{{ route('marketing.features') }}" class="bg-[#F5F7FA] border border-slate-100 rounded-2xl p-6 text-center hover:border-[#D4AF37]/40 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-[#0A1A2F]/8 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-5 h-5 text-[#0A1A2F]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                </div>
                <p class="text-sm font-700 text-[#1E293B] mb-1">Explore Features</p>
                <p class="text-xs text-[#64748B]">Everything AccountTaxNG can do</p>
            </a>
        </div>
    </div>
</section>

@endsection
