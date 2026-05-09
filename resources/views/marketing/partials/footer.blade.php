<footer class="bg-[#0A1A2F] text-slate-400">

    {{-- Main footer grid --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-10">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-10 lg:gap-12">

            {{-- Brand column --}}
            <div class="col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 mb-4">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                         style="background: linear-gradient(135deg, #D4AF37, #C9A227);">
                        <span class="text-xs font-black text-[#0A1A2F] tracking-tighter">AT</span>
                    </div>
                    <span class="font-display font-800 text-white text-[17px] tracking-tight">
                        Account<span class="text-[#D4AF37]">Tax</span>NG
                    </span>
                </a>
                <p class="text-sm leading-relaxed text-slate-400 max-w-xs">
                    Accounting and tax compliance software built for Nigerian SMEs. Finance Act-aligned, FIRS-ready, cloud-based.
                </p>
                <p class="text-xs text-slate-500 mt-3">
                    A product of <span class="text-slate-300">Bytestream Technologies</span>
                </p>

                {{-- Social links --}}
                <div class="flex items-center gap-3 mt-5">
                    <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-[#D4AF37]/20 hover:text-[#D4AF37] flex items-center justify-center transition-colors" aria-label="Twitter/X">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-[#D4AF37]/20 hover:text-[#D4AF37] flex items-center justify-center transition-colors" aria-label="LinkedIn">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-[#D4AF37]/20 hover:text-[#D4AF37] flex items-center justify-center transition-colors" aria-label="Instagram">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                </div>
            </div>

            {{-- Product links --}}
            <div>
                <h4 class="text-xs font-700 text-white uppercase tracking-widest mb-4">Product</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('marketing.features') }}"               class="hover:text-white transition-colors">Features</a></li>
                    <li><a href="{{ route('marketing.pricing') }}"                class="hover:text-white transition-colors">Pricing</a></li>
                    <li><a href="{{ route('marketing.features') }}#tax"           class="hover:text-white transition-colors">Tax Automation</a></li>
                    <li><a href="{{ route('marketing.features') }}#payroll"       class="hover:text-white transition-colors">Payroll</a></li>
                    <li><a href="{{ route('marketing.features') }}#invoicing"     class="hover:text-white transition-colors">Invoicing</a></li>
                    <li><a href="{{ route('marketing.features') }}#reports"       class="hover:text-white transition-colors">Reports</a></li>
                </ul>
            </div>

            {{-- Company links --}}
            <div>
                <h4 class="text-xs font-700 text-white uppercase tracking-widest mb-4">Company</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('marketing.about') }}"   class="hover:text-white transition-colors">About Us</a></li>
                    <li><a href="{{ route('marketing.contact') }}" class="hover:text-white transition-colors">Contact</a></li>
                    <li><a href="{{ route('marketing.contact') }}#demo" class="hover:text-white transition-colors">Book a Demo</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                </ul>
            </div>

            {{-- Legal links --}}
            <div>
                <h4 class="text-xs font-700 text-white uppercase tracking-widest mb-4">Legal</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Cookie Policy</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Security</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">NDPR Compliance</a></li>
                </ul>
            </div>
        </div>

        {{-- Trust badges --}}
        <div class="mt-12 pt-8 border-t border-white/10 grid grid-cols-2 sm:grid-cols-4 gap-6">
            <div class="flex items-center gap-2.5">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-white">Secure & Encrypted</p>
                    <p class="text-[10px] text-slate-500">256-bit SSL</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-white">NRS Compliant</p>
                    <p class="text-[10px] text-slate-500">Finance Act Aligned</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-white">Nigerian Data Residency</p>
                    <p class="text-[10px] text-slate-500">NDPR Compliant</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15.75V21h5.25M3 15.75L12 6.75l3.375 3.375M3 15.75l3.375-3.375M21 8.25V3h-5.25M21 8.25L12 17.25l-3.375-3.375M21 8.25l-3.375 3.375"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-white">99.9% Uptime SLA</p>
                    <p class="text-[10px] text-slate-500">Always available</p>
                </div>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="mt-8 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-slate-500">
                &copy; {{ date('Y') }} Bytestream Technologies. All rights reserved.
            </p>
            <p class="text-xs text-slate-500">
                Built with ♥ in Nigeria 🇳🇬 &nbsp;·&nbsp; Making compliance easy for Nigerian SMEs
            </p>
        </div>
    </div>
</footer>
