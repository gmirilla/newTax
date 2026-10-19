@extends('layouts.app')

@section('page-title', 'VAT Computation')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Period selector --}}
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
            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">Compute</button>
        </form>
    </div>

    {{-- Status bar --}}
    <div class="bg-white rounded-lg shadow p-4 flex flex-wrap items-center gap-6 text-sm">
        <div>
            <p class="text-xs text-gray-400 uppercase font-medium">Filing Period</p>
            <p class="font-semibold">{{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</p>
        </div>
        <div class="border-l pl-4">
            <p class="text-xs text-gray-400 uppercase font-medium">Due Date</p>
            <p class="font-semibold {{ now()->toDateString() > $vatReturn->due_date ? 'text-red-600' : 'text-gray-700' }}">
                {{ \Carbon\Carbon::parse($vatReturn->due_date)->format('d M Y') }}
                @if(now()->toDateString() > $vatReturn->due_date)
                    <span class="text-xs bg-red-100 text-red-700 rounded px-1 ml-1">Overdue</span>
                @endif
            </p>
        </div>
        <div class="border-l pl-4">
            <p class="text-xs text-gray-400 uppercase font-medium">Status</p>
            <span class="px-2 py-0.5 text-xs rounded-full font-medium
                {{ $vatReturn->status === 'paid'   ? 'bg-green-100 text-green-700' :
                   ($vatReturn->status === 'filed'  ? 'bg-blue-100 text-blue-700' :
                   ($vatReturn->status === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')) }}">
                {{ ucfirst(str_replace('_', ' ', $vatReturn->status)) }}
            </span>
        </div>
        @if(auth()->user()->tenant->vat_registered)
        <div class="border-l pl-4">
            <p class="text-xs text-gray-400 uppercase font-medium">VAT Number</p>
            <p class="font-semibold text-green-700">{{ auth()->user()->tenant->vat_number }}</p>
        </div>
        @else
        <div class="border-l pl-4">
            <p class="text-xs font-medium text-orange-600">Not VAT-registered (below ₦25M threshold)</p>
        </div>
        @endif
    </div>

    {{-- Computation table --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-sm font-semibold">VAT Return Computation</h3>
            <p class="text-xs text-gray-400 mt-0.5">
                Period: {{ \Carbon\Carbon::parse($vatReturn->period_start)->format('d M Y') }}
                — {{ \Carbon\Carbon::parse($vatReturn->period_end)->format('d M Y') }}
            </p>
        </div>
        <div class="p-6">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    <tr class="bg-blue-50">
                        <td class="py-3 px-4 font-semibold text-blue-800" colspan="2">Output VAT (Tax Collected on Sales)</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-4 text-gray-600">Output VAT collected (7.5% on invoices)</td>
                        <td class="py-2 text-right px-4 text-blue-700 font-semibold">₦{{ number_format($vatReturn->output_vat, 2) }}</td>
                    </tr>

                    <tr class="bg-green-50">
                        <td class="py-3 px-4 font-semibold text-green-800" colspan="2">Input VAT (Tax Paid on Purchases)</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-4 text-gray-600">Input VAT claimable (7.5% on approved expenses)</td>
                        <td class="py-2 text-right px-4 text-green-700 font-semibold">₦{{ number_format($vatReturn->input_vat, 2) }}</td>
                    </tr>

                    <tr class="bg-orange-50 border-t-2 border-orange-200">
                        <td class="py-3 px-4 font-bold text-orange-800">
                            Net VAT {{ $vatReturn->net_vat_payable >= 0 ? 'Payable to FIRS' : 'Credit (Refundable)' }}
                        </td>
                        <td class="py-3 text-right px-4 font-bold text-xl {{ $vatReturn->net_vat_payable >= 0 ? 'text-orange-700' : 'text-green-700' }}">
                            ₦{{ number_format(abs($vatReturn->net_vat_payable), 2) }}
                        </td>
                    </tr>

                    @if($vatReturn->amount_paid > 0)
                    <tr>
                        <td class="py-2 px-4 text-gray-600">Amount Paid</td>
                        <td class="py-2 text-right px-4 text-green-700">₦{{ number_format($vatReturn->amount_paid, 2) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Underlying transactions ───────────────────────────────────────────── --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold">Transactions in This Period</h3>
                <p class="text-xs text-gray-400 mt-0.5">Review what makes up the figures above before filing.</p>
            </div>
            @if($report['output_vat']['items']->isNotEmpty() || $report['input_vat']['items']->isNotEmpty())
            <button onclick="downloadVatTransactionsCsv()"
                    class="px-3 py-1.5 border border-gray-300 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-50 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download CSV
            </button>
            @endif
        </div>

        <div class="px-6 pt-4">
            <h4 class="text-xs font-semibold text-blue-800 uppercase tracking-wide mb-2">Output VAT — Sales Invoices</h4>
        </div>
        <div class="overflow-x-auto">
            <table id="vat-output-items" class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Invoice #</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Customer</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Subtotal</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Amount Paid</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">VAT Counted</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($report['output_vat']['items'] as $inv)
                    @php $counted = $vatService->proratedOutputVat($inv); @endphp
                    <tr data-export="{{ json_encode([
                        'Output VAT',
                        $inv->invoice_date->format('Y-m-d'),
                        $inv->invoice_number,
                        $inv->customer?->name ?? '—',
                        (string) $inv->subtotal,
                        (string) $counted,
                        ucfirst($inv->status),
                    ]) }}">
                        <td class="px-4 py-2 font-mono text-xs text-green-700">{{ $inv->invoice_number }}</td>
                        <td class="px-4 py-2">{{ $inv->customer?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $inv->invoice_date->format('d M Y') }}</td>
                        <td class="px-4 py-2 text-right">₦{{ number_format($inv->subtotal, 2) }}</td>
                        <td class="px-4 py-2 text-right">₦{{ number_format($inv->amount_paid, 2) }}</td>
                        <td class="px-4 py-2 text-right font-medium text-blue-600">
                            ₦{{ number_format($counted, 2) }}
                            @if($counted < (float) $inv->vat_amount)
                                <span class="block text-[10px] text-gray-400 font-normal">of ₦{{ number_format($inv->vat_amount, 2) }} — partially paid</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs capitalize">{{ $inv->status }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-4 text-center text-gray-400">No output VAT this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 pt-5">
            <h4 class="text-xs font-semibold text-green-800 uppercase tracking-wide mb-2">Input VAT — Expenses</h4>
        </div>
        <div class="overflow-x-auto">
            <table id="vat-input-items" class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Ref</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Vendor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Amount</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">VAT Claimed</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($report['input_vat']['items'] as $exp)
                    <tr data-export="{{ json_encode([
                        'Input VAT',
                        $exp->expense_date->format('Y-m-d'),
                        $exp->reference,
                        $exp->vendor->name ?? 'Direct expense',
                        (string) $exp->amount,
                        (string) $exp->vat_amount,
                        ucfirst($exp->status),
                    ]) }}">
                        <td class="px-4 py-2 font-mono text-xs">{{ $exp->reference }}</td>
                        <td class="px-4 py-2">{{ $exp->vendor->name ?? 'Direct expense' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $exp->expense_date->format('d M Y') }}</td>
                        <td class="px-4 py-2 text-right">₦{{ number_format($exp->amount, 2) }}</td>
                        <td class="px-4 py-2 text-right font-medium text-green-600">₦{{ number_format($exp->vat_amount, 2) }}</td>
                        <td class="px-4 py-2 text-xs capitalize">{{ $exp->status }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">No input VAT this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="px-6 py-4 text-xs text-gray-400 border-t">
            Reflects {{ date('F Y', mktime(0,0,0,$month,1,$year)) }} invoices/expenses as of today
            @if(in_array($vatReturn->status, ['filed', 'paid']))
                — this return was already filed, so the figures above stay locked even if transactions in this period change later.
            @endif
        </p>
    </div>

    {{-- File Return --}}
    @if($vatReturn->status === 'pending' || $vatReturn->status === 'overdue')
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold mb-4">File This VAT Return</h3>
        <form method="POST" action="{{ route('tax.vat.filed', $vatReturn) }}">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                <div>
                    <label class="block text-sm font-medium text-gray-700">NRS Reference Number</label>
                    <input type="text" name="filing_reference"
                           value="{{ $vatReturn->filing_reference }}"
                           placeholder="e.g. VAT/2024/001"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date Filed</label>
                    <input type="date" name="filed_date" value="{{ now()->toDateString() }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>
            <button type="submit"
                    class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Mark as Filed
            </button>
            <p class="text-xs text-gray-400 mt-2">Records that this VAT return was submitted to NRS via TaxPro-Max.</p>
        </form>
    </div>
    @else
    <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 flex items-center gap-2">
        ✓ This VAT return was filed on {{ \Carbon\Carbon::parse($vatReturn->filed_date)->format('d M Y') }}.
        @if($vatReturn->filing_reference)
            NRS Ref: <strong>{{ $vatReturn->filing_reference }}</strong>
        @endif
    </div>
    @endif

    <div class="text-xs text-gray-400 text-center">
        VAT at 7.5% per Finance Act 2019 (effective 1 Feb 2020). Returns and remittance due by 21st of following month.
        Businesses with annual turnover ≥ ₦25M must register for VAT with FIRS.
    </div>
</div>

@push('scripts')
<script>
function downloadVatTransactionsCsv() {
    var headers = ['Type', 'Date', 'Reference', 'Party', 'Amount', 'VAT Counted', 'Status'];
    var rows = [headers];
    document.querySelectorAll('#vat-output-items tbody tr[data-export], #vat-input-items tbody tr[data-export]').forEach(function (tr) {
        try { rows.push(JSON.parse(tr.dataset.export)); } catch (e) {}
    });
    var csv = rows.map(function (r) {
        return r.map(function (v) { return '"' + String(v ?? '').replace(/"/g, '""') + '"'; }).join(',');
    }).join('\n');
    var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    a.href     = url;
    a.download = 'vat-transactions-{{ $year }}-{{ str_pad($month, 2, "0", STR_PAD_LEFT) }}.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
@endpush
@endsection
