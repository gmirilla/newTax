@extends('layouts.app')

@section('page-title', 'Quotes & Proforma Invoices')

@section('content')
<div class="space-y-6">

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['label'=>'Draft',    'key'=>'draft',    'color'=>'gray'],
            ['label'=>'Sent',     'key'=>'sent',     'color'=>'blue'],
            ['label'=>'Accepted', 'key'=>'accepted', 'color'=>'green'],
            ['label'=>'Declined', 'key'=>'declined', 'color'=>'red'],
        ] as $card)
        <a href="{{ route('quotes.index', ['status' => $card['key']]) }}"
           class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $card['label'] }}</p>
            <p class="text-2xl font-bold text-{{ $card['color'] }}-600 mt-1">{{ $summary[$card['key']] }}</p>
        </a>
        @endforeach
    </div>

    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 class="text-base font-semibold">Quotes / Proforma Invoices</h2>
                {{-- Status filter --}}
                <form method="GET" class="flex gap-2 text-sm">
                    <select name="status" onchange="this.form.submit()"
                            class="border-gray-300 rounded-md text-sm py-1 focus:ring-green-500 focus:border-green-500">
                        <option value="">All Statuses</option>
                        @foreach(['draft','sent','accepted','declined','expired'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                </form>
            </div>
            <a href="{{ route('quotes.create') }}"
               class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                + New Quote
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quote #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($quotes as $quote)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">
                            <a href="{{ route('quotes.show', $quote) }}" class="text-green-700 hover:underline font-medium">
                                {{ $quote->quote_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $quote->customer->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $quote->quote_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 {{ $quote->isExpired() ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                            {{ $quote->expiry_date->format('d M Y') }}
                            @if($quote->isExpired()) <span class="text-xs">(Expired)</span> @endif
                        </td>
                        <td class="px-4 py-3 text-right font-medium">₦{{ number_format($quote->total_amount, 2) }}</td>
                        <td class="px-4 py-3">
                            @php
                                $colors = [
                                    'draft'    => 'gray',
                                    'sent'     => 'blue',
                                    'accepted' => 'green',
                                    'declined' => 'red',
                                    'expired'  => 'yellow',
                                ];
                                $color = $colors[$quote->status] ?? 'gray';
                            @endphp
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ ucfirst($quote->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($quote->converted_invoice_id)
                                <a href="{{ route('invoices.show', $quote->converted_invoice_id) }}"
                                   class="text-xs text-green-700 hover:underline">View Invoice →</a>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('quotes.show', $quote) }}" class="text-xs text-gray-500 hover:text-gray-700">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                            No quotes yet.
                            <a href="{{ route('quotes.create') }}" class="text-green-600 hover:underline ml-1">Create one →</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t">
            {{ $quotes->links() }}
        </div>
    </div>
</div>
@endsection
