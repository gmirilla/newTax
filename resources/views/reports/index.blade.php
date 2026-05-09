@extends('layouts.app')

@section('page-title', 'Reports')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

    <a href="{{ route('reports.pl') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow group">
        <div class="text-3xl mb-3">📈</div>
        <h3 class="font-semibold text-gray-900 group-hover:text-green-700">Profit & Loss</h3>
        <p class="text-sm text-gray-500 mt-1">Revenue vs expenses, net profit/loss for any period</p>
    </a>

    <a href="{{ route('reports.bs') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow group">
        <div class="text-3xl mb-3">📉</div>
        <h3 class="font-semibold text-gray-900 group-hover:text-green-700">Balance Sheet</h3>
        <p class="text-sm text-gray-500 mt-1">Assets, liabilities, and equity at a point in time</p>
    </a>

    <a href="{{ route('reports.tb') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow group">
        <div class="text-3xl mb-3">⚖️</div>
        <h3 class="font-semibold text-gray-900 group-hover:text-green-700">Trial Balance</h3>
        <p class="text-sm text-gray-500 mt-1">All account debit/credit balances — must balance</p>
    </a>

    <a href="{{ route('reports.ledger') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow group">
        <div class="text-3xl mb-3">📒</div>
        <h3 class="font-semibold text-gray-900 group-hover:text-green-700">General Ledger</h3>
        <p class="text-sm text-gray-500 mt-1">All journal entries per account with running balance — export Excel or PDF</p>
    </a>

    <a href="{{ route('reports.vat') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow group border-t-4 border-blue-500">
        <div class="text-3xl mb-3">🏛️</div>
        <h3 class="font-semibold text-gray-900 group-hover:text-blue-700">VAT Report</h3>
        <p class="text-sm text-gray-500 mt-1">Monthly output/input VAT for NRS filing (7.5%)</p>
    </a>

    <a href="{{ route('reports.cit') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow group border-t-4 border-orange-500">
        <div class="text-3xl mb-3">🏢</div>
        <h3 class="font-semibold text-gray-900 group-hover:text-orange-700">CIT Report</h3>
        <p class="text-sm text-gray-500 mt-1">Annual Company Income Tax computation (0/20/30%)</p>
    </a>

    <a href="{{ route('reports.tax-summary') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow group border-t-4 border-green-500">
        <div class="text-3xl mb-3">📑</div>
        <h3 class="font-semibold text-gray-900 group-hover:text-green-700">Tax Summary</h3>
        <p class="text-sm text-gray-500 mt-1">Full compliance overview: VAT, WHT, CIT, PAYE</p>
    </a>

</div>
@endsection
