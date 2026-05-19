@extends('superadmin.layout')

@section('page-title', 'New Enterprise Agreement — ' . $tenant->name)

@section('content')
<div class="space-y-6">
    <div>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('superadmin.companies.show', $tenant) }}" class="hover:underline">{{ $tenant->name }}</a>
            <span>/</span>
            <a href="{{ route('superadmin.enterprises.agreements.index', $tenant) }}" class="hover:underline">Agreements</a>
            <span>/</span>
            <span>New</span>
        </div>
        <h1 class="text-xl font-bold text-gray-900">New Enterprise Agreement</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Creating a new agreement will terminate any currently active agreement for {{ $tenant->name }}.
        </p>
    </div>

    <form method="POST" action="{{ route('superadmin.enterprises.agreements.store', $tenant) }}">
        @csrf
        @include('superadmin.enterprise.agreements._form')
    </form>
</div>
@endsection
