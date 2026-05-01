@extends('superadmin.layout')

@section('page-title', 'New Plan')

@section('content')
<div class="space-y-4">
    <a href="{{ route('superadmin.plans.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Plans</a>

    <form method="POST" action="{{ route('superadmin.plans.store') }}">
        @csrf
        @php $plan = new \App\Models\Plan(); @endphp
        @include('superadmin.plans._form')
    </form>
</div>
@endsection
