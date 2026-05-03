<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Admin') — NaijaBooks Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-100 min-h-screen">

{{-- Impersonation banner --}}
@if(session('info'))
<div class="bg-yellow-500 text-yellow-900 text-sm px-4 py-2 flex justify-between items-center">
    <span>⚠ {{ session('info') }}</span>
    <form method="POST" action="{{ route('superadmin.exit-impersonate') }}">
        @csrf
        <button type="submit" class="underline font-semibold">Exit Impersonation</button>
    </form>
</div>
@endif

<div class="flex h-screen overflow-hidden">
    {{-- Sidebar --}}
    <aside class="w-60 bg-gray-900 text-white flex flex-col flex-shrink-0">
        <div class="px-5 py-5 border-b border-gray-700">
            <p class="text-xs uppercase tracking-widest text-gray-400 font-medium">Platform Admin</p>
            <h1 class="text-lg font-bold text-white mt-0.5">NaijaBooks</h1>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            @php
                $navItem = fn(string $route, string $label, string $icon) =>
                    '<a href="' . route($route) . '"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors ' .
                       (request()->routeIs($route . '*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') .
                       '"><span>' . $icon . '</span>' . $label . '</a>';
            @endphp
            {!! $navItem('superadmin.dashboard', 'Dashboard', '🏠') !!}
            {!! $navItem('superadmin.companies', 'Companies', '🏢') !!}
            {!! $navItem('superadmin.plans.index', 'Plans', '💳') !!}
            {!! $navItem('superadmin.transactions', 'Transactions', '💰') !!}

            <div class="pt-4 mt-4 border-t border-gray-700">
                <p class="px-3 text-xs uppercase tracking-widest text-gray-500 mb-2">Account</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
                        <span>🚪</span> Sign Out
                    </button>
                </form>
            </div>
        </nav>

        <div class="px-4 py-3 border-t border-gray-700 text-xs text-gray-500">
            Signed in as <span class="text-gray-300 font-medium">{{ auth()->user()->name }}</span>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b px-6 py-3 flex items-center justify-between flex-shrink-0">
            <h2 class="text-base font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
            <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded font-medium">SUPERADMIN</span>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                {{ session('error') }}
            </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
