@extends('layouts.app')

@section('page-title', 'Invoices')

@section('content')
<div class="space-y-6">

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-blue-500">
            <p class="text-xs text-gray-500">Total Invoiced</p>
            <p class="text-xl font-bold">₦{{ number_format($summary['total_invoiced'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-green-500">
            <p class="text-xs text-gray-500">Paid</p>
            <p class="text-xl font-bold text-green-700">₦{{ number_format($summary['total_paid'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-yellow-500">
            <p class="text-xs text-gray-500">Outstanding</p>
            <p class="text-xl font-bold text-yellow-700">₦{{ number_format($summary['total_outstanding'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-t-4 border-red-500">
            <p class="text-xs text-gray-500">Overdue</p>
            <p class="text-xl font-bold text-red-700">₦{{ number_format($summary['total_overdue'], 2) }}</p>
            @if($summary['overdue_count'] > 0)
                <p class="text-xs text-red-500">{{ $summary['overdue_count'] }} invoice(s)</p>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-base font-semibold">All Invoices</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('invoices.import') }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                    ↑ Import
                </a>
                <a href="{{ route('invoices.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    + New Invoice
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="px-6 py-3 bg-gray-50 border-b">
            <form method="GET" class="flex flex-wrap gap-3">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search invoice # or customer..."
                       class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-green-500 focus:border-green-500 px-3 py-1.5">
                <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                    <option value="">All Status</option>
                    @foreach(['draft','sent','partial','paid','overdue','cancelled','void'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">VAT (7.5%)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-green-700">
                            <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->customer->name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->invoice_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-sm {{ $invoice->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                            {{ $invoice->due_date->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right">₦{{ number_format($invoice->subtotal, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-blue-600">₦{{ number_format($invoice->vat_amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">₦{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium
                            {{ $invoice->balance_due > 0 ? 'text-red-600' : 'text-green-600' }}">
                            ₦{{ number_format($invoice->balance_due, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            @php $colors = ['draft'=>'gray','sent'=>'blue','partial'=>'yellow','paid'=>'green','overdue'=>'red','void'=>'gray','cancelled'=>'gray'] @endphp
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold
                                bg-{{ $colors[$invoice->status] ?? 'gray' }}-100
                                text-{{ $colors[$invoice->status] ?? 'gray' }}-800">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex gap-2">
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-green-600 hover:underline">View</a>
                                <a href="{{ route('invoices.pdf', $invoice) }}" class="text-gray-600 hover:underline">PDF</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                            No invoices found. <a href="{{ route('invoices.create') }}" class="text-green-600">Create your first invoice</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
