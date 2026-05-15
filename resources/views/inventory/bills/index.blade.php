@extends('layouts.app')
@section('page-title', 'Supplier Bills')

@section('content')
<div class="space-y-5">

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-yellow-400">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Outstanding Bills</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats->outstanding_count ?? 0) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">₦{{ number_format($stats->outstanding_value ?? 0, 2) }} total</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Overdue</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($stats->overdue_count ?? 0) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">₦{{ number_format($stats->overdue_value ?? 0, 2) }} overdue</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">All Bills</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($bills->total()) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">matching current filters</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg">

        {{-- Header --}}
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Supplier Bills</h2>
                <p class="text-xs text-gray-500 mt-0.5">Generated automatically when stock is received via restock requests.</p>
            </div>
            <a href="{{ route('inventory.restock.index') }}"
               class="text-sm text-green-600 hover:underline">← Restock Requests</a>
        </div>

        {{-- Filters --}}
        <div class="px-6 py-3 bg-gray-50 border-b">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Bill # or supplier…"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 w-48">

                <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All Statuses</option>
                    <option value="sent"    {{ request('status') === 'sent'    ? 'selected' : '' }}>Outstanding</option>
                    <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partially Paid</option>
                    <option value="paid"    {{ request('status') === 'paid'    ? 'selected' : '' }}>Paid</option>
                </select>

                <input type="date" name="from" value="{{ request('from') }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                <input type="date" name="to" value="{{ request('to') }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">

                <button type="submit"
                        class="px-4 py-1.5 bg-gray-700 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                    Filter
                </button>
                @if(request()->hasAny(['search','status','from','to']))
                    <a href="{{ route('inventory.bills.index') }}"
                       class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Restock Ref</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total (₦)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid (₦)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance (₦)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($bills as $bill)
                    @php
                        $isOverdue  = $bill->status !== 'paid' && $bill->due_date->isPast();
                        $statusColor = match(true) {
                            $bill->status === 'paid'    => ['pill' => 'bg-green-100 text-green-800'],
                            $bill->status === 'partial' => ['pill' => 'bg-blue-100 text-blue-800'],
                            $isOverdue                  => ['pill' => 'bg-red-100 text-red-800'],
                            default                     => ['pill' => 'bg-yellow-100 text-yellow-800'],
                        };
                        $rr = $bill->restockRequest;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $bill->invoice_number }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($rr)
                                <a href="{{ route('inventory.restock.show', $rr) }}"
                                   class="text-green-700 hover:underline font-medium">
                                    {{ $rr->request_number }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            @if($rr?->item)
                                <p class="font-medium">{{ $rr->item->name }}</p>
                                @if($rr->item->sku)
                                    <p class="text-xs text-gray-400">{{ $rr->item->sku }}</p>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $rr->supplier_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                            {{ $bill->invoice_date->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap {{ $isOverdue ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                            {{ $bill->due_date->format('d M Y') }}
                            @if($isOverdue)
                                <br><span class="text-[10px] font-normal">{{ $bill->due_date->diffForHumans() }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                            {{ number_format($bill->total_amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-green-700">
                            {{ number_format($bill->amount_paid, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-semibold {{ (float)$bill->balance_due > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ number_format($bill->balance_due, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold {{ $statusColor['pill'] }}">
                                @if($isOverdue) Overdue
                                @elseif($bill->status === 'partial') Partial
                                @elseif($bill->status === 'paid') Paid
                                @else Outstanding
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            @if($rr)
                                <a href="{{ route('inventory.restock.show', $rr) }}"
                                   class="text-green-600 hover:text-green-800 font-medium">View</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-4 py-10 text-center text-gray-400">
                            <p class="text-sm">No supplier bills found.</p>
                            <p class="text-xs mt-1 text-gray-300">Bills are created automatically when restock goods are marked as received.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bills->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $bills->links() }}
        </div>
        @endif
    </div>

    {{-- AP Accounting note --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
        <p class="font-semibold mb-1">Accounting note</p>
        <p>
            When stock is received, the system posts <strong>Dr Inventory / Cr AP 2001</strong>.
            Recording a payment here posts <strong>Dr AP 2001 / Cr Bank</strong>, which clears the liability.
            All entries are visible in the General Ledger.
        </p>
    </div>
</div>
@endsection
