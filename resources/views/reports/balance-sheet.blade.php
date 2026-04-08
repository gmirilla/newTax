@extends('layouts.app')

@section('page-title', 'Balance Sheet')

@section('content')
<div class="space-y-6">

    {{-- Controls --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('reports.bs') }}" class="flex flex-wrap gap-3 items-center">
            <label class="text-sm font-medium text-gray-700">As of:</label>
            <input type="date" name="as_of" value="{{ $report['as_of'] }}"
                   class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 focus:ring-green-500 focus:border-green-500">
            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md">
                Apply
            </button>

            <div class="flex-1"></div>

            {{-- Export buttons --}}
            @php $asOf = $report['as_of']; @endphp
            <a href="{{ route('reports.bs.excel', ['as_of' => $asOf]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-emerald-300 text-emerald-700 text-sm font-medium rounded-md hover:bg-emerald-50">
                ⬇ Excel
            </a>
            <a href="{{ route('reports.bs.pdf', ['as_of' => $asOf]) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-red-300 text-red-700 text-sm font-medium rounded-md hover:bg-red-50">
                ⬇ PDF
            </a>
        </form>
    </div>

    {{-- Approximate notice --}}
    @if($report['is_approximate'])
    <div class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
        <span class="text-lg leading-none mt-0.5">ℹ</span>
        <div>
            <p class="font-semibold">Supplemented Balance Sheet</p>
            <p class="mt-0.5 text-xs">
                This report combines journal entries with data from your invoices, expenses, and payrolls.
                For a fully audited balance sheet, post all transactions as double-entry journal entries via
                <a href="{{ route('transactions.create') }}" class="underline font-medium">Transactions → New Entry</a>.
            </p>
        </div>
    </div>
    @endif

    {{-- Out-of-balance warning (only when gap > 1%) --}}
    @if(!$report['is_balanced'])
    @php
        $gap = abs($report['total_assets'] - $report['total_liabilities'] - $report['total_equity']);
    @endphp
    <div class="flex items-start gap-3 p-4 bg-orange-50 border border-orange-200 rounded-lg text-sm text-orange-800">
        <span class="text-lg leading-none mt-0.5">⚠</span>
        <div>
            <p class="font-semibold">Out of Balance — ₦{{ number_format($gap, 2) }} difference</p>
            <p class="mt-0.5 text-xs">
                Assets (₦{{ number_format($report['total_assets'], 2) }})
                ≠ Liabilities + Equity (₦{{ number_format($report['total_liabilities'] + $report['total_equity'], 2) }}).
                This typically means some expense payments have not been recorded against a bank account.
            </p>
        </div>
    </div>
    @endif

    {{-- Balance sheet body --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold">Balance Sheet</h2>
                <p class="text-sm text-gray-500">
                    As of {{ \Carbon\Carbon::parse($report['as_of'])->format('d F Y') }}
                    @if($report['is_balanced'])
                    &nbsp;<span class="text-green-600 text-xs font-semibold">✓ Balanced</span>
                    @endif
                </p>
            </div>
            {{-- Source legend --}}
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-indigo-400 inline-block"></span>Journal</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>Invoices</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-teal-400 inline-block"></span>Payments</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-orange-400 inline-block"></span>Expenses</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>Payroll</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-violet-400 inline-block"></span>Operations</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200">

            {{-- LEFT: Assets --}}
            <div class="p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-blue-700 mb-3">Assets</h3>
                @if(count($report['assets']) > 0)
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase">
                            <th class="py-1 text-left font-medium w-12">Code</th>
                            <th class="py-1 text-left font-medium">Account</th>
                            <th class="py-1 text-center font-medium w-16">Source</th>
                            <th class="py-1 text-right font-medium w-32">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($report['assets'] as $row)
                    @php
                        $src = $row['source'] ?? 'journal';
                        $dot = match($src) {
                            'payments'   => 'bg-teal-400',
                            'invoices'   => 'bg-green-400',
                            'expenses'   => 'bg-orange-400',
                            'payroll'    => 'bg-blue-400',
                            'operations' => 'bg-violet-400',
                            default      => 'bg-indigo-400',
                        };
                        $isNeg = $row['balance'] < 0;
                    @endphp
                    <tr class="hover:bg-gray-50 border-b border-gray-100 {{ $isNeg ? 'bg-red-50/40' : '' }}">
                        <td class="py-1.5 font-mono text-xs text-gray-400">{{ $row['code'] }}</td>
                        <td class="py-1.5 text-gray-700">
                            {{ $row['name'] }}
                            @if($isNeg)
                                <span class="ml-1 text-xs text-red-500 font-normal">(overdraft/contra)</span>
                            @endif
                        </td>
                        <td class="py-1.5 text-center">
                            <span class="inline-block w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
                        </td>
                        <td class="py-1.5 text-right font-medium {{ $isNeg ? 'text-red-600' : 'text-blue-700' }}">
                            ₦{{ number_format($row['balance'], 2) }}
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="border-t-2 border-blue-300 bg-blue-50 font-bold">
                        <td class="py-2 font-mono text-xs text-gray-400"></td>
                        <td class="py-2" colspan="2">Total Assets</td>
                        <td class="py-2 text-right text-blue-700">₦{{ number_format($report['total_assets'], 2) }}</td>
                    </tr>
                    </tfoot>
                </table>
                @else
                <p class="text-sm text-gray-400 italic">No asset balances yet. <a href="{{ route('transactions.create') }}" class="text-green-600 not-italic hover:underline">Post a journal entry</a> or record an invoice payment.</p>
                @endif
            </div>

            {{-- RIGHT: Liabilities + Equity --}}
            <div class="p-6 space-y-6">

                {{-- Liabilities --}}
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-red-700 mb-3">Liabilities</h3>
                    @if(count($report['liabilities']) > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase">
                                <th class="py-1 text-left font-medium w-12">Code</th>
                                <th class="py-1 text-left font-medium">Account</th>
                                <th class="py-1 text-center font-medium w-16">Source</th>
                                <th class="py-1 text-right font-medium w-32">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($report['liabilities'] as $row)
                        @php
                            $src = $row['source'] ?? 'journal';
                            $dot = match($src) {
                                'payments'   => 'bg-teal-400',
                                'invoices'   => 'bg-green-400',
                                'expenses'   => 'bg-orange-400',
                                'payroll'    => 'bg-blue-400',
                                'operations' => 'bg-violet-400',
                                default      => 'bg-indigo-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="py-1.5 font-mono text-xs text-gray-400">{{ $row['code'] }}</td>
                            <td class="py-1.5 text-gray-700">{{ $row['name'] }}</td>
                            <td class="py-1.5 text-center">
                                <span class="inline-block w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
                            </td>
                            <td class="py-1.5 text-right font-medium text-red-600">₦{{ number_format($row['balance'], 2) }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr class="border-t-2 border-red-200 bg-red-50 font-bold">
                            <td class="py-2 font-mono text-xs text-gray-400"></td>
                            <td class="py-2" colspan="2">Total Liabilities</td>
                            <td class="py-2 text-right text-red-600">₦{{ number_format($report['total_liabilities'], 2) }}</td>
                        </tr>
                        </tfoot>
                    </table>
                    @else
                    <p class="text-sm text-gray-400 italic">No liabilities recorded.</p>
                    @endif
                </div>

                {{-- Equity --}}
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-green-700 mb-3">Equity</h3>
                    @if(count($report['equity']) > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase">
                                <th class="py-1 text-left font-medium w-12">Code</th>
                                <th class="py-1 text-left font-medium">Account</th>
                                <th class="py-1 text-center font-medium w-16">Source</th>
                                <th class="py-1 text-right font-medium w-32">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($report['equity'] as $row)
                        @php
                            $src = $row['source'] ?? 'journal';
                            $dot = match($src) {
                                'operations' => 'bg-violet-400',
                                'invoices'   => 'bg-green-400',
                                default      => 'bg-indigo-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="py-1.5 font-mono text-xs text-gray-400">{{ $row['code'] }}</td>
                            <td class="py-1.5 text-gray-700">{{ $row['name'] }}</td>
                            <td class="py-1.5 text-center">
                                <span class="inline-block w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
                            </td>
                            <td class="py-1.5 text-right font-medium text-green-700">₦{{ number_format($row['balance'], 2) }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr class="border-t-2 border-green-200 bg-green-50 font-bold">
                            <td class="py-2 font-mono text-xs text-gray-400"></td>
                            <td class="py-2" colspan="2">Total Equity</td>
                            <td class="py-2 text-right text-green-700">₦{{ number_format($report['total_equity'], 2) }}</td>
                        </tr>
                        </tfoot>
                    </table>
                    @else
                    <p class="text-sm text-gray-400 italic">No equity recorded.</p>
                    @endif

                    {{-- Total Liabilities + Equity --}}
                    <div class="mt-4 pt-3 border-t-2 border-gray-400 flex justify-between items-center font-bold text-sm">
                        <span>Total Liabilities + Equity</span>
                        <span class="text-lg">₦{{ number_format($report['total_liabilities'] + $report['total_equity'], 2) }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
