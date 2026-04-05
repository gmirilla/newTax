<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NaijaBooks') – Nigerian Tax & Bookkeeping</title>

    {{-- TailwindCSS via CDN (replace with Vite in production) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .naija-green {
            color: #008751;
        }

        .bg-naija-green {
            background-color: #008751;
        }

        .border-naija-green {
            border-color: #008751;
        }
    </style>

    @stack('styles')
</head>

<body class="h-full font-sans antialiased">

    <div class="min-h-full" x-data="{ sidebarOpen: false }">

        {{-- Mobile sidebar backdrop --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 md:hidden"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
        </div>

        {{-- Mobile sidebar panel --}}
        <div x-show="sidebarOpen" x-cloak
            class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col md:hidden"
            x-transition:enter="transition ease-in-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full">
            <div class="flex flex-grow flex-col overflow-y-auto bg-naija-green pt-5 pb-4">

                {{-- Close button --}}
                <div class="flex items-center justify-between px-4 mb-2">
                    <span class="text-2xl font-bold text-white">🇳🇬 NaijaBooks</span>
                    <button @click="sidebarOpen = false" class="text-green-200 hover:text-white focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Company Name --}}
                <div class="px-4">
                    <p class="text-green-200 text-sm truncate">{{ $currentTenant->name ?? '' }}</p>
                    <span class="inline-flex items-center rounded-full bg-green-700 px-2 py-0.5 text-xs text-green-100 mt-1">
                        {{ strtoupper($currentTenant->tax_category ?? 'SME') }} Company
                    </span>
                </div>

                {{-- Navigation --}}
                <nav class="mt-5 flex flex-1 flex-col divide-y divide-green-700 overflow-y-auto">
                    <div class="space-y-1 px-2">
                        <a href="{{ route('dashboard') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('dashboard') ? 'bg-green-900' : '' }}">
                            📊 Dashboard
                        </a>
                        <a href="{{ route('invoices.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('invoices.*') ? 'bg-green-900' : '' }}">
                            🧾 Invoices
                        </a>
                        <a href="{{ route('transactions.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('transactions.*') ? 'bg-green-900' : '' }}">
                            💰 Transactions
                        </a>
                        <a href="{{ route('transactions.expenses') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('transactions.expenses*') ? 'bg-green-900' : '' }}">
                            🧮 Expenses
                        </a>
                    </div>

                    <div class="mt-2 pt-2 space-y-1 px-2">
                        <p class="px-2 py-1 text-xs font-semibold text-green-300 uppercase tracking-wider">Tax Compliance</p>
                        <a href="{{ route('tax.dashboard') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('tax.*') ? 'bg-green-900' : '' }}">
                            🏛️ Tax Dashboard
                        </a>
                        <a href="{{ route('tax.vat.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('tax.vat.*') ? 'bg-green-900' : '' }}">
                            📋 VAT Returns
                        </a>
                        <a href="{{ route('tax.wht.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('tax.wht.*') ? 'bg-green-900' : '' }}">
                            🔖 WHT Records
                        </a>
                        <a href="{{ route('tax.cit.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('tax.cit.*') ? 'bg-green-900' : '' }}">
                            🏢 CIT (Company Tax)
                        </a>
                    </div>

                    <div class="mt-2 pt-2 space-y-1 px-2">
                        <p class="px-2 py-1 text-xs font-semibold text-green-300 uppercase tracking-wider">HR & Payroll</p>
                        <a href="{{ route('payroll.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('payroll.*') ? 'bg-green-900' : '' }}">
                            👥 Payroll & PAYE
                        </a>
                        <a href="{{ route('payroll.employees') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('payroll.employees*') ? 'bg-green-900' : '' }}">
                            🧑‍💼 Employees
                        </a>
                    </div>

                    <div class="mt-2 pt-2 space-y-1 px-2">
                        <p class="px-2 py-1 text-xs font-semibold text-green-300 uppercase tracking-wider">Reports</p>
                        <a href="{{ route('reports.pl') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('reports.pl*') ? 'bg-green-900' : '' }}">
                            📈 Profit & Loss
                        </a>
                        <a href="{{ route('reports.bs') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('reports.bs*') ? 'bg-green-900' : '' }}">
                            📉 Balance Sheet
                        </a>
                        <a href="{{ route('reports.tax-summary') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('reports.tax-summary*') ? 'bg-green-900' : '' }}">
                            📑 Tax Summary
                        </a>
                    </div>
                </nav>
            </div>
        </div>

        {{-- Desktop sidebar --}}
        <aside class="hidden md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col">
            <div class="flex flex-grow flex-col overflow-y-auto bg-naija-green pt-5 pb-4">

                {{-- Logo --}}
                <div class="flex flex-shrink-0 items-center px-4">
                    <span class="text-2xl font-bold text-white">🇳🇬 NaijaBooks</span>
                </div>

                {{-- Company Name --}}
                <div class="mt-2 px-4">
                    <p class="text-green-200 text-sm truncate">{{ $currentTenant->name ?? '' }}</p>
                    <span class="inline-flex items-center rounded-full bg-green-700 px-2 py-0.5 text-xs text-green-100 mt-1">
                        {{ strtoupper($currentTenant->tax_category ?? 'SME') }} Company
                    </span>
                </div>

                {{-- Navigation --}}
                <nav class="mt-5 flex flex-1 flex-col divide-y divide-green-700 overflow-y-auto">
                    <div class="space-y-1 px-2">
                        <a href="{{ route('dashboard') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('dashboard') ? 'bg-green-900' : '' }}">
                            📊 Dashboard
                        </a>
                        <a href="{{ route('invoices.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('invoices.*') ? 'bg-green-900' : '' }}">
                            🧾 Invoices
                        </a>
                        <a href="{{ route('transactions.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('transactions.*') ? 'bg-green-900' : '' }}">
                            💰 Transactions
                        </a>
                        <a href="{{ route('transactions.expenses') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('transactions.expenses*') ? 'bg-green-900' : '' }}">
                            🧮 Expenses
                        </a>
                    </div>

                    <div class="mt-2 pt-2 space-y-1 px-2">
                        <p class="px-2 py-1 text-xs font-semibold text-green-300 uppercase tracking-wider">Tax Compliance</p>
                        <a href="{{ route('tax.dashboard') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('tax.*') ? 'bg-green-900' : '' }}">
                            🏛️ Tax Dashboard
                        </a>
                        <a href="{{ route('tax.vat.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('tax.vat.*') ? 'bg-green-900' : '' }}">
                            📋 VAT Returns
                        </a>
                        <a href="{{ route('tax.wht.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('tax.wht.*') ? 'bg-green-900' : '' }}">
                            🔖 WHT Records
                        </a>
                        <a href="{{ route('tax.cit.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('tax.cit.*') ? 'bg-green-900' : '' }}">
                            🏢 CIT (Company Tax)
                        </a>
                    </div>

                    <div class="mt-2 pt-2 space-y-1 px-2">
                        <p class="px-2 py-1 text-xs font-semibold text-green-300 uppercase tracking-wider">HR & Payroll</p>
                        <a href="{{ route('payroll.index') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('payroll.*') ? 'bg-green-900' : '' }}">
                            👥 Payroll & PAYE
                        </a>
                        <a href="{{ route('payroll.employees') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('payroll.employees*') ? 'bg-green-900' : '' }}">
                            🧑‍💼 Employees
                        </a>
                    </div>

                    <div class="mt-2 pt-2 space-y-1 px-2">
                        <p class="px-2 py-1 text-xs font-semibold text-green-300 uppercase tracking-wider">Reports</p>
                        <a href="{{ route('reports.pl') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('reports.pl*') ? 'bg-green-900' : '' }}">
                            📈 Profit & Loss
                        </a>
                        <a href="{{ route('reports.bs') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('reports.bs*') ? 'bg-green-900' : '' }}">
                            📉 Balance Sheet
                        </a>
                        <a href="{{ route('reports.tax-summary') }}"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 {{ request()->routeIs('reports.tax-summary*') ? 'bg-green-900' : '' }}">
                            📑 Tax Summary
                        </a>
                    </div>
                </nav>

                {{-- ── User menu (dropdown) ── --}}
                <div class="flex-shrink-0 border-t border-green-700 relative" x-data="{ open: false }"
                    @click.outside="open = false">

                    {{-- Trigger --}}
                    <button type="button" @click="open = !open"
                        class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-green-700 transition-colors focus:outline-none">
                        {{-- Avatar --}}
                        <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-green-900">
                            <span class="text-sm font-medium leading-none text-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </span>
                        </span>
                        {{-- Name + role --}}
                        <span class="flex-1 min-w-0">
                            <span class="block text-sm font-medium text-white truncate">{{ auth()->user()->name }}</span>
                            <span class="block text-xs text-green-300 capitalize">{{ auth()->user()->role }}</span>
                        </span>
                        {{-- Chevron --}}
                        <svg class="h-4 w-4 text-green-300 flex-shrink-0 transition-transform duration-150"
                            :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- Dropdown panel — slides up from the trigger --}}
                    <div x-show="open" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute bottom-full mb-1 left-2 right-2 z-50 rounded-lg bg-white shadow-xl border border-gray-200 overflow-hidden">

                        {{-- My Profile --}}
                        <a href="{{ route('profile.edit') }}"
                            class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors {{ request()->routeIs('profile.*') ? 'bg-gray-50 font-medium' : '' }}">
                            <span class="flex-shrink-0 text-base">👤</span>
                            <span>My Profile</span>
                        </a>

                        @if (auth()->user()->isAdmin())
                            <div class="border-t border-gray-100 mx-3"></div>

                            <a href="{{ route('settings.company') }}"
                                class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors {{ request()->routeIs('settings.company*') ? 'bg-gray-50 font-medium' : '' }}">
                                <span class="flex-shrink-0 text-base">🏢</span>
                                <span>Company Settings</span>
                                <span class="ml-auto text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium">Admin</span>
                            </a>

                            {{-- FIRS Configuration --}}
                            @php
                                $firsActive = \App\Models\TenantFirsCredential::where('tenant_id', auth()->user()->tenant_id)
                                    ->where('is_active', true)
                                    ->exists();
                            @endphp
                            <a href="{{ route('settings.firs') }}"
                                class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors {{ request()->routeIs('settings.firs*') ? 'bg-gray-50 font-medium' : '' }}">
                                <span class="flex-shrink-0 text-base">⬆</span>
                                <div class="flex-1 min-w-0">
                                    <span class="block">FIRS e-Invoicing</span>
                                    <span class="block text-xs {{ $firsActive ? 'text-green-600' : 'text-gray-400' }}">
                                        {{ $firsActive ? '● Configured' : '○ Not configured' }}
                                    </span>
                                </div>
                                <span class="ml-auto text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium">Admin</span>
                            </a>
                        @endif

                        <div class="border-t border-gray-100 mx-3"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <span class="flex-shrink-0 text-base">🚪</span>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>
                {{-- ── End user menu ── --}}

            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex flex-1 flex-col md:pl-64">

            {{-- Top bar --}}
            <div class="sticky top-0 z-10 flex h-16 flex-shrink-0 bg-white shadow">
                <div class="flex flex-1 items-center justify-between px-4 md:px-6">

                    {{-- Hamburger (mobile only) --}}
                    <button type="button" @click="sidebarOpen = true"
                        class="md:hidden inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>

                    <div class="flex items-center gap-4">
                        {{-- VAT alert badge --}}
                        @php $nextVatDue = \App\Services\VatService::VAT_FILING_DAY; @endphp
                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800">
                            VAT Due: {{ now()->setDay($nextVatDue)->format('M d') }}
                        </span>
                        <span class="hidden sm:block text-sm text-gray-500">₦ NGN</span>

                        {{-- Top-bar user avatar dropdown --}}
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" @click="open = !open"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-700 text-white text-xs font-semibold hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </button>

                            <div x-show="open" x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 top-10 z-50 w-56 rounded-lg bg-white shadow-xl border border-gray-200 overflow-hidden">

                                {{-- User info header --}}
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                                    <span class="inline-block mt-1 text-xs capitalize bg-green-100 text-green-800 px-1.5 py-0.5 rounded">
                                        {{ auth()->user()->role }}
                                    </span>
                                </div>

                                <a href="{{ route('profile.edit') }}"
                                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('profile.*') ? 'bg-gray-50 font-medium' : '' }}">
                                    <span>👤</span> My Profile
                                </a>

                                @if (auth()->user()->isAdmin())
                                    <div class="border-t border-gray-100 mx-3"></div>

                                    <a href="{{ route('settings.company') }}"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('settings.company*') ? 'bg-gray-50 font-medium' : '' }}">
                                        <span>🏢</span>
                                        <span class="flex-1">Company Settings</span>
                                        <span class="text-xs bg-gray-100 text-gray-500 px-1 rounded">Admin</span>
                                    </a>

                                    <a href="{{ route('settings.firs') }}"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('settings.firs*') ? 'bg-gray-50 font-medium' : '' }}">
                                        <span>⬆</span>
                                        <span class="flex-1">FIRS e-Invoicing</span>
                                        <span class="text-xs bg-gray-100 text-gray-500 px-1 rounded">Admin</span>
                                    </a>
                                @endif

                                <div class="border-t border-gray-100 mx-3"></div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                                        <span>🚪</span> Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                        {{-- End top-bar user avatar --}}
                    </div>
                </div>
            </div>

            {{-- Flash messages --}}
            <div class="px-4 pt-4 md:px-6">
                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4 border border-green-200">
                        <p class="text-sm text-green-800">✅ {{ session('success') }}</p>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
                        <p class="text-sm text-red-800">❌ {{ session('error') }}</p>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
                        <ul class="list-disc pl-5 text-sm text-red-800">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Page content --}}
            <main class="flex-1 pb-8">
                <div class="px-4 py-4 md:px-6">
                    @yield('content')
                </div>
            </main>

        </div>
    </div>

    @stack('scripts')
</body>

</html>
