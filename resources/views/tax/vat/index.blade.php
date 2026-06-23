@extends('layouts.app')
@section('page-title', 'VAT Returns')
@section('content')
<div class="space-y-6" x-data="{ show: false, selected: {} }">

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold">VAT Returns – {{ $year }}</h2>
            <div class="flex gap-3">
                <form method="GET" class="flex gap-2">
                    <select name="year" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 text-sm shadow-sm">
                        @for($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </form>
                @if($returns->isNotEmpty())
                <button onclick="downloadVatCsv()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download CSV
                </button>
                @endif
                <a href="{{ route('tax.vat.compute') }}"
                   class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                    Compute New Return
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="vat-table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Output VAT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Input VAT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net VAT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NRS Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($returns as $return)
                    @php
                        $statusLabel = ucfirst(str_replace('_', ' ', $return->status));
                        $color = ['pending'=>'yellow','filed'=>'blue','paid'=>'green','overdue'=>'red','nil_return'=>'gray'][$return->status] ?? 'gray';
                        $detail = [
                            'period'           => $return->getMonthName() . ' ' . $return->tax_year,
                            'output_vat'       => '₦' . number_format($return->output_vat, 2),
                            'input_vat'        => '₦' . number_format($return->input_vat, 2),
                            'net_vat'          => '₦' . number_format($return->net_vat_payable, 2),
                            'due_date'         => $return->due_date->format('d M Y'),
                            'filing_reference' => $return->filing_reference ?? '—',
                            'filed_date'       => $return->filed_date?->format('d M Y') ?? '—',
                            'paid_date'        => $return->paid_date?->format('d M Y') ?? '—',
                            'amount_paid'      => $return->amount_paid ? '₦' . number_format($return->amount_paid, 2) : '—',
                            'status'           => $statusLabel,
                        ];
                    @endphp
                    <tr class="{{ $return->isOverdue() ? 'bg-red-50' : '' }}"
                        data-export="{{ json_encode([
                            $return->getMonthName() . ' ' . $return->tax_year,
                            (string) $return->output_vat,
                            (string) $return->input_vat,
                            (string) $return->net_vat_payable,
                            $return->due_date->format('d M Y'),
                            $return->filing_reference ?? '',
                            $statusLabel,
                        ]) }}">
                        <td class="px-4 py-3 text-sm font-medium">{{ $return->getMonthName() }} {{ $return->tax_year }}</td>
                        <td class="px-4 py-3 text-sm text-right">₦{{ number_format($return->output_vat, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right">₦{{ number_format($return->input_vat, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold
                            {{ $return->net_vat_payable >= 0 ? 'text-red-600' : 'text-green-600' }}">
                            ₦{{ number_format($return->net_vat_payable, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm {{ $return->isOverdue() ? 'text-red-600 font-bold' : '' }}">
                            {{ $return->due_date->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-600">
                            {{ $return->filing_reference ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-col gap-1.5">
                                @if(in_array($return->status, ['filed', 'paid']))
                                <button @click="selected = @js($detail); show = true"
                                        class="text-xs text-blue-600 hover:underline font-medium text-left">
                                    View Details
                                </button>
                                @endif
                                @if($return->status === 'pending' || $return->status === 'nil_return')
                                <form method="POST" action="{{ route('tax.vat.filed', $return) }}" class="flex gap-1">
                                    @csrf
                                    <input type="hidden" name="filed_date" value="{{ now()->toDateString() }}">
                                    <input type="text" name="filing_reference" placeholder="NRS Ref #" required
                                           class="border rounded text-xs px-2 py-1 w-28">
                                    <button class="text-xs bg-blue-600 text-white px-2 py-1 rounded">Mark Filed</button>
                                </form>
                                @elseif($return->status === 'filed')
                                <form method="POST" action="{{ route('tax.vat.paid', $return) }}" class="flex gap-1">
                                    @csrf
                                    <input type="hidden" name="paid_date" value="{{ now()->toDateString() }}">
                                    <input type="number" name="amount_paid" value="{{ $return->net_vat_payable }}" step="0.01"
                                           class="border rounded text-xs px-2 py-1 w-28">
                                    <button class="text-xs bg-green-600 text-white px-2 py-1 rounded">Mark Paid</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No VAT returns for {{ $year }}.
                            <a href="{{ route('tax.vat.compute') }}" class="text-green-600">Compute now</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($returns->isNotEmpty())
                <tfoot class="bg-gray-50 font-medium">
                    <tr>
                        <td class="px-4 py-3 text-sm">Annual Total</td>
                        <td class="px-4 py-3 text-sm text-right">₦{{ number_format($returns->sum('output_vat'), 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right">₦{{ number_format($returns->sum('input_vat'), 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold">₦{{ number_format($returns->sum('net_vat_payable'), 2) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- VAT Return Detail Modal --}}
    <div x-show="show" x-transition.opacity style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="show = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md z-10" @click.stop>

            <div class="flex items-center justify-between px-6 py-4 border-b">
                <div>
                    <h3 class="text-sm font-semibold">VAT Return</h3>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="selected.period"></p>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <dl class="px-6 py-5 space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                              :class="{
                                  'bg-green-100 text-green-800': selected.status === 'Paid',
                                  'bg-blue-100  text-blue-800':  selected.status === 'Filed',
                              }"
                              x-text="selected.status"></span>
                    </dd>
                </div>

                <div class="border-t pt-3 space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Output VAT (collected from customers)</dt>
                        <dd class="font-medium" x-text="selected.output_vat"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Input VAT (claimable credit)</dt>
                        <dd class="font-medium" x-text="selected.input_vat"></dd>
                    </div>
                    <div class="flex justify-between bg-orange-50 rounded px-2 py-2 mt-1">
                        <dt class="font-semibold text-orange-800">Net VAT Payable</dt>
                        <dd class="font-bold text-orange-700" x-text="selected.net_vat"></dd>
                    </div>
                </div>

                <div class="border-t pt-3 space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Filing Due Date</dt>
                        <dd x-text="selected.due_date"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Date Filed</dt>
                        <dd x-text="selected.filed_date"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">NRS Reference</dt>
                        <dd class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded" x-text="selected.filing_reference"></dd>
                    </div>
                </div>

                <div x-show="selected.status === 'Paid'" class="border-t pt-3 space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Date Paid</dt>
                        <dd x-text="selected.paid_date"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Amount Paid</dt>
                        <dd class="font-semibold text-green-700" x-text="selected.amount_paid"></dd>
                    </div>
                </div>
            </dl>

            <div class="px-6 py-3 border-t bg-gray-50 rounded-b-xl text-right">
                <button @click="show = false"
                        class="px-4 py-1.5 text-sm bg-gray-200 hover:bg-gray-300 rounded-md text-gray-700">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function downloadVatCsv() {
    var headers = ['Period', 'Output VAT', 'Input VAT', 'Net VAT', 'Due Date', 'NRS Reference', 'Status'];
    var rows = [headers];
    document.querySelectorAll('#vat-table tbody tr[data-export]').forEach(function (tr) {
        try { rows.push(JSON.parse(tr.dataset.export)); } catch (e) {}
    });
    var csv = rows.map(function (r) {
        return r.map(function (v) { return '"' + String(v ?? '').replace(/"/g, '""') + '"'; }).join(',');
    }).join('\n');
    var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    a.href     = url;
    a.download = 'vat-returns-{{ $year }}.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
@endpush
@endsection
