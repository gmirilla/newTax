<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $tenant->name . ' — Shop')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak]{display:none!important}
        body{font-family:'Inter',ui-sans-serif,system-ui,sans-serif;}
        .font-display{font-family:'Fraunces',ui-serif,Georgia,serif;}
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

@php
    $accent     = $tenant->accentColor();
    $accentText = $tenant->accentTextColor();
    $cartCount  = collect(session('storefront_cart_' . $tenant->id, []))->count();
@endphp

<header style="background-color: {{ $accent }}; color: {{ $accentText }};" class="shadow-[0_1px_0_rgba(0,0,0,0.06),0_4px_16px_-4px_rgba(0,0,0,0.12)] relative z-10">
    <div class="max-w-5xl mx-auto px-4 py-5 flex items-center justify-between">
        <a href="{{ route('storefront.index', $tenant->slug) }}" class="flex items-center gap-3">
            @if($tenant->logo)
                <img src="{{ Storage::url($tenant->logo) }}" alt="{{ $tenant->name }}" class="h-9 bg-white rounded px-1.5 py-1 object-contain">
            @else
                <span class="font-display text-xl font-semibold tracking-tight">{{ $tenant->name }}</span>
            @endif
        </a>
        <a href="{{ route('storefront.cart', $tenant->slug) }}" class="flex items-center gap-2 text-sm font-medium opacity-90 hover:opacity-100 transition-opacity">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
            </svg>
            Cart
            @if($cartCount > 0)
                <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-white text-[11px] font-bold" style="color: {{ $accent }};">{{ $cartCount }}</span>
            @endif
        </a>
    </div>
</header>

@if(session('success') || session('error') || $errors->any())
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-3"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed top-4 inset-x-0 z-50 flex justify-center px-4" x-cloak>
    <div class="max-w-sm w-full sm:w-auto flex items-start gap-2.5 rounded-2xl shadow-lg px-4 py-3 text-sm font-medium
                {{ session('success') ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }}">
        @if(session('success'))
        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l6-6.75L18 15M12 21a9 9 0 100-18 9 9 0 000 18z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12.75l2.25 2.25 5.25-5.25" />
        </svg>
        @else
        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12V16.5zm9-4.5a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        @endif
        <div class="flex-1 leading-snug">
            @if(session('success')) {{ session('success') }} @endif
            @if(session('error')) {{ session('error') }} @endif
            @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
        <button @click="show = false" class="opacity-70 hover:opacity-100 leading-none text-lg -mt-0.5">&times;</button>
    </div>
</div>
@endif

<main class="flex-1 max-w-5xl w-full mx-auto px-4 py-10">
    @yield('content')
</main>

<footer class="border-t border-gray-100 bg-white mt-8">
    <div class="max-w-5xl mx-auto px-4 py-8 text-center text-xs text-gray-400">
        {{ $tenant->name }} @if($tenant->address) · {{ $tenant->address }} @endif
        <br class="sm:hidden">
        <span class="text-gray-300">·</span> Powered by AccountTaxNG
    </div>
</footer>

</body>
</html>
