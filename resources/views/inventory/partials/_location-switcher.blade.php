{{--
    Location switcher banner.
    Usage: @include('inventory.partials._location-switcher', ['active' => $active, 'locations' => $locations])
    $active    — current InventoryLocation
    $locations — collection of all active locations for this tenant (optional; fetched if not passed)
--}}
@php
    if (!isset($locations)) {
        $locations = \App\Models\InventoryLocation::where('tenant_id', auth()->user()->tenant_id)
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }
    $multiLocation = $locations->count() > 1;
@endphp

@if($multiLocation)
<div class="flex items-center gap-2 mb-4 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg text-sm"
     x-data="{}">
    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span class="text-amber-700 font-medium flex-1 truncate">{{ $active->name }}</span>
    <form method="POST" action="{{ route('inventory.locations.switch') }}" class="flex items-center gap-1">
        @csrf
        <select name="location_id"
                onchange="this.form.submit()"
                class="text-xs border border-amber-300 rounded px-2 py-1 bg-white text-gray-700 focus:ring-1 focus:ring-amber-400 focus:outline-none">
            @foreach($locations as $loc)
                <option value="{{ $loc->id }}" @selected($loc->id == $active->id)>{{ $loc->name }}</option>
            @endforeach
        </select>
    </form>
</div>
@else
<div class="flex items-center gap-1.5 mb-4 text-xs text-gray-500">
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span>{{ $active->name }}</span>
    <a href="{{ route('inventory.locations.index') }}" class="ml-1 text-green-600 hover:underline">+ Add location</a>
</div>
@endif
