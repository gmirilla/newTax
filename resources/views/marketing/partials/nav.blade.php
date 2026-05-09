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
            <a href="https://forum.accounttaxng.com" target="_blank" class="block px-4 py-2.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Forum</a>
            <a href="{{ route('marketing.about') }}"    class="block px-4 py-2.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">About</a>
            <a href="{{ route('marketing.contact') }}"  class="block px-4 py-2.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors">Contact</a>
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
