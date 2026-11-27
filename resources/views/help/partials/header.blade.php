{{-- Shared help-topic header: breadcrumb, title, subtitle, optional "who is this for" box. --}}
<div class="flex items-center gap-2 text-sm text-gray-500">
    <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
</div>

<div>
    <h1 class="text-xl font-bold text-gray-900">{{ $meta['title'] }}</h1>
    <p class="text-sm text-gray-500 mt-1">{{ $subtitle ?? $meta['description'] }}</p>
</div>

@isset($why)
<div class="rounded-lg bg-green-50 border border-green-200 p-4">
    <p class="text-sm font-semibold text-green-800">Why you'd use this</p>
    <p class="text-sm text-green-900/80 mt-1 leading-relaxed">{{ $why }}</p>
    @isset($who)
    <p class="text-xs text-green-800 mt-2"><strong>Available to:</strong> {{ $who }}</p>
    @endisset
</div>
@endisset
