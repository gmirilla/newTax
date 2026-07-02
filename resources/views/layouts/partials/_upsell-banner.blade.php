<div x-data="{ show: true }" x-show="show"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 max-h-16"
     x-transition:leave-end="opacity-0 max-h-0"
     class="overflow-hidden">
    <div class="bg-indigo-700 text-white text-sm font-medium px-4 py-2.5 flex items-center justify-between flex-wrap gap-2">
        <span class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span>
                You're on the <strong>Free plan</strong> — upgrade to unlock
                payroll, inventory management, FIRS e-invoicing, advanced reports and more.
            </span>
        </span>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('billing') }}"
               class="bg-white text-indigo-700 text-xs font-semibold px-3 py-1 rounded hover:bg-indigo-50 whitespace-nowrap">
                See Plans
            </a>
            <button title="Dismiss for today"
                    @click="show = false; fetch('{{ route('upsell.dismiss') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    })"
                    class="opacity-70 hover:opacity-100 transition-opacity ml-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
</div>
