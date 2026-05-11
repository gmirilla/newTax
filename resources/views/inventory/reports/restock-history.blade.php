@extends('layouts.app')

@section('title', 'Restock History')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Restock History</h1>
            <p class="text-sm text-gray-500 mt-1">All restock requests with supplier and approval details</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.reports.restock-history.pdf', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-red-300 text-red-700 bg-white hover:bg-red-50">
                PDF
            </a>
            <a href="{{ route('inventory.reports.restock-history.excel', request()->query()) }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-green-300 text-green-700 bg-white hover:bg-green-50">
                Excel
            </a>
        </div>
    </div>

    <form method="GET" class="bg-white shadow rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
            <input type="date" name="from" value="{{ $filters['from'] }}"
                   class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
            <input type="date" name="to" value="{{ $filters['to'] }}"
                   class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
                <option value="">All Statuses</option>
                @foreach(['pending','approved','received','rejected','cancelled'] as $s)
                <option value="{{ $s }}" @selected($filters['status'] === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-1.5 bg-naija-green text-white text-sm rounded hover:bg-green-700">Filter</button>
        <a href="{{ route('inventory.reports.restock-history') }}" class="px-4 py-1.5 text-sm text-gray-600 hover:underline">Clear</a>
    </form>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 text-sm text-gray-500">
            {{ $requests->count() }} request(s) &bull; {{ $filters['from'] }} → {{ $filters['to'] }}
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-naija-green text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Request No.</th>
                        <th class="px-4 py-3 text-left font-semibold">Item</th>
                        <th class="px-4 py-3 text-right font-semibold">Qty</th>
                        <th class="px-4 py-3 text-right font-semibold">Unit Cost</th>
                        <th class="px-4 py-3 text-right font-semibold">Total Cost</th>
                        <th class="px-4 py-3 text-left font-semibold">Supplier</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Requested By</th>
                        <th class="px-4 py-3 text-left font-semibold">Approved By</th>
                        <th class="px-4 py-3 text-left font-semibold">Received</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($requests as $rr)
                    @php
                        $statusColor = match($rr->status) {
                            'pending'   => 'bg-yellow-100 text-yellow-800',
                            'approved'  => 'bg-blue-100 text-blue-800',
                            'received'  => 'bg-green-100 text-green-800',
                            'rejected'  => 'bg-red-100 text-red-800',
                            'cancelled' => 'bg-gray-100 text-gray-500',
                            default     => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-gray-900 whitespace-nowrap">
                            <a href="{{ route('inventory.restock.show', $rr) }}" class="text-naija-green hover:underline">{{ $rr->request_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-900">{{ $rr->item?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float)$rr->quantity_requested, 3) }}</td>
                        <td class="px-4 py-3 text-right font-mono">₦{{ number_format((float)$rr->unit_cost, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold">₦{{ number_format($rr->totalCost(), 2) }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $rr->supplier_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColor }}">
                                {{ ucfirst($rr->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $rr->requester?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $rr->approver?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs whitespace-nowrap">{{ $rr->received_at?->format('d M Y') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-400">No restock requests found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4 text-right">Generated {{ now()->format('d M Y H:i') }}</p>
</div>
@endsection
