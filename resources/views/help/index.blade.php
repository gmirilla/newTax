@extends('layouts.app')
@section('page-title', 'Help Center')

@section('content')
<div class="max-w-4xl space-y-6">

    <div>
        <h1 class="text-lg font-semibold text-gray-900">Help Center</h1>
        <p class="text-sm text-gray-500 mt-0.5">Step-by-step guides for every feature in NaijaBooks.</p>
    </div>

    @php
        $categories = [
            'basics'     => ['label' => 'Getting Started',  'color' => 'green'],
            'sales'      => ['label' => 'Sales',            'color' => 'blue'],
            'finance'    => ['label' => 'Finance',          'color' => 'purple'],
            'operations' => ['label' => 'Operations',       'color' => 'orange'],
            'settings'   => ['label' => 'Settings',         'color' => 'gray'],
        ];

        $grouped = collect($topics)->groupBy(fn($t) => $t['category']);

        $icons = [
            'rocket'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.82m5.84-2.56a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.63 3.28a14.96 14.96 0 01.16 4.25M12 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
            'document'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/>',
            'clipboard'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
            'users'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
            'book'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
            'bank'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>',
            'chart'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
            'box'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
            'cash'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
            'team'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
            'credit-card' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
        ];

        $catColors = [
            'green'  => ['bg' => 'bg-green-50',  'border' => 'border-green-200', 'icon' => 'text-green-600',  'label' => 'bg-green-100 text-green-700'],
            'blue'   => ['bg' => 'bg-blue-50',   'border' => 'border-blue-200',  'icon' => 'text-blue-600',   'label' => 'bg-blue-100 text-blue-700'],
            'purple' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200','icon' => 'text-purple-600', 'label' => 'bg-purple-100 text-purple-700'],
            'orange' => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200','icon' => 'text-orange-600', 'label' => 'bg-orange-100 text-orange-700'],
            'gray'   => ['bg' => 'bg-gray-50',   'border' => 'border-gray-200',  'icon' => 'text-gray-600',   'label' => 'bg-gray-100 text-gray-700'],
        ];
    @endphp

    @foreach($categories as $catKey => $cat)
        @if($grouped->has($catKey))
        <div>
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">{{ $cat['label'] }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($grouped[$catKey] as $slug => $topic)
                @php $c = $catColors[$cat['color']]; @endphp
                <a href="{{ route('help.show', $slug) }}"
                   class="flex items-start gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-green-400 hover:shadow-sm transition-all group">
                    <div class="mt-0.5 w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-md {{ $c['bg'] }} {{ $c['border'] }} border">
                        <svg class="w-4 h-4 {{ $c['icon'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            {!! $icons[$topic['icon']] !!}
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-semibold text-gray-800 group-hover:text-green-700">{{ $topic['title'] }}</span>
                            @if(isset($topic['plan']))
                                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded {{ $c['label'] }} uppercase tracking-wide">Pro</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ $topic['description'] }}</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach

    <div class="rounded-lg bg-green-50 border border-green-200 p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-green-800">Need more help?</p>
            <p class="text-xs text-green-700 mt-0.5">Contact your account manager or send an email to <span class="font-medium">support@naijabooks.ng</span></p>
        </div>
    </div>

</div>
@endsection
