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

    {{-- File Return --}}
    @if($vatReturn->status === 'pending' || $vatReturn->status === 'overdue')
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold mb-4">File This VAT Return</h3>
        <form method="POST" action="{{ route('tax.vat.filed', $vatReturn) }}">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                <div>
                    <label class="block text-sm font-medium text-gray-700">NRS Reference Number</label>
                    <input type="text" name="firs_reference"
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
@endsection
