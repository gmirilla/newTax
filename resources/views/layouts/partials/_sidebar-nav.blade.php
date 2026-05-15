{{--
    Shared sidebar navigation — included by both the mobile panel and the desktop aside.
    Variables expected from parent scope (computed once in app.blade.php):
      $navRole, $canPayroll, $canInventory, $canInventoryReports, $canManufacturing,
      $navAlertCount, $navPendingRestocks,
      $navInSales, $navInTax, $navInPayroll,
      $navInInventory, $navInManufacturing, $navInReports, $navInSettings
--}}
@php
    // Reusable class fragments
    $lnk  = 'flex items-center gap-2.5 px-3 py-1.5 text-sm font-medium rounded-md transition-colors duration-150';
    $on   = 'bg-green-900 text-white';
    $off  = 'text-green-100 hover:bg-green-700 hover:text-white';
    $dim  = 'text-green-300/60 hover:bg-green-800/50';

    // Section header button — triggers collapse
    $grp  = 'flex items-center gap-2.5 w-full px-3 py-2 text-xs font-bold tracking-widest uppercase '
          . 'text-green-300 hover:bg-green-800/60 hover:text-green-100 rounded-md transition-colors duration-150 select-none';

    // Child link — indented inside a collapsible group
    $sub  = 'flex items-center gap-2.5 pl-8 pr-2 py-1.5 text-sm font-medium rounded-md transition-colors duration-150';

    $chv  = 'ml-auto w-3.5 h-3.5 text-green-400 flex-shrink-0 transition-transform duration-200';

    // Helper: badge pill
    $pill = fn(int $n, string $color = 'red') =>
        $n > 0
        ? '<span class="ml-auto inline-flex items-center justify-center min-w-[1.15rem] h-[1.15rem] px-1 rounded-full text-[10px] font-bold '
          . ($color === 'yellow' ? 'bg-yellow-400 text-yellow-900' : 'bg-red-500 text-white') . '">'
          . ($n > 99 ? '99+' : $n) . '</span>'
        : '';
@endphp

<nav class="mt-3 flex-1 flex flex-col gap-0.5 px-2 pb-4 overflow-y-auto">

    {{-- ── Dashboard ───────────────────────────────────────────────────────── --}}
    <a href="{{ route('dashboard') }}"
       class="{{ $lnk }} {{ request()->routeIs('dashboard', 'staff.dashboard') ? $on : $off }}">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Dashboard
    </a>

    @if(in_array($navRole, ['admin', 'accountant']))

    {{-- ── Sales & Finance ────────────────────────────────────────────────── --}}
    <div x-data="{ open: @js($navInSales) }">
        <button type="button" @click="open = !open" class="{{ $grp }}">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="flex-1 text-left">Sales &amp; Finance</span>
            <svg class="{{ $chv }}" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse class="mt-0.5 space-y-0.5">
            <a href="{{ route('quotes.index') }}"
               class="{{ $sub }} {{ request()->routeIs('quotes.*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414A1 1 0 0120 8.414V17a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                Quotes / Proforma
            </a>
            <a href="{{ route('invoices.index') }}"
               class="{{ $sub }} {{ request()->routeIs('invoices.*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                Invoices
            </a>
            <a href="{{ route('transactions.index') }}"
               class="{{ $sub }} {{ request()->routeIs('transactions.*') && !request()->routeIs('transactions.expenses*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                Transactions
            </a>
            <a href="{{ route('transactions.expenses') }}"
               class="{{ $sub }} {{ request()->routeIs('transactions.expenses*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Expenses
            </a>
        </div>
    </div>

    {{-- ── Tax & Compliance ───────────────────────────────────────────────── --}}
    <div x-data="{ open: @js($navInTax) }">
        <button type="button" @click="open = !open" class="{{ $grp }}">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
            </svg>
            <span class="flex-1 text-left">Tax &amp; Compliance</span>
            <svg class="{{ $chv }}" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse class="mt-0.5 space-y-0.5">
            <a href="{{ route('tax.dashboard') }}"
               class="{{ $sub }} {{ request()->routeIs('tax.dashboard') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/></svg>
                Overview
            </a>
            <a href="{{ route('tax.vat.index') }}"
               class="{{ $sub }} {{ request()->routeIs('tax.vat.*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                VAT Returns
            </a>
            <a href="{{ route('tax.wht.index') }}"
               class="{{ $sub }} {{ request()->routeIs('tax.wht.*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                WHT Records
            </a>
            <a href="{{ route('tax.cit.index') }}"
               class="{{ $sub }} {{ request()->routeIs('tax.cit.*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                CIT (Company Tax)
            </a>
        </div>
    </div>

    {{-- ── HR & Payroll ────────────────────────────────────────────────────── --}}
    <div x-data="{ open: @js($navInPayroll) }">
        <button type="button" @click="open = !open" class="{{ $grp }}">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="flex-1 text-left">HR &amp; Payroll</span>
            @unless($canPayroll)
                <svg class="w-3 h-3 text-green-400/70 flex-shrink-0 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            @endunless
            <svg class="{{ $chv }}" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse class="mt-0.5 space-y-0.5">
            <a href="{{ $canPayroll ? route('payroll.index') : route('billing').'?upgrade_feature=payroll' }}"
               class="{{ $sub }} {{ $canPayroll ? (request()->routeIs('payroll.index') ? $on : $off) : $dim }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                Payroll &amp; PAYE
                @unless($canPayroll)<svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@endunless
            </a>
            <a href="{{ $canPayroll ? route('payroll.employees') : route('billing').'?upgrade_feature=payroll' }}"
               class="{{ $sub }} {{ $canPayroll ? (request()->routeIs('payroll.employees*') ? $on : $off) : $dim }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Employees
                @unless($canPayroll)<svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@endunless
            </a>
        </div>
    </div>

    {{-- ── Inventory ───────────────────────────────────────────────────────── --}}
    @php
        $invBadgeTotal = $navAlertCount + $navPendingRestocks;
    @endphp
    <div x-data="{ open: @js($navInInventory) }">
        <button type="button" @click="open = !open" class="{{ $grp }}">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="flex-1 text-left">Inventory</span>
            @if(! $canInventory)
                <svg class="w-3 h-3 text-green-400/70 flex-shrink-0 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            @elseif($invBadgeTotal > 0)
                <span class="mr-1 inline-flex items-center justify-center min-w-[1.15rem] h-[1.15rem] px-1 rounded-full text-[10px] font-bold bg-red-500 text-white">
                    {{ $invBadgeTotal > 99 ? '99+' : $invBadgeTotal }}
                </span>
            @endif
            <svg class="{{ $chv }}" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse class="mt-0.5 space-y-0.5">
            {{-- Items & Stock --}}
            <a href="{{ $canInventory ? route('inventory.items.index') : route('billing').'?upgrade_feature=inventory' }}"
               class="{{ $sub }} {{ $canInventory ? (request()->routeIs('inventory.items.*', 'inventory.import.*') ? $on : $off) : $dim }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                Items &amp; Stock
                @if($canInventory && $navAlertCount > 0)
                    <span class="ml-auto inline-flex items-center justify-center min-w-[1.15rem] h-[1.15rem] px-1 rounded-full text-[10px] font-bold bg-red-500 text-white">{{ $navAlertCount > 99 ? '99+' : $navAlertCount }}</span>
                @elseif(! $canInventory)
                    <svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                @endif
            </a>
            {{-- Sales Orders --}}
            <a href="{{ $canInventory ? route('inventory.sales.index') : route('billing').'?upgrade_feature=inventory' }}"
               class="{{ $sub }} {{ $canInventory ? (request()->routeIs('inventory.sales.*') ? $on : $off) : $dim }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Sales Orders
            </a>
            {{-- Restock --}}
            <a href="{{ $canInventory ? route('inventory.restock.index') : route('billing').'?upgrade_feature=inventory' }}"
               class="{{ $sub }} {{ $canInventory ? (request()->routeIs('inventory.restock.*') ? $on : $off) : $dim }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Restock Requests
                @if($canInventory && $navPendingRestocks > 0)
                    <span class="ml-auto inline-flex items-center justify-center min-w-[1.15rem] h-[1.15rem] px-1 rounded-full text-[10px] font-bold bg-yellow-400 text-yellow-900">{{ $navPendingRestocks > 99 ? '99+' : $navPendingRestocks }}</span>
                @endif
            </a>
            {{-- Catalogue management sub-group --}}
            <div class="pt-1 mt-1 border-t border-green-700/50 space-y-0.5">
                <a href="{{ $canInventory ? route('inventory.categories.index') : route('billing').'?upgrade_feature=inventory' }}"
                   class="{{ $sub }} text-[12px] {{ $canInventory ? (request()->routeIs('inventory.categories.*') ? $on : $off) : $dim }}">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Categories
                </a>
                <a href="{{ $canInventory ? route('inventory.units.index') : route('billing').'?upgrade_feature=inventory' }}"
                   class="{{ $sub }} text-[12px] {{ $canInventory ? (request()->routeIs('inventory.units.*') ? $on : $off) : $dim }}">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                    Units of Measure
                </a>
            </div>
        </div>
    </div>

    {{-- ── Manufacturing ─────────────────────────────────────────────────── --}}
    <div x-data="{ open: @js($navInManufacturing) }">
        <button type="button" @click="open = !open" class="{{ $grp }}">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="flex-1 text-left">Manufacturing</span>
            @unless($canManufacturing)
                <svg class="w-3 h-3 text-green-400/70 flex-shrink-0 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            @endunless
            <svg class="{{ $chv }}" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse class="mt-0.5 space-y-0.5">
            <a href="{{ $canManufacturing ? route('manufacturing.boms.index') : route('billing').'?upgrade_feature=manufacturing' }}"
               class="{{ $sub }} {{ $canManufacturing ? (request()->routeIs('manufacturing.boms.*') ? $on : $off) : $dim }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Bills of Materials
                @unless($canManufacturing)<svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@endunless
            </a>
            <a href="{{ $canManufacturing ? route('manufacturing.production.index') : route('billing').'?upgrade_feature=manufacturing' }}"
               class="{{ $sub }} {{ $canManufacturing ? (request()->routeIs('manufacturing.production.*') ? $on : $off) : $dim }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                Production Orders
                @unless($canManufacturing)<svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@endunless
            </a>
        </div>
    </div>

    {{-- ── Reports ─────────────────────────────────────────────────────────── --}}
    <div x-data="{ open: @js($navInReports) }">
        <button type="button" @click="open = !open" class="{{ $grp }}">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span class="flex-1 text-left">Reports</span>
            <svg class="{{ $chv }}" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse class="mt-0.5 space-y-0.5">
            {{-- Financial reports --}}
            <a href="{{ route('reports.pl') }}"
               class="{{ $sub }} {{ request()->routeIs('reports.pl*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Profit &amp; Loss
            </a>
            <a href="{{ route('reports.bs') }}"
               class="{{ $sub }} {{ request()->routeIs('reports.bs*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Balance Sheet
            </a>
            <a href="{{ route('reports.tb') }}"
               class="{{ $sub }} {{ request()->routeIs('reports.tb*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Trial Balance
            </a>
            <a href="{{ route('reports.ledger') }}"
               class="{{ $sub }} {{ request()->routeIs('reports.ledger*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                General Ledger
            </a>
            <a href="{{ route('reports.tax-summary') }}"
               class="{{ $sub }} {{ request()->routeIs('reports.tax-summary*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/></svg>
                Tax Summary
            </a>
            {{-- Inventory reports --}}
            <div class="pt-1 mt-1 border-t border-green-700/50">
                <p class="pl-8 py-1 text-[10px] font-bold tracking-widest uppercase text-green-400/70">Inventory</p>
                <div class="space-y-0.5">
                    <a href="{{ $canInventoryReports ? route('inventory.reports.stock-valuation') : route('billing').'?upgrade_feature=inventory_reports' }}"
                       class="{{ $sub }} text-[12px] {{ $canInventoryReports ? (request()->routeIs('inventory.reports.stock-valuation*') ? $on : $off) : $dim }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Stock Valuation
                        @unless($canInventoryReports)<svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@endunless
                    </a>
                    <a href="{{ $canInventoryReports ? route('inventory.reports.low-stock') : route('billing').'?upgrade_feature=inventory_reports' }}"
                       class="{{ $sub }} text-[12px] {{ $canInventoryReports ? (request()->routeIs('inventory.reports.low-stock*') ? $on : $off) : $dim }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Low Stock
                        @unless($canInventoryReports)<svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@endunless
                    </a>
                    <a href="{{ $canInventoryReports ? route('inventory.reports.movements') : route('billing').'?upgrade_feature=inventory_reports' }}"
                       class="{{ $sub }} text-[12px] {{ $canInventoryReports ? (request()->routeIs('inventory.reports.movements*') ? $on : $off) : $dim }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                        Stock Movements
                        @unless($canInventoryReports)<svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@endunless
                    </a>
                    <a href="{{ $canInventoryReports ? route('inventory.reports.sales-by-item') : route('billing').'?upgrade_feature=inventory_reports' }}"
                       class="{{ $sub }} text-[12px] {{ $canInventoryReports ? (request()->routeIs('inventory.reports.sales-by-item*') ? $on : $off) : $dim }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                        Sales by Item
                        @unless($canInventoryReports)<svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@endunless
                    </a>
                    <a href="{{ $canInventoryReports ? route('inventory.reports.sales-by-period') : route('billing').'?upgrade_feature=inventory_reports' }}"
                       class="{{ $sub }} text-[12px] {{ $canInventoryReports ? (request()->routeIs('inventory.reports.sales-by-period*') ? $on : $off) : $dim }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Sales by Period
                        @unless($canInventoryReports)<svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@endunless
                    </a>
                    <a href="{{ $canInventoryReports ? route('inventory.reports.restock-history') : route('billing').'?upgrade_feature=inventory_reports' }}"
                       class="{{ $sub }} text-[12px] {{ $canInventoryReports ? (request()->routeIs('inventory.reports.restock-history*') ? $on : $off) : $dim }}">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Restock History
                        @unless($canInventoryReports)<svg class="ml-auto w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@endunless
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Activity Log ──────────────────────────────────────────────────── --}}
    <a href="{{ route('activity.index') }}"
       class="{{ $lnk }} {{ request()->routeIs('activity.*') ? $on : $off }}">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>
        Activity Log
    </a>

    @endif {{-- end admin/accountant --}}

    {{-- ── Settings (admin only) ──────────────────────────────────────────── --}}
    @if($navRole === 'admin')
    <div x-data="{ open: @js($navInSettings) }">
        <button type="button" @click="open = !open" class="{{ $grp }}">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="flex-1 text-left">Settings</span>
            <svg class="{{ $chv }}" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse class="mt-0.5 space-y-0.5">
            <a href="{{ route('team.index') }}"
               class="{{ $sub }} {{ request()->routeIs('team.*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Team Members
            </a>
            <a href="{{ route('settings.bank-accounts.index') }}"
               class="{{ $sub }} {{ request()->routeIs('settings.bank-accounts*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                Bank Accounts
            </a>
            <a href="{{ route('billing') }}"
               class="{{ $sub }} {{ request()->routeIs('billing*') ? $on : $off }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Billing &amp; Plan
            </a>
        </div>
    </div>
    @endif

</nav>
