<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenant->name }} — Store Unavailable</title>
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body{font-family:'Inter',ui-sans-serif,system-ui,sans-serif;}
        .font-display{font-family:'Fraunces',ui-serif,Georgia,serif;}
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">

<div class="max-w-md w-full text-center">

    @if($tenant->logo)
        <img src="{{ Storage::url($tenant->logo) }}" alt="{{ $tenant->name }}" class="h-12 mx-auto mb-6 object-contain">
    @else
        <div class="font-display text-xl font-semibold text-gray-800 mb-6">{{ $tenant->name }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-8 py-10">
        <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-amber-50 flex items-center justify-center">
            <svg class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
        </div>
        <h1 class="font-display text-lg font-semibold text-gray-900">Store Temporarily Unavailable</h1>
        <p class="mt-2 text-sm text-gray-500 leading-relaxed">
            {{ $tenant->name }}'s online store isn't available right now. Please check back later.
        </p>
        <p class="mt-4 text-sm text-gray-400 leading-relaxed">
            Checking on an existing order? Please contact the seller directly
            @if($tenant->email || $tenant->phone)
                — {{ $tenant->email }}{{ $tenant->email && $tenant->phone ? ' or ' : '' }}{{ $tenant->phone }}.
            @else
                .
            @endif
        </p>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        Powered by <span class="font-medium text-gray-500">AccountTaxNG</span>
    </p>

</div>
</body>
</html>
