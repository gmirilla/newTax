@extends('layouts.app')
@section('page-title', 'VAT Returns')
@section('content')
<div class="space-y-6">
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
                <a href="{{ route('tax.vat.compute') }}"
                   class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                    Compute New Return
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Output VAT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Input VAT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net VAT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($returns as $return)
                    <tr class="{{ $return->isOverdue() ? 'bg-red-50' : '' }}">
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
                        <td class="px-4 py-3">
                            @php $c = ['pending'=>'yellow','filed'=>'blue','paid'=>'green','overdue'=>'red','nil_return'=>'gray'] @endphp
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-{{ $c[$return->status] }}-100 text-{{ $c[$return->status] }}-800">
                                {{ ucfirst(str_replace('_',' ', $return->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
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
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
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
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
