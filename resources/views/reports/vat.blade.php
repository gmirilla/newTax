@extends('layouts.app')

@section('page-title', 'VAT Report')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex gap-3 items-center">
            <select name="year" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <select name="month" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">View Report</button>
        </form>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="bg-blue-50 rounded-lg p-4 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Output VAT (Sales)</p>
            <p class="text-2xl font-bold text-blue-700">₦{{ number_format($report['output_vat']['total'], 2) }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Input VAT (Purchases)</p>
            <p class="text-2xl font-bold text-green-700">₦{{ number_format($report['input_vat']['total'], 2) }}</p>
        </div>
        <div class="{{ $report['net_vat'] >= 0 ? 'bg-orange-50' : 'bg-green-50' }} rounded-lg p-4 text-center">
            <p class="text-xs text-gray-500 uppercase font-medium">Net VAT {{ $report['net_vat'] >= 0 ? 'Payable' : 'Credit' }}</p>
            <p class="text-2xl font-bold {{ $report['net_vat'] >= 0 ? 'text-orange-700' : 'text-green-700' }}">
                ₦{{ number_format(abs($report['net_vat']), 2) }}
            </p>
            <p class="text-xs text-gray-500">Due: {{ $report['due_date'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Output VAT --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-sm font-semibold">Output VAT — Sales Invoices</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Invoice #</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Customer</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Subtotal</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">VAT (7.5%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($report['output_vat']['items'] as $inv)
                    <tr>
                        <td class="px-4 py-2 font-mono text-xs text-green-700">{{ $inv->invoice_number }}</td>
                        <td class="px-4 py-2">{{ $inv->customer->name }}</td>
                        <td class="px-4 py-2 text-right">₦{{ number_format($inv->subtotal, 2) }}</td>
                        <td class="px-4 py-2 text-right font-medium text-blue-600">₦{{ number_format($inv->vat_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400">No output VAT this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Input VAT --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-sm font-semibold">Input VAT — Expenses</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Ref</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Vendor</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Amount</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">VAT Claimed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($report['input_vat']['items'] as $exp)
                    <tr>
                        <td class="px-4 py-2 font-mono text-xs">{{ $exp->reference }}</td>
                        <td class="px-4 py-2">{{ $exp->vendor->name ?? 'Direct expense' }}</td>
                        <td class="px-4 py-2 text-right">₦{{ number_format($exp->amount, 2) }}</td>
                        <td class="px-4 py-2 text-right font-medium text-green-600">₦{{ number_format($exp->vat_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400">No input VAT this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
