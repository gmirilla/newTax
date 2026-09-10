<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $tenant->name . ' — Shop')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

@php
    $accent     = $tenant->accentColor();
    $accentText = $tenant->accentTextColor();
    $cartCount  = collect(session('storefront_cart_' . $tenant->id, []))->count();
@endphp

<header style="background-color: {{ $accent }}; color: {{ $accentText }};">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="{{ route('storefront.index', $tenant->slug) }}" class="flex items-center gap-3">
            @if($tenant->logo)
                <img src="{{ Storage::url($tenant->logo) }}" alt="{{ $tenant->name }}" class="h-9 bg-white rounded px-1.5 py-1 object-contain">
            @else
                <span class="text-lg font-bold">{{ $tenant->name }}</span>
            @endif
        </a>
        <a href="{{ route('storefront.cart', $tenant->slug) }}" class="flex items-center gap-2 text-sm font-medium">
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

@if(session('success'))
<div class="bg-green-50 border-b border-green-200 text-green-800 text-sm px-4 py-2.5 text-center">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border-b border-red-200 text-red-800 text-sm px-4 py-2.5 text-center">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="bg-red-50 border-b border-red-200 text-red-800 text-sm px-4 py-2.5 text-center">
    @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
</div>
@endif

<main class="flex-1 max-w-5xl w-full mx-auto px-4 py-8">
    @yield('content')
</main>

<footer class="border-t border-gray-200 bg-white">
    <div class="max-w-5xl mx-auto px-4 py-6 text-center text-xs text-gray-400">
        {{ $tenant->name }} @if($tenant->address) · {{ $tenant->address }} @endif
        <br class="sm:hidden">
        <span class="text-gray-300">·</span> Powered by AccountTaxNG
    </div>
</footer>

</body>
</html>
