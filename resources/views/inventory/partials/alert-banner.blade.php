@if($unseenAlerts->isNotEmpty())
@php
    $outOfStock = $unseenAlerts->where('type', 'out_of_stock');
    $lowStock   = $unseenAlerts->where('type', 'low_stock');
@endphp
<div x-data="{ open: true }" x-show="open" class="rounded-lg border border-red-200 bg-red-50 p-4">
    <div class="flex items-start gap-3">
        <span class="text-xl shrink-0 mt-0.5">
            {{ $outOfStock->isNotEmpty() ? '🚨' : '⚠️' }}
        </span>

        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-red-800">
                {{ $outOfStock->isNotEmpty() ? 'Stock Alert — Action Required' : 'Low Stock Warning' }}
            </p>

            {{-- Out of stock items --}}
            @if($outOfStock->isNotEmpty())
            <div class="mt-2">
                <p class="text-xs font-medium text-red-700 uppercase tracking-wide mb-1">Out of Stock ({{ $outOfStock->count() }})</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($outOfStock->take(5) as $alert)
                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                        {{ $alert->item->name }}
                        <form method="POST" action="{{ route('inventory.alerts.dismiss', $alert) }}" class="inline">
                            @csrf
                            <button type="submit" class="ml-1 text-red-400 hover:text-red-700 leading-none"
                                    title="Dismiss">×</button>
                        </form>
                    </span>
                    @endforeach
                    @if($outOfStock->count() > 5)
                        <span class="text-xs text-red-600">+{{ $outOfStock->count() - 5 }} more</span>
                    @endif
                </div>
            </div>
            @endif

            {{-- Low stock items --}}
            @if($lowStock->isNotEmpty())
            <div class="mt-2">
                <p class="text-xs font-medium text-yellow-700 uppercase tracking-wide mb-1">Low Stock ({{ $lowStock->count() }})</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($lowStock->take(5) as $alert)
                    <span class="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                        {{ $alert->item->name }}
                        <span class="text-yellow-500">({{ number_format($alert->item->current_stock, 0) }} left)</span>
                        <form method="POST" action="{{ route('inventory.alerts.dismiss', $alert) }}" class="inline">
                            @csrf
                            <button type="submit" class="ml-1 text-yellow-400 hover:text-yellow-700 leading-none"
                                    title="Dismiss">×</button>
                        </form>
                    </span>
                    @endforeach
                    @if($lowStock->count() > 5)
                        <span class="text-xs text-yellow-600">+{{ $lowStock->count() - 5 }} more</span>
                    @endif
                </div>
            </div>
            @endif

            <div class="mt-3 flex items-center gap-3">
                <a href="{{ route('inventory.items.index', ['stock_status' => 'low']) }}"
                   class="text-xs font-medium text-red-700 hover:text-red-900 underline">
                    View all low-stock items →
                </a>
                <form method="POST" action="{{ route('inventory.alerts.dismiss-all') }}">
                    @csrf
                    <button type="submit"
                            class="text-xs text-red-500 hover:text-red-700 underline">
                        Dismiss all alerts
                    </button>
                </form>
            </div>
        </div>

        {{-- Close button (client-side only — hides the banner without dismissing in DB) --}}
        <button @click="open = false"
                class="shrink-0 text-red-400 hover:text-red-600 transition-colors"
                title="Hide banner">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
@endif
