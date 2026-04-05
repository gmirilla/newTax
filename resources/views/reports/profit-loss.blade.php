@extends('layouts.app')

@section('page-title', 'Profit & Loss Statement')

@section('content')
<div class="space-y-6">

    {{-- Controls bar --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('reports.pl') }}" class="flex flex-wrap gap-3 items-center">

            {{-- Year --}}
            <select name="year" onchange="this.form.submit()"
                    class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                @for($y = now()->year; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>

            {{-- Month --}}
            <select name="month" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                <option value="">Full Year</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ date('F', mktime(0,0,0,$m,1)) }}
                    </option>
                @endfor
            </select>

            {{-- Hidden basis so submit button preserves current toggle --}}
            <input type="hidden" name="basis" value="{{ $basis }}">

            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">
                Apply
            </button>

            <div class="flex-1"></div>

            {{-- Accounting basis toggle --}}
            <div class="flex rounded-md overflow-hidden border border-gray-300 text-sm" role="group">
                <a href="{{ route('reports.pl', ['year'=>$year,'month'=>$month,'basis'=>'accrual']) }}"
                   class="px-4 py-1.5 font-medium transition-colors
                          {{ $basis === 'accrual' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                    Accrual
                </a>
                <a href="{{ route('reports.pl', ['year'=>$year,'month'=>$month,'basis'=>'cash']) }}"
                   class="px-4 py-1.5 font-medium border-l border-gray-300 transition-colors
                          {{ $basis === 'cash' ? 'bg-amber-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                    Cash
                </a>
            </div>

            {{-- Export buttons --}}
            @php $exportParams = ['year'=>$year,'month'=>$month,'basis'=>$basis]; @endphp
            <a href="{{ route('reports.pl.excel', $exportParams) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-emerald-300 text-emerald-700 text-sm font-medium rounded-md hover:bg-emerald-50">
                ⬇ Excel
            </a>
            <a href="{{ route('reports.pl.pdf', $exportParams) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-red-300 text-red-700 text-sm font-medium rounded-md hover:bg-red-50">
                ⬇ PDF
            </a>
        </form>
    </div>

    {{-- Cash-basis advisory banner --}}
    @if($basis === 'cash')
    <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
        <span class="text-lg leading-none mt-0.5">⚠</span>
        <div>
            <p class="font-semibold">Cash Basis — Management Information Only</p>
            <p class="mt-0.5 text-xs">
                Revenue shows actual payments received in this period, not invoices issued.
                FIRS assess CIT on <strong>accrual basis</strong>. Use this view for cashflow
                insight; switch to <a href="{{ route('reports.pl', ['year'=>$year,'month'=>$month,'basis'=>'accrual']) }}"
                class="underline font-medium">Accrual</a> for tax reporting.
            </p>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold">Profit & Loss Statement</h2>
                <p class="text-sm text-gray-500">
                    {{ \Carbon\Carbon::parse($report['period_start'])->format('d M Y') }}
                    &mdash;
                    {{ \Carbon\Carbon::parse($report['period_end'])->format('d M Y') }}
                    &nbsp;·&nbsp;
                    <span class="font-medium {{ $basis === 'cash' ? 'text-amber-700' : 'text-blue-700' }}">
                        {{ $basis === 'cash' ? 'Cash Basis' : 'Accrual Basis' }}
                    </span>
                </p>
            </div>
            {{-- Source legend --}}
            <div class="flex items-center gap-3 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-indigo-400 inline-block"></span> Journal</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span> Invoices</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-teal-400 inline-block"></span> Payments</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-orange-400 inline-block"></span> Expenses</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span> Payroll</span>
            </div>
        </div>

        <div class="p-6 space-y-6">

            {{-- Revenue --}}
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 mb-3">Revenue</h3>
                @if(count($report['revenue']) > 0)
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase">
                            <th class="py-1 pl-4 text-left font-medium w-16">Code</th>
                            <th class="py-1 text-left font-medium">Account</th>
                            <th class="py-1 text-center font-medium w-20">Source</th>
                            <th class="py-1 pr-2 text-right font-medium w-36">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($report['revenue'] as $row)
                    @php
                        $src = $row['source'] ?? 'journal';
                        $dotClass = match($src) {
                            'payments' => 'bg-teal-400',
                            'invoices' => 'bg-green-400',
                            'payroll'  => 'bg-blue-400',
                            'expenses' => 'bg-orange-400',
                            default    => 'bg-indigo-400',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="py-1.5 pl-4 font-mono text-xs text-gray-400">{{ $row['code'] }}</td>
                        <td class="py-1.5 text-gray-700">{{ $row['name'] }}</td>
                        <td class="py-1.5 text-center">
                            <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                <span class="inline-block w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
                                {{ ucfirst($src) }}
                            </span>
                        </td>
                        <td class="py-1.5 pr-2 text-right font-medium text-green-700">
                            ₦{{ number_format($row['balance'], 2) }}
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="border-t font-bold bg-green-50">
                        <td class="py-2 pl-4 font-mono text-xs text-gray-400"></td>
                        <td class="py-2" colspan="2">Total Revenue</td>
                        <td class="py-2 pr-2 text-right text-green-700">
                            ₦{{ number_format($report['total_revenue'], 2) }}
                        </td>
                    </tr>
                    </tfoot>
                </table>
                @else
                <p class="text-sm text-gray-400 italic pl-4">
                    No {{ $basis === 'cash' ? 'payments received' : 'revenue recorded' }} for this period.
                    <a href="{{ route('invoices.create') }}" class="text-green-600 not-italic hover:underline">
                        Create an invoice
                    </a>
                    @if($basis === 'cash') then <a href="{{ route('invoices.index') }}" class="text-green-600 hover:underline">record a payment</a> against it @endif.
                </p>
                @endif
            </div>

            {{-- Expenses --}}
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 mb-3">Expenses</h3>
                @if(count($report['expenses']) > 0)
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase">
                            <th class="py-1 pl-4 text-left font-medium w-16">Code</th>
                            <th class="py-1 text-left font-medium">Account</th>
                            <th class="py-1 text-center font-medium w-20">Source</th>
                            <th class="py-1 pr-2 text-right font-medium w-36">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($report['expenses'] as $row)
                    @php
                        $src = $row['source'] ?? 'journal';
                        $dotClass = match($src) {
                            'payments' => 'bg-teal-400',
                            'invoices' => 'bg-green-400',
                            'payroll'  => 'bg-blue-400',
                            'expenses' => 'bg-orange-400',
                            default    => 'bg-indigo-400',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="py-1.5 pl-4 font-mono text-xs text-gray-400">{{ $row['code'] }}</td>
                        <td class="py-1.5 text-gray-700">{{ $row['name'] }}</td>
                        <td class="py-1.5 text-center">
                            <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                <span class="inline-block w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
                                {{ ucfirst($src) }}
                            </span>
                        </td>
                        <td class="py-1.5 pr-2 text-right font-medium text-red-600">
                            ₦{{ number_format($row['balance'], 2) }}
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="border-t font-bold bg-red-50">
                        <td class="py-2 pl-4 font-mono text-xs text-gray-400"></td>
                        <td class="py-2" colspan="2">Total Expenses</td>
                        <td class="py-2 pr-2 text-right text-red-600">
                            ₦{{ number_format($report['total_expenses'], 2) }}
                        </td>
                    </tr>
                    </tfoot>
                </table>
                @else
                <p class="text-sm text-gray-400 italic pl-4">
                    No expenses recorded for this period.
                    <a href="{{ route('transactions.expenses') }}" class="text-green-600 not-italic hover:underline">
                        Record an expense.
                    </a>
                </p>
                @endif
            </div>

            {{-- Net result --}}
            <div class="rounded-lg p-5 {{ $report['is_profit'] ? 'bg-green-100 border border-green-300' : 'bg-red-100 border border-red-300' }}">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="font-bold text-lg">
                            {{ $report['is_profit'] ? 'Net Profit' : 'Net Loss' }}
                        </span>
                        <p class="text-xs text-gray-500 mt-1">
                            Revenue ₦{{ number_format($report['total_revenue'], 2) }}
                            &minus; Expenses ₦{{ number_format($report['total_expenses'], 2) }}
                        </p>
                    </div>
                    <span class="text-2xl font-bold {{ $report['is_profit'] ? 'text-green-700' : 'text-red-700' }}">
                        ₦{{ number_format(abs($report['net_profit']), 2) }}
                    </span>
                </div>
            </div>

            @if(!$report['is_profit'])
            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-700">
                ⚠️ Loss position this period. No CIT due; however minimum tax of 0.5% of gross
                turnover may apply under NTA 2025.
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
