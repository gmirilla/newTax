@extends('layouts.app')

@section('page-title', 'General Ledger')

@section('content')
<div class="space-y-6">

    {{-- Controls --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('reports.ledger') }}" class="flex flex-wrap gap-3 items-end">

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
                <input type="date" name="date_from" value="{{ $report['period_start'] }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                <input type="date" name="date_to" value="{{ $report['period_end'] }}"
                       class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Account (optional)</label>
                <select name="account_code"
                        class="rounded-md border-gray-300 text-sm shadow-sm px-3 py-1.5 focus:ring-green-500 focus:border-green-500">
                    <option value="">— All Accounts —</option>
                    @foreach($accounts as $acct)
                        <option value="{{ $acct->code }}"
                            {{ $report['account_code'] === $acct->code ? 'selected' : '' }}>
                            {{ $acct->code }} – {{ $acct->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white text-sm rounded-md self-end">
                Apply
            </button>

            <div class="flex-1"></div>

            {{-- Export buttons --}}
            @php
                $qp = array_filter([
                    'date_from'    => $report['period_start'],
                    'date_to'      => $report['period_end'],
                    'account_code' => $report['account_code'],
                ]);
            @endphp
            <a href="{{ route('reports.ledger.excel', $qp) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-emerald-300 text-emerald-700 text-sm font-medium rounded-md hover:bg-emerald-50 self-end">
                ⬇ Excel
            </a>
            <a href="{{ route('reports.ledger.pdf', $qp) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-red-300 text-red-700 text-sm font-medium rounded-md hover:bg-red-50 self-end">
                ⬇ PDF
            </a>
        </form>
    </div>

    {{-- Period summary --}}
    <div class="flex items-center justify-between text-sm text-gray-500">
        <span>
            Period:
            <strong class="text-gray-700">{{ \Carbon\Carbon::parse($report['period_start'])->format('d M Y') }}</strong>
            to
            <strong class="text-gray-700">{{ \Carbon\Carbon::parse($report['period_end'])->format('d M Y') }}</strong>
            @if($report['account_code'])
                &mdash; Account <span class="font-mono">{{ $report['account_code'] }}</span>
            @endif
        </span>
        <span class="text-xs text-gray-400">{{ count($report['accounts']) }} account(s) with activity</span>
    </div>

    @forelse($report['accounts'] as $acct)
    @php
        $typeColor = match($acct['type']) {
            'asset'     => ['header' => 'bg-blue-700',   'badge' => 'bg-blue-100 text-blue-800'],
            'liability' => ['header' => 'bg-red-700',    'badge' => 'bg-red-100 text-red-800'],
            'equity'    => ['header' => 'bg-green-700',  'badge' => 'bg-green-100 text-green-800'],
            'revenue'   => ['header' => 'bg-teal-700',   'badge' => 'bg-teal-100 text-teal-800'],
            'expense'   => ['header' => 'bg-orange-700', 'badge' => 'bg-orange-100 text-orange-800'],
            default     => ['header' => 'bg-gray-700',   'badge' => 'bg-gray-100 text-gray-800'],
        };
    @endphp
    <div class="bg-white rounded-lg shadow overflow-hidden">
        {{-- Account header --}}
        <div class="px-5 py-3 {{ $typeColor['header'] }} flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="font-mono text-white/70 text-sm">{{ $acct['code'] }}</span>
                <span class="font-semibold text-white">{{ $acct['name'] }}</span>
                <span class="text-xs px-1.5 py-0.5 rounded {{ $typeColor['badge'] }} font-medium">
                    {{ ucfirst($acct['type']) }}
                </span>
            </div>
            <div class="text-xs text-white/80 flex items-center gap-4">
                <span>Opening: <strong>₦{{ number_format($acct['opening_balance'], 2) }}</strong></span>
                <span>Closing: <strong>₦{{ number_format($acct['closing_balance'], 2) }}</strong></span>
            </div>
        </div>

        {{-- Entries table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-100">
                <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                    <tr>
                        <th class="px-4 py-2 text-left w-28 font-medium">Date</th>
                        <th class="px-4 py-2 text-left w-36 font-medium">Reference</th>
                        <th class="px-4 py-2 text-left font-medium">Description</th>
                        <th class="px-4 py-2 text-right w-32 font-medium">Debit (₦)</th>
                        <th class="px-4 py-2 text-right w-32 font-medium">Credit (₦)</th>
                        <th class="px-4 py-2 text-right w-36 font-medium">Balance (₦)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    {{-- Opening balance row --}}
                    <tr class="bg-gray-50 text-gray-500 italic text-xs">
                        <td class="px-4 py-1.5" colspan="2">Opening Balance</td>
                        <td class="px-4 py-1.5">Balance brought forward</td>
                        <td class="px-4 py-1.5 text-right"></td>
                        <td class="px-4 py-1.5 text-right"></td>
                        <td class="px-4 py-1.5 text-right font-medium not-italic text-gray-700">
                            ₦{{ number_format($acct['opening_balance'], 2) }}
                        </td>
                    </tr>

                    @foreach($acct['lines'] as $line)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1.5 text-gray-500 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($line['date'])->format('d M Y') }}
                        </td>
                        <td class="px-4 py-1.5 font-mono text-xs text-gray-500 whitespace-nowrap">
                            {{ $line['reference'] }}
                        </td>
                        <td class="px-4 py-1.5 text-gray-700 max-w-xs truncate" title="{{ $line['description'] }}">
                            {{ $line['description'] }}
                        </td>
                        <td class="px-4 py-1.5 text-right text-blue-700">
                            @if($line['debit'] !== null)
                                ₦{{ number_format($line['debit'], 2) }}
                            @endif
                        </td>
                        <td class="px-4 py-1.5 text-right text-red-600">
                            @if($line['credit'] !== null)
                                ₦{{ number_format($line['credit'], 2) }}
                            @endif
                        </td>
                        <td class="px-4 py-1.5 text-right font-medium
                            {{ $line['balance'] >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                            ₦{{ number_format($line['balance'], 2) }}
                        </td>
                    </tr>
                    @endforeach

                    {{-- Closing balance row --}}
                    <tr class="bg-gray-50 border-t-2 border-gray-300 font-semibold text-xs">
                        <td class="px-4 py-2" colspan="2">Closing Balance</td>
                        <td class="px-4 py-2">Balance carried forward</td>
                        <td class="px-4 py-2 text-right"></td>
                        <td class="px-4 py-2 text-right"></td>
                        <td class="px-4 py-2 text-right {{ $typeColor['header'] }} text-white rounded-sm">
                            ₦{{ number_format($acct['closing_balance'], 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-400">
        <p class="text-base">No journal entries found for the selected period.</p>
        <p class="text-sm mt-1">
            Adjust the date range, or
            <a href="{{ route('transactions.create') }}" class="text-green-600 hover:underline">post a journal entry</a>.
        </p>
    </div>
    @endforelse

</div>
@endsection
