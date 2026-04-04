@extends('layouts.app')

@section('page-title', 'Transaction ' . $transaction->reference)

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-bold font-mono text-green-700">{{ $transaction->reference }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $transaction->description }}</p>
            </div>
            <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold
                {{ $transaction->status === 'posted' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                {{ ucfirst($transaction->status) }}
            </span>
        </div>

        <dl class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">Date</dt>
                <dd class="font-medium">{{ $transaction->transaction_date->format('d M Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Type</dt>
                <dd class="font-medium capitalize">{{ str_replace('_', ' ', $transaction->type) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Amount</dt>
                <dd class="font-bold text-green-700">₦{{ number_format($transaction->amount, 2) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Created By</dt>
                <dd class="font-medium">{{ $transaction->creator->name ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Journal Entries (double-entry) --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-base font-semibold">Journal Entries (Double-Entry)</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit (₦)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit (₦)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($transaction->journalEntries as $entry)
                <tr>
                    <td class="px-4 py-3 text-sm font-medium">{{ $entry->account->name }}</td>
                    <td class="px-4 py-3 text-sm font-mono text-gray-500">{{ $entry->account->code }}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="inline-flex rounded-full px-2 text-xs font-semibold
                            {{ $entry->entry_type === 'debit' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ strtoupper($entry->entry_type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-medium">
                        @if($entry->entry_type === 'debit')
                            ₦{{ number_format($entry->amount, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-medium">
                        @if($entry->entry_type === 'credit')
                            ₦{{ number_format($entry->amount, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ $entry->description }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-bold">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-sm">Totals</td>
                    <td class="px-4 py-3 text-sm text-right">
                        ₦{{ number_format($transaction->journalEntries->where('entry_type','debit')->sum('amount'), 2) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right">
                        ₦{{ number_format($transaction->journalEntries->where('entry_type','credit')->sum('amount'), 2) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="flex gap-3">
        <a href="{{ route('transactions.index') }}"
           class="px-4 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">
            ← Back to Transactions
        </a>
    </div>
</div>
@endsection
