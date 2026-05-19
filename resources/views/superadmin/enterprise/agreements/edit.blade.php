@extends('superadmin.layout')

@section('page-title', 'Edit Agreement — ' . $tenant->name)

@section('content')
<div class="space-y-6">
    <div>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('superadmin.companies.show', $tenant) }}" class="hover:underline">{{ $tenant->name }}</a>
            <span>/</span>
            <a href="{{ route('superadmin.enterprises.agreements.index', $tenant) }}" class="hover:underline">Agreements</a>
            <span>/</span>
            <span>Edit</span>
        </div>
        <h1 class="text-xl font-bold text-gray-900">Edit Agreement</h1>
    </div>

    <form method="POST" action="{{ route('superadmin.enterprises.agreements.update', [$tenant, $agreement]) }}">
        @csrf
        @method('PUT')
        @include('superadmin.enterprise.agreements._form')
    </form>
</div>
@endsection
