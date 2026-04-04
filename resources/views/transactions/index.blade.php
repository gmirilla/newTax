@extends('layouts.app')

@section('page-title', 'Transactions')

@section('content')
<div class="space-y-6">

    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-base font-semibold">Journal Transactions</h2>
            <div class="flex gap-3">
                <a href="{{ route('transactions.expenses') }}"
                   class="px-4 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">
                    View Expenses
                </a>
                <a href="{{ route('transactions.create') }}"
                   class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    + New Journal Entry
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="px-6 py-3 bg-gray-50 border-b">
            <form method="GET" class="flex flex-wrap gap-3">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search reference or description..."
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 focus:ring-green-500 focus:border-green-500">
                <select name="type" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All Types</option>
                    @foreach(['sale','purchase','expense','income','payment','receipt','journal','tax_payment','payroll'] as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_',' ', $type)) }}
                        </option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">Filter</button>
                @if(request()->hasAny(['search','type','date_from','date_to']))
                    <a href="{{ route('transactions.index') }}" class="px-3 py-1.5 text-sm text-gray-500 hover:underline">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (₦)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">By</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-green-700">
                            {{ $tx->reference }}
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $tx->transaction_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold text-blue-800">
                                {{ ucfirst(str_replace('_', ' ', $tx->type)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate">{{ $tx->description }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">
                            ₦{{ number_format($tx->amount, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            @php $colors = ['draft'=>'yellow','posted'=>'green','voided'=>'red'] @endphp
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold
                                bg-{{ $colors[$tx->status] ?? 'gray' }}-100
                                text-{{ $colors[$tx->status] ?? 'gray' }}-800">
                                {{ ucfirst($tx->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $tx->creator->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('transactions.show', $tx->id) }}" class="text-green-600 hover:underline">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No transactions yet.
                            <a href="{{ route('transactions.create') }}" class="text-green-600">Post a journal entry</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
