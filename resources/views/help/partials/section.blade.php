{{-- Usage: @component('help.partials.section', ['heading' => '...']) html @endcomponent --}}
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
        <h2 class="text-sm font-semibold text-gray-800">{{ $heading }}</h2>
    </div>
    <div class="p-5 space-y-3 text-sm text-gray-700">{{ $slot }}</div>
</div>
