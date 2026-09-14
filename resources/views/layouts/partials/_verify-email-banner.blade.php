<div x-data="{ show: true }" x-show="show"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 max-h-16"
     x-transition:leave-end="opacity-0 max-h-0"
     class="overflow-hidden">
    <div class="bg-blue-600 text-white text-sm font-medium px-4 py-2.5 flex items-center justify-between flex-wrap gap-2">
        <span class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span>
                Verify your email to secure your account — check <strong>{{ auth()->user()->email }}</strong> for a link.
            </span>
        </span>
        <div class="flex items-center gap-2 shrink-0">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                        class="bg-white text-blue-700 text-xs font-semibold px-3 py-1 rounded hover:bg-blue-50 whitespace-nowrap">
                    Resend Email
                </button>
            </form>
            <button title="Dismiss for today"
                    @click="show = false; fetch('{{ route('verification.banner.dismiss') }}', {
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
