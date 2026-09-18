{{--
    Flash-message toast for the public storefront.
    Pass ['anchored' => true] to render it inline (e.g. right under a button)
    instead of as a fixed overlay pinned to the top of the viewport.
--}}
@php $anchored = $anchored ?? false; @endphp
@if(session('success') || session('error') || $errors->any())
<div @class(['fixed top-4 inset-x-0 z-50 flex justify-center px-4' => !$anchored, 'mt-3' => $anchored])
     @if(!$anchored) x-cloak @endif>
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @class([
             'max-w-sm w-full sm:w-auto flex items-start gap-2.5 rounded-2xl shadow-lg px-4 py-3 text-sm font-medium',
             session('success') ? 'bg-green-600 text-white' : 'bg-red-600 text-white',
         ])>
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
