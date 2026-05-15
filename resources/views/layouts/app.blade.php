<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AccountTaxNG') – Nigerian Tax & Bookkeeping</title>

    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Collapse plugin must load before Alpine --}}
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .bg-naija-green  { background-color: #008751; }
        .border-naija-green { border-color: #008751; }
    </style>

    @stack('styles')
</head>

<body class="h-full font-sans antialiased">

@php
    // ── Nav variables — computed once, shared by both mobile & desktop partials ──
    $navRole               = $currentUser->role ?? 'staff';
    $canPayroll            = $currentTenant->planAllows('payroll')      && $currentUser->canAccess('payroll');
    $canInventory          = $currentTenant->planAllows('inventory')     && $currentUser->canAccess('inventory');
    $canInventoryReports   = $currentTenant->planAllows('inventory_reports') && $currentUser->canAccess('reports');
    $canManufacturing      = $currentTenant->planAllows('manufacturing') && $currentUser->canAccess('manufacturing');

    $navAlertCount = $canInventory
        ? \App\Models\InventoryAlert::where('tenant_id', auth()->user()->tenant_id ?? 0)
              ->withoutGlobalScope('tenant')->whereNull('seen_at')->count()
        : 0;
    $navPendingRestocks = $canInventory
        ? \App\Models\RestockRequest::where('tenant_id', auth()->user()->tenant_id ?? 0)
              ->withoutGlobalScope('tenant')->where('status', 'pending')->count()
        : 0;

    // Section auto-open states — true when the current page belongs to that group
    $navInSales     = request()->routeIs('quotes.*', 'invoices.*', 'transactions.*');
    $navInTax       = request()->routeIs('tax.*');
    $navInPayroll   = request()->routeIs('payroll.*');
    $navInInventory      = request()->routeIs('inventory.*') && ! request()->routeIs('inventory.reports.*');
    $navInManufacturing  = request()->routeIs('manufacturing.*');
    $navInReports   = request()->routeIs('reports.*', 'inventory.reports.*');
    $navInSettings  = request()->routeIs('team.*', 'billing*', 'settings.*');
@endphp

<div class="min-h-full" x-data="{ sidebarOpen: false }">

    {{-- ── Mobile sidebar backdrop ──────────────────────────────────────────── --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-gray-600/75 md:hidden"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    </div>

    {{-- ── Mobile sidebar panel ──────────────────────────────────────────────── --}}
    <div x-show="sidebarOpen" x-cloak
         class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col md:hidden"
         x-transition:enter="transition ease-in-out duration-300"
         x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">

        <div class="flex flex-grow flex-col overflow-hidden bg-naija-green pt-4 pb-0">

            {{-- Header --}}
            <div class="flex items-center justify-between px-4 mb-1 flex-shrink-0">
                <span class="text-lg font-bold text-white tracking-tight">🇳🇬 AccountTaxNG</span>
                <button @click="sidebarOpen = false"
                        class="p-1 rounded-md text-green-200 hover:text-white hover:bg-green-700 focus:outline-none">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Tenant badge --}}
            <div class="px-4 pb-3 flex-shrink-0">
                <p class="text-green-200 text-xs truncate font-medium">{{ $currentTenant->name ?? '' }}</p>
                <span class="inline-flex items-center rounded-full bg-green-700/80 px-2 py-0.5 text-[10px] text-green-100 mt-0.5">
                    {{ strtoupper($currentTenant->tax_category ?? 'SME') }} Company
                </span>
            </div>

            <div class="border-t border-green-700/60 flex-shrink-0"></div>

            {{-- Shared nav partial --}}
            @include('layouts.partials._sidebar-nav')

            {{-- User menu (mobile) --}}
            <div class="flex-shrink-0 border-t border-green-700/60" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open"
                        class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-green-700/60 transition-colors focus:outline-none">
                    <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-900">
                        <span class="text-xs font-semibold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                    </span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-medium text-white truncate">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-green-300 capitalize">{{ auth()->user()->role }}</span>
                    </span>
                    <svg class="h-4 w-4 text-green-300 flex-shrink-0 transition-transform duration-150"
                         :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak x-collapse
                     class="bg-white border-t border-gray-100 overflow-hidden">
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('profile.*') ? 'bg-gray-50 font-medium' : '' }}">
                        <span>👤</span> My Profile
                    </a>
                    @if(auth()->user()->isAdmin())
                    <div class="border-t border-gray-100 mx-4"></div>
                    <a href="{{ route('settings.company') }}"
                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('settings.company*') ? 'bg-gray-50 font-medium' : '' }}">
                        <span>🏢</span> Company Settings
                    </a>
                    @php $canNRS = $currentTenant->planAllows('firs'); @endphp
                    <a href="{{ $canNRS ? route('settings.firs') : route('billing').'?upgrade_feature=firs' }}"
                       class="flex items-center gap-3 px-4 py-2.5 text-sm {{ $canNRS ? 'text-gray-700 hover:bg-gray-50' : 'text-gray-400 opacity-70 hover:bg-gray-50' }}">
                        <span>⬆</span>
                        <span class="flex-1">NRS e-Invoicing</span>
                        @unless($canNRS)<span class="text-xs">🔒</span>@endunless
                    </a>
                    @endif
                    <div class="border-t border-gray-100 mx-4"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                            <span>🚪</span> Sign Out
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Desktop sidebar ───────────────────────────────────────────────────── --}}
    <aside class="hidden md:fixed md:inset-y-0 md:flex md:w-60 md:flex-col">
        <div class="flex flex-grow flex-col overflow-hidden bg-naija-green pt-4 pb-0">

            {{-- Logo --}}
            <div class="flex flex-shrink-0 items-center px-4 mb-1">
                <span class="text-lg font-bold text-white tracking-tight">🇳🇬 AccountTaxNG</span>
            </div>

            {{-- Tenant badge --}}
            <div class="px-4 pb-3 flex-shrink-0">
                <p class="text-green-200 text-xs truncate font-medium">{{ $currentTenant->name ?? '' }}</p>
                <span class="inline-flex items-center rounded-full bg-green-700/80 px-2 py-0.5 text-[10px] text-green-100 mt-0.5">
                    {{ strtoupper($currentTenant->tax_category ?? 'SME') }} Company
                </span>
            </div>

            <div class="border-t border-green-700/60 flex-shrink-0"></div>

            {{-- Shared nav partial --}}
            @include('layouts.partials._sidebar-nav')

            {{-- User menu (desktop) --}}
            <div class="flex-shrink-0 border-t border-green-700/60 relative"
                 x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open"
                        class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-green-700/60 transition-colors focus:outline-none">
                    <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-900">
                        <span class="text-xs font-semibold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                    </span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-medium text-white truncate">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-green-300 capitalize">{{ auth()->user()->role }}</span>
                    </span>
                    <svg class="h-4 w-4 text-green-300 flex-shrink-0 transition-transform duration-150"
                         :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Dropdown — slides up from trigger --}}
                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="absolute bottom-full mb-1 left-2 right-2 z-50 rounded-lg bg-white shadow-xl border border-gray-200 overflow-hidden">

                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors {{ request()->routeIs('profile.*') ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="text-base">👤</span> My Profile
                    </a>

                    @if(auth()->user()->isAdmin())
                        <div class="border-t border-gray-100 mx-3"></div>
                        <a href="{{ route('settings.company') }}"
                           class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors {{ request()->routeIs('settings.company*') ? 'bg-gray-50 font-medium' : '' }}">
                            <span class="text-base">🏢</span>
                            <span class="flex-1">Company Settings</span>
                            <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium">Admin</span>
                        </a>

                        @php
                            $firsActive  = \App\Models\TenantFirsCredential::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->exists();
                            $canNRSDesk  = $currentTenant->planAllows('firs');
                        @endphp
                        <a href="{{ $canNRSDesk ? route('settings.firs') : route('billing').'?upgrade_feature=firs' }}"
                           class="flex items-center gap-3 px-4 py-3 text-sm {{ $canNRSDesk ? 'text-gray-700 hover:bg-gray-50' : 'text-gray-400 hover:bg-gray-50 opacity-70' }} transition-colors {{ request()->routeIs('settings.firs*') ? 'bg-gray-50 font-medium' : '' }}">
                            <span class="text-base">⬆</span>
                            <div class="flex-1 min-w-0">
                                <span class="block">NRS e-Invoicing</span>
                                @if($canNRSDesk)
                                <span class="block text-xs {{ $firsActive ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $firsActive ? '● Configured' : '○ Not configured' }}
                                </span>
                                @else
                                <span class="block text-xs text-amber-500">Upgrade required</span>
                                @endif
                            </div>
                            @if($canNRSDesk)
                            <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium">Admin</span>
                            @else
                            <span class="text-xs">🔒</span>
                            @endif
                        </a>
                    @endif

                    <div class="border-t border-gray-100 mx-3"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <span class="text-base">🚪</span> Sign Out
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </aside>

    {{-- ── Main content ───────────────────────────────────────────────────────── --}}
    <div class="flex flex-1 flex-col md:pl-60">

        {{-- Top bar --}}
        <div class="sticky top-0 z-10 flex h-14 flex-shrink-0 bg-white shadow">
            <div class="flex flex-1 items-center justify-between px-4 md:px-6">

                {{-- Hamburger (mobile) --}}
                <button type="button" @click="sidebarOpen = true"
                        class="md:hidden inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <h1 class="text-lg font-semibold text-gray-900 truncate">@yield('page-title', 'Dashboard')</h1>

                <div class="flex items-center gap-3">
                    @php $nextVatDue = \App\Services\VatService::VAT_FILING_DAY; @endphp
                    <span class="hidden sm:inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-medium text-yellow-800">
                        VAT: {{ now()->setDay($nextVatDue)->format('M d') }}
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
                             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 top-10 z-50 w-56 rounded-lg bg-white shadow-xl border border-gray-200 overflow-hidden">

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

                            @if(auth()->user()->isAdmin())
                                <div class="border-t border-gray-100 mx-3"></div>
                                <a href="{{ route('settings.company') }}"
                                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('settings.company*') ? 'bg-gray-50 font-medium' : '' }}">
                                    <span>🏢</span>
                                    <span class="flex-1">Company Settings</span>
                                    <span class="text-xs bg-gray-100 text-gray-500 px-1 rounded">Admin</span>
                                </a>

                                @php $canFirsTopbar = $currentTenant->planAllows('firs'); @endphp
                                <a href="{{ $canFirsTopbar ? route('settings.firs') : route('billing').'?upgrade_feature=firs' }}"
                                   class="flex items-center gap-3 px-4 py-2.5 text-sm {{ $canFirsTopbar ? 'text-gray-700 hover:bg-gray-50' : 'text-gray-400 hover:bg-gray-50 opacity-70' }}">
                                    <span>⬆</span>
                                    <span class="flex-1">NRS e-Invoicing</span>
                                    @if($canFirsTopbar)<span class="text-xs bg-gray-100 text-gray-500 px-1 rounded">Admin</span>
                                    @else<span class="text-xs">🔒</span>@endif
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
                </div>
            </div>
        </div>

        {{-- ── Banners ─────────────────────────────────────────────────────────── --}}

        @if(session()->has('superadmin_id'))
        <div class="bg-orange-500 text-white text-sm font-medium px-4 py-2.5 flex items-center justify-between flex-wrap gap-2">
            <span>⚠️ SuperAdmin mode — impersonating <strong>{{ $currentTenant->name }}</strong></span>
            <form method="POST" action="{{ route('superadmin.exit-impersonate') }}">
                @csrf
                <button type="submit" class="bg-white text-orange-700 text-xs font-semibold px-3 py-1 rounded hover:bg-orange-50">
                    Exit Impersonation
                </button>
            </form>
        </div>
        @endif

        @if(isset($currentTenant))
            @if($currentTenant->isInGracePeriod())
            @php $graceDays = $currentTenant->graceDaysLeft(); @endphp
            <div class="bg-orange-500 text-white text-sm font-medium px-4 py-2.5 flex items-center justify-between flex-wrap gap-2">
                <span>⚠️ Your subscription expired — <strong>{{ $graceDays }} {{ $graceDays === 1 ? 'day' : 'days' }}</strong> of grace period remaining.</span>
                <a href="{{ route('billing') }}" class="bg-white text-orange-700 text-xs font-semibold px-3 py-1 rounded hover:bg-orange-50 whitespace-nowrap">Renew Now</a>
            </div>
            @elseif($currentTenant->trialExpired())
            <div class="bg-red-600 text-white text-sm font-medium px-4 py-2.5 flex items-center justify-between flex-wrap gap-2">
                <span>🔒 Your free trial has ended — you've been moved to the Free plan. Upgrade to restore full access.</span>
                <a href="{{ route('billing') }}" class="bg-white text-red-700 text-xs font-semibold px-3 py-1 rounded hover:bg-red-50 whitespace-nowrap">Upgrade Now</a>
            </div>
            @elseif($currentTenant->isOnTrial())
            @php $trialDaysLeft = (int) now()->diffInDays($currentTenant->trial_ends_at); @endphp
            @if($trialDaysLeft <= 3)
            <div class="bg-amber-500 text-white text-sm font-medium px-4 py-2.5 flex items-center justify-between flex-wrap gap-2">
                <span>⏰ Trial ends in <strong>{{ $trialDaysLeft }} {{ $trialDaysLeft === 1 ? 'day' : 'days' }}</strong> ({{ $currentTenant->trial_ends_at->format('d M Y') }}). Upgrade to keep access.</span>
                <a href="{{ route('billing') }}" class="bg-white text-amber-700 text-xs font-semibold px-3 py-1 rounded hover:bg-amber-50 whitespace-nowrap">Upgrade Now</a>
            </div>
            @else
            <div class="bg-blue-600 text-white text-sm font-medium px-4 py-2.5 flex items-center justify-between flex-wrap gap-2">
                <span>🎁 Free trial — <strong>{{ $trialDaysLeft }} days</strong> remaining (ends {{ $currentTenant->trial_ends_at->format('d M Y') }})</span>
                <a href="{{ route('billing') }}" class="bg-white text-blue-700 text-xs font-semibold px-3 py-1 rounded hover:bg-blue-50 whitespace-nowrap">View Plans</a>
            </div>
            @endif
            @endif
        @endif

        {{-- ── Flash messages ──────────────────────────────────────────────────── --}}
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

        {{-- ── Page content ─────────────────────────────────────────────────────── --}}
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
