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
        [x-cloak] { display: none !important; }
        .naija-green { color: #008751; }
        .bg-naija-green { background-color: #008751; }
        .border-naija-green { border-color: #008751; }
    </style>

    @stack('styles')
</head>
<body class="h-full font-sans antialiased">

<div class="min-h-full" x-data="{ sidebarOpen: false }">

    {{-- Mobile sidebar backdrop --}}
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 flex md:hidden">
        <div @click="sidebarOpen = false" class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>
    </div>

    {{-- Sidebar --}}
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
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700">
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
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700">
                        📋 VAT Returns
                    </a>
                    <a href="{{ route('tax.wht.index') }}"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700">
                        🔖 WHT Records
                    </a>
                    <a href="{{ route('tax.cit.index') }}"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700">
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
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700">
                        🧑‍💼 Employees
                    </a>
                </div>

                <div class="mt-2 pt-2 space-y-1 px-2">
                    <p class="px-2 py-1 text-xs font-semibold text-green-300 uppercase tracking-wider">Reports</p>
                    <a href="{{ route('reports.pl') }}"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700">
                        📈 Profit & Loss
                    </a>
                    <a href="{{ route('reports.bs') }}"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700">
                        📉 Balance Sheet
                    </a>
                    <a href="{{ route('reports.tax-summary') }}"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700">
                        📑 Tax Summary
                    </a>
                </div>
            </nav>

            {{-- User info --}}
            <div class="flex flex-shrink-0 border-t border-green-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-900">
                            <span class="text-sm font-medium leading-none text-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </span>
                        </span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-green-300 capitalize">{{ auth()->user()->role }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                        @csrf
                        <button type="submit" class="text-green-300 hover:text-white text-xs">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="flex flex-1 flex-col md:pl-64">

        {{-- Top bar --}}
        <div class="sticky top-0 z-10 flex h-16 flex-shrink-0 bg-white shadow">
            <div class="flex flex-1 justify-between px-4 md:px-6">
                <div class="flex flex-1 items-center">
                    <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                </div>
                <div class="ml-4 flex items-center gap-4">
                    {{-- VAT alert badge --}}
                    @php $nextVatDue = \App\Services\VatService::VAT_FILING_DAY; @endphp
                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800">
                        VAT Due: {{ now()->setDay($nextVatDue)->format('M d') }}
                    </span>
                    <span class="text-sm text-gray-500">₦ NGN</span>
                </div>
            </div>
        </div>

        {{-- Flash messages --}}
        <div class="px-4 pt-4 md:px-6">
            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm text-green-800">✅ {{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
                    <p class="text-sm text-red-800">❌ {{ session('error') }}</p>
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
                    <ul class="list-disc pl-5 text-sm text-red-800">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 pb-8">
            <div class="px-4 py-4 md:px-6">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md text-sm text-green-700">
                    {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
                    {{ session('error') }}
                </div>
                @endif
                @yield('content')
            </div>
        </main>

    </div>
</div>

@stack('scripts')
</body>
</html>
