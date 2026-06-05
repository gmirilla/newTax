<header x-data="{ open: false, scrolled: false }"
        x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 20)"
        :class="scrolled ? 'bg-[#0A1A2F]/98 backdrop-blur-md shadow-lg shadow-black/20' : 'bg-transparent'"
        class="fixed top-0 inset-x-0 z-50 transition-all duration-300">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-18">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center flex-shrink-0">
                <img src="{{ asset('images/logo/logo.png') }}"
                     alt="AccountTaxNG"
                     class="h-8 w-auto">
            </a>

            {{-- Desktop nav --}}
            <div class="hidden lg:flex items-center gap-8">
                <a href="{{ route('marketing.features') }}"
                   class="text-sm font-medium text-slate-900 hover:text-white transition-colors {{ request()->routeIs('marketing.features') ? 'text-white' : '' }}">
                    Features
                </a>
                <a href="{{ route('marketing.pricing') }}"
                   class="text-sm font-medium text-slate-900 hover:text-white transition-colors {{ request()->routeIs('marketing.pricing') ? 'text-white' : '' }}">
                    Pricing
                </a>
                <a href="{{ route('marketing.about') }}"
                   class="text-sm font-medium text-slate-900 hover:text-white transition-colors {{ request()->routeIs('marketing.about') ? 'text-white' : '' }}">
                    About
                </a>

                {{-- Resources dropdown --}}
                <div x-data="{ resOpen: false }" class="relative"
                     @mouseenter="resOpen = true" @mouseleave="resOpen = false">
                    <button type="button"
                            class="flex items-center gap-1 text-sm font-medium text-slate-900 hover:text-white transition-colors"
                            :class="resOpen ? 'text-white' : ''">
                        Resources
                        <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="resOpen ? 'rotate-180' : ''"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="resOpen" x-cloak x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute top-full left-1/2 -translate-x-1/2 pt-3 w-64">
                        <div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
                            <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">User Guides</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('help.show', 'getting-started') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.82m5.84-2.56a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.63 3.28a14.96 14.96 0 01.16 4.25M12 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Getting Started
                                </a>
                                <a href="{{ route('help.show', 'invoicing') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/></svg>
                                    Invoices & Receipts
                                </a>
                                <a href="{{ route('help.show', 'reports') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    Financial Reports
                                </a>
                                <a href="{{ route('help.show', 'inventory') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    Inventory
                                </a>
                                <a href="{{ route('help.show', 'payroll') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Payroll & PAYE
                                </a>
                            </div>
                            <div class="border-t border-gray-100 px-4 py-2.5">
                                <a href="{{ route('help.index') }}"
                                   class="flex items-center justify-between text-xs font-semibold text-green-600 hover:text-green-700 transition-colors">
                                    View all guides
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="https://forum.accounttaxng.com" target="_blank"
                   class="text-sm font-medium text-slate-900 hover:text-white transition-colors">
                    Forum
                </a>
                <a href="{{ route('marketing.contact') }}"
                   class="text-sm font-medium text-slate-900 hover:text-white transition-colors {{ request()->routeIs('marketing.contact') ? 'text-white' : '' }}">
                    Contact
                </a>
            </div>

            {{-- Desktop CTAs --}}
            <div class="hidden lg:flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="text-sm font-medium text-slate-900 hover:text-white transition-colors px-3 py-1.5">
                        Go to App
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="text-sm font-medium text-slate-900 hover:text-white transition-colors px-3 py-1.5">
                        Log In
                    </a>
                    <a href="{{ route('register') }}"
                       class="btn-gold text-sm px-5 py-2.5 rounded-lg inline-flex items-center gap-1.5">
                        Start Free Trial
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                @endauth
            </div>

            {{-- Mobile hamburger --}}
            <button @click="open = !open"
                    class="lg:hidden p-2 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition-colors">
                <svg x-show="!open" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
                <svg x-show="open" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak x-transition
             class="lg:hidden border-t border-white/10 py-4 space-y-1 pb-6 bg-black/50 backdrop-blur-md mt-2 rounded-lg">
            <a href="{{ route('marketing.features') }}" class="block px-4 py-2.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Features</a>
            <a href="{{ route('marketing.pricing') }}"  class="block px-4 py-2.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Pricing</a>
            <a href="{{ route('marketing.about') }}"    class="block px-4 py-2.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">About</a>
            <a href="https://forum.accounttaxng.com" target="_blank" class="block px-4 py-2.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Forum</a>
            <a href="{{ route('marketing.contact') }}"  class="block px-4 py-2.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Contact</a>

            {{-- Resources / Help --}}
            <div class="pt-2 mt-1 border-t border-white/10">
                <p class="px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-500">Resources</p>
                <a href="{{ route('help.show', 'getting-started') }}" class="block px-4 py-2 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Getting Started</a>
                <a href="{{ route('help.show', 'invoicing') }}"       class="block px-4 py-2 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Invoices & Receipts</a>
                <a href="{{ route('help.show', 'reports') }}"         class="block px-4 py-2 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Financial Reports</a>
                <a href="{{ route('help.show', 'inventory') }}"       class="block px-4 py-2 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Inventory</a>
                <a href="{{ route('help.show', 'payroll') }}"         class="block px-4 py-2 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Payroll & PAYE</a>
                <a href="{{ route('help.index') }}"                   class="block px-4 py-2 text-sm font-semibold text-green-400 hover:text-green-300 hover:bg-white/5 rounded-lg transition-colors">All guides →</a>
            </div>
            <div class="pt-3 px-4 flex flex-col gap-2 border-t border-white/10 mt-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="w-full text-center py-2.5 text-sm font-semibold text-white bg-white/10 rounded-lg">Go to App</a>
                @else
                    <a href="{{ route('login') }}"    class="w-full text-center py-2.5 text-sm font-medium text-slate-300 bg-white/5 rounded-lg border border-white/10">Log In</a>
                    <a href="{{ route('register') }}" class="w-full text-center py-2.5 text-sm font-bold btn-gold rounded-lg">Start Free Trial</a>
                @endauth
            </div>
        </div>
    </nav>
</header>
