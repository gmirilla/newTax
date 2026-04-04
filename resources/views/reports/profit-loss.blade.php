@extends('layouts.app')

@section('page-title', 'Profit & Loss Statement')

@section('content')
<div class="space-y-6">

    {{-- Period selector --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-center">
            <select name="year" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                @for($y = now()->year; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <select name="month" class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5">
                <option value="">Full Year</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">Apply</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-base font-semibold">Profit & Loss Statement</h2>
            <p class="text-sm text-gray-500">Period: {{ $report['period_start'] }} to {{ $report['period_end'] }}</p>
        </div>

        <div class="p-6 space-y-6">
            {{-- Revenue --}}
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 mb-3">Revenue</h3>
                <table class="w-full text-sm">
                    @foreach($report['revenue'] as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="py-1.5 pl-4 text-gray-600">{{ $row['code'] }} – {{ $row['name'] }}</td>
                        <td class="py-1.5 text-right font-medium text-green-700">₦{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="border-t font-bold bg-green-50">
                        <td class="py-2 pl-4">Total Revenue</td>
                        <td class="py-2 text-right text-green-700">₦{{ number_format($report['total_revenue'], 2) }}</td>
                    </tr>
                </table>
            </div>

            {{-- Expenses --}}
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 mb-3">Expenses</h3>
                <table class="w-full text-sm">
                    @foreach($report['expenses'] as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="py-1.5 pl-4 text-gray-600">{{ $row['code'] }} – {{ $row['name'] }}</td>
                        <td class="py-1.5 text-right font-medium text-red-600">₦{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="border-t font-bold bg-red-50">
                        <td class="py-2 pl-4">Total Expenses</td>
                        <td class="py-2 text-right text-red-600">₦{{ number_format($report['total_expenses'], 2) }}</td>
                    </tr>
                </table>
            </div>

            {{-- Net Profit --}}
            <div class="rounded-lg p-4 {{ $report['is_profit'] ? 'bg-green-100 border border-green-300' : 'bg-red-100 border border-red-300' }}">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-lg">{{ $report['is_profit'] ? 'Net Profit' : 'Net Loss' }}</span>
                    <span class="text-2xl font-bold {{ $report['is_profit'] ? 'text-green-700' : 'text-red-700' }}">
                        ₦{{ number_format(abs($report['net_profit']), 2) }}
                    </span>
                </div>
            </div>

            @if(!$report['is_profit'])
            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-700">
                ⚠️ Your company is in a loss position this period. No CIT will be due; however, minimum tax of 0.5% of gross turnover may apply.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
