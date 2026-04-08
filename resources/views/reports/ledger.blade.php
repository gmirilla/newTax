@extends('layouts.app')

@section('page-title', 'General Ledger')

@push('styles')
<style>
/* ── Print styles ──────────────────────────────────────────────────── */
@media print {
    /* Hide everything except the ledger content */
    .no-print { display: none !important; }
    .print-only { display: block !important; }

    body { font-size: 10px; color: #000; background: #fff; }
    .page-wrapper { padding: 0; margin: 0; }

    .account-card { break-inside: avoid; box-shadow: none !important; border: 1px solid #ccc; margin-bottom: 12px; }
    .account-card-body { display: block !important; } /* override Alpine collapsed state */

    table { width: 100%; border-collapse: collapse; font-size: 9px; }
    th, td { padding: 3px 5px !important; border-bottom: 1px solid #e5e7eb; }
    thead th { background: #f3f4f6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .summary-bar { background: #f0fdf4 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    /* Force account headers to print with background */
    .acct-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    @page { size: A4 landscape; margin: 10mm 12mm; }
}
.print-only { display: none; }
</style>
@endpush

@section('content')
<div class="space-y-4 page-wrapper"
     x-data="{ open: Array({{ count($report['accounts']) }}).fill(true), allExpanded: true,
                collapseAll() { this.open = this.open.map(() => false); this.allExpanded = false; },
                expandAll()   { this.open = this.open.map(() => true);  this.allExpanded = true; } }">

    {{-- ── Filter bar ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-lg shadow p-4 no-print">
        <form method="GET" action="{{ route('reports.ledger') }}" id="filter-form">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">

                {{-- Date From --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
                    <input type="date" name="date_from" value="{{ $report['period_start'] }}"
                           class="w-full rounded-md border-gray-300 text-sm shadow-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                    <input type="date" name="date_to" value="{{ $report['period_end'] }}"
                           class="w-full rounded-md border-gray-300 text-sm shadow-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                </div>

                {{-- Account Type --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Account Type</label>
                    <select name="account_type"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                        <option value="">All Types</option>
                        @foreach(['asset','liability','equity','revenue','expense'] as $t)
                            <option value="{{ $t }}" {{ $report['account_type'] === $t ? 'selected' : '' }}>
                                {{ ucfirst($t) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Specific Account --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Specific Account</label>
                    <select name="account_code"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                        <option value="">— All Accounts —</option>
                        @foreach($accounts as $acct)
                            <option value="{{ $acct->code }}"
                                {{ $report['account_code'] === $acct->code ? 'selected' : '' }}>
                                {{ $acct->code }} – {{ $acct->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Search --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Search Account</label>
                    <input type="text" name="search" value="{{ $report['search'] }}"
                           placeholder="Code or name…"
                           class="w-full rounded-md border-gray-300 text-sm shadow-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                </div>

                {{-- Actions --}}
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="flex-1 px-3 py-1.5 bg-gray-700 text-white text-sm rounded-md hover:bg-gray-800">
                        Apply
                    </button>
                    @if($report['account_code'] || $report['account_type'] || $report['search'])
                        <a href="{{ route('reports.ledger', ['date_from' => $report['period_start'], 'date_to' => $report['period_end']]) }}"
                           class="px-2 py-1.5 text-xs text-gray-500 border border-gray-200 rounded-md hover:bg-gray-50">
                            Clear
                        </a>
                    @endif
                </div>
            </div>

            {{-- Export + Print row --}}
            @php
                $qp = array_filter([
                    'date_from'    => $report['period_start'],
                    'date_to'      => $report['period_end'],
                    'account_code' => $report['account_code'],
                    'account_type' => $report['account_type'],
                    'search'       => $report['search'],
                ]);
            @endphp
            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100">
                <span class="text-xs text-gray-400">Export current view:</span>
                <a href="{{ route('reports.ledger.excel', $qp) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 border border-emerald-300 text-emerald-700 text-sm font-medium rounded-md hover:bg-emerald-50">
                    ⬇ Excel
                </a>
                <a href="{{ route('reports.ledger.pdf', $qp) }}" target="_blank"
                   class="inline-flex items-center gap-1 px-3 py-1.5 border border-red-300 text-red-700 text-sm font-medium rounded-md hover:bg-red-50">
                    ⬇ PDF
                </a>
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-1 px-3 py-1.5 border border-gray-300 text-gray-600 text-sm font-medium rounded-md hover:bg-gray-50">
                    🖨 Print
                </button>
                <div class="flex-1"></div>
                <button type="button" @click="collapseAll()"
                        class="text-xs text-gray-400 hover:text-gray-600 underline">
                    Collapse all
                </button>
                <span class="text-gray-300">|</span>
                <button type="button" @click="expandAll()"
                        class="text-xs text-gray-400 hover:text-gray-600 underline">
                    Expand all
                </button>
            </div>
        </form>
    </div>

    {{-- ── Print header (only visible when printing) ───────────────────────── --}}
    <div class="print-only mb-4">
        <div style="border-bottom: 3px solid #008751; padding-bottom: 8px; margin-bottom: 12px;">
            <div style="font-size: 16px; font-weight: bold; color: #008751;">
                {{ auth()->user()->tenant->company_name ?? auth()->user()->tenant->name }}
            </div>
            <div style="font-size: 13px; font-weight: bold; margin-top: 2px;">General Ledger</div>
            <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">
                Period: {{ \Carbon\Carbon::parse($report['period_start'])->format('d M Y') }}
                to {{ \Carbon\Carbon::parse($report['period_end'])->format('d M Y') }}
                @if($report['account_type']) · Type: {{ ucfirst($report['account_type']) }} @endif
                @if($report['account_code']) · Account: {{ $report['account_code'] }} @endif
                @if($report['search']) · Search: "{{ $report['search'] }}" @endif
                · Generated: {{ now()->format('d M Y H:i') }}
            </div>
        </div>
    </div>

    {{-- ── Summary bar ─────────────────────────────────────────────────────── --}}
    @if(count($report['accounts']) > 0)
    <div class="summary-bar bg-white rounded-lg shadow p-4">
        <div class="flex flex-wrap items-center gap-6 text-sm">
            <div>
                <span class="text-gray-500 text-xs uppercase font-medium">Period</span>
                <p class="font-semibold text-gray-800 mt-0.5">
                    {{ \Carbon\Carbon::parse($report['period_start'])->format('d M Y') }}
                    — {{ \Carbon\Carbon::parse($report['period_end'])->format('d M Y') }}
                </p>
            </div>
            <div class="h-8 w-px bg-gray-200"></div>
            <div>
                <span class="text-gray-500 text-xs uppercase font-medium">Accounts Shown</span>
                <p class="font-semibold text-gray-800 mt-0.5">{{ count($report['accounts']) }}</p>
            </div>
            <div class="h-8 w-px bg-gray-200"></div>
            <div>
                <span class="text-gray-500 text-xs uppercase font-medium">Period Total Debits</span>
                <p class="font-semibold text-blue-700 mt-0.5">₦{{ number_format($report['total_debits'], 2) }}</p>
            </div>
            <div class="h-8 w-px bg-gray-200"></div>
            <div>
                <span class="text-gray-500 text-xs uppercase font-medium">Period Total Credits</span>
                <p class="font-semibold text-red-600 mt-0.5">₦{{ number_format($report['total_credits'], 2) }}</p>
            </div>
            <div class="h-8 w-px bg-gray-200"></div>
            <div>
                <span class="text-gray-500 text-xs uppercase font-medium">Balance Check</span>
                @php $balanced = round($report['total_debits'], 2) === round($report['total_credits'], 2); @endphp
                <p class="font-semibold mt-0.5 {{ $balanced ? 'text-green-600' : 'text-orange-600' }}">
                    {{ $balanced ? '✓ Balanced' : '⚠ ' . number_format(abs($report['total_debits'] - $report['total_credits']), 2) . ' difference' }}
                </p>
            </div>
            @if($report['account_type'] || $report['account_code'] || $report['search'])
            <div class="h-8 w-px bg-gray-200"></div>
            <div>
                <span class="text-gray-500 text-xs uppercase font-medium">Filter</span>
                <p class="text-xs text-gray-600 mt-0.5">
                    @if($report['account_type']) <span class="bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">{{ ucfirst($report['account_type']) }}</span> @endif
                    @if($report['account_code']) <span class="bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-mono">{{ $report['account_code'] }}</span> @endif
                    @if($report['search']) <span class="bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded">"{{ $report['search'] }}"</span> @endif
                </p>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ── Account cards ────────────────────────────────────────────────────── --}}
    @forelse($report['accounts'] as $idx => $acct)
    @php
        $typeColor = match($acct['type']) {
            'asset'     => ['bg' => 'bg-blue-700',   'light' => 'bg-blue-50',   'text' => 'text-blue-700',   'badge' => 'bg-blue-100 text-blue-800',   'border' => 'border-blue-300'],
            'liability' => ['bg' => 'bg-red-700',    'light' => 'bg-red-50',    'text' => 'text-red-700',    'badge' => 'bg-red-100 text-red-800',     'border' => 'border-red-300'],
            'equity'    => ['bg' => 'bg-green-700',  'light' => 'bg-green-50',  'text' => 'text-green-700',  'badge' => 'bg-green-100 text-green-800', 'border' => 'border-green-300'],
            'revenue'   => ['bg' => 'bg-teal-700',   'light' => 'bg-teal-50',   'text' => 'text-teal-700',   'badge' => 'bg-teal-100 text-teal-800',   'border' => 'border-teal-300'],
            'expense'   => ['bg' => 'bg-orange-700', 'light' => 'bg-orange-50', 'text' => 'text-orange-700', 'badge' => 'bg-orange-100 text-orange-800','border' => 'border-orange-300'],
            default     => ['bg' => 'bg-gray-700',   'light' => 'bg-gray-50',   'text' => 'text-gray-700',   'badge' => 'bg-gray-100 text-gray-800',   'border' => 'border-gray-300'],
        };
    @endphp
    <div class="account-card bg-white rounded-lg shadow overflow-hidden"
         :class="{ 'opacity-75': !open[{{ $idx }}] }">

        {{-- Account header (clickable to collapse) --}}
        <div class="acct-header px-5 py-3 {{ $typeColor['bg'] }} cursor-pointer select-none"
             @click="open[{{ $idx }}] = !open[{{ $idx }}]">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="font-mono text-white/60 text-sm">{{ $acct['code'] }}</span>
                    <span class="font-semibold text-white">{{ $acct['name'] }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $typeColor['badge'] }} font-medium">
                        {{ ucfirst($acct['type']) }}
                    </span>
                    @if(count($acct['lines']) === 0)
                        <span class="text-xs text-white/50 italic">no entries this period</span>
                    @else
                        <span class="text-xs text-white/60">{{ count($acct['lines']) }} entries</span>
                    @endif
                </div>
                <div class="flex items-center gap-6 text-xs text-white/80">
                    <span>Opening: <strong class="text-white">₦{{ number_format($acct['opening_balance'], 2) }}</strong></span>
                    @if(count($acct['lines']) > 0)
                    <span>DR <strong class="text-white">₦{{ number_format($acct['period_debits'], 2) }}</strong></span>
                    <span>CR <strong class="text-white">₦{{ number_format($acct['period_credits'], 2) }}</strong></span>
                    @endif
                    <span>Closing: <strong class="text-white">₦{{ number_format($acct['closing_balance'], 2) }}</strong></span>
                    <span x-text="open[{{ $idx }}] ? '▲' : '▼'" class="text-white/50 text-xs ml-2"></span>
                </div>
            </div>
        </div>

        {{-- Entries table (collapsible) --}}
        <div class="account-card-body overflow-x-auto" x-show="open[{{ $idx }}]"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0">
            <table class="min-w-full text-sm divide-y divide-gray-100">
                <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium w-28">Date</th>
                        <th class="px-4 py-2 text-left font-medium w-36">Reference</th>
                        <th class="px-4 py-2 text-left font-medium">Description</th>
                        <th class="px-4 py-2 text-right font-medium w-32">Debit (₦)</th>
                        <th class="px-4 py-2 text-right font-medium w-32">Credit (₦)</th>
                        <th class="px-4 py-2 text-right font-medium w-36">Balance (₦)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    {{-- Opening balance row --}}
                    <tr class="bg-gray-50 text-gray-500 italic text-xs">
                        <td class="px-4 py-1.5" colspan="2">Opening Balance</td>
                        <td class="px-4 py-1.5">Balance brought forward</td>
                        <td class="px-4 py-1.5"></td>
                        <td class="px-4 py-1.5"></td>
                        <td class="px-4 py-1.5 text-right font-semibold not-italic
                            {{ $acct['opening_balance'] < 0 ? 'text-red-600' : 'text-gray-700' }}">
                            ₦{{ number_format($acct['opening_balance'], 2) }}
                        </td>
                    </tr>

                    @forelse($acct['lines'] as $line)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1.5 text-gray-500 whitespace-nowrap text-xs">
                            {{ \Carbon\Carbon::parse($line['date'])->format('d M Y') }}
                        </td>
                        <td class="px-4 py-1.5 font-mono text-xs text-gray-400 whitespace-nowrap">
                            {{ $line['reference'] }}
                        </td>
                        <td class="px-4 py-1.5 text-gray-700 max-w-xs truncate" title="{{ $line['description'] }}">
                            {{ $line['description'] }}
                        </td>
                        <td class="px-4 py-1.5 text-right font-medium text-blue-700">
                            @if($line['debit'] !== null)₦{{ number_format($line['debit'], 2) }}@endif
                        </td>
                        <td class="px-4 py-1.5 text-right font-medium text-red-600">
                            @if($line['credit'] !== null)₦{{ number_format($line['credit'], 2) }}@endif
                        </td>
                        <td class="px-4 py-1.5 text-right font-semibold
                            {{ $line['balance'] < 0 ? 'text-red-600' : 'text-gray-800' }}">
                            ₦{{ number_format($line['balance'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr class="text-xs italic text-gray-400">
                        <td class="px-4 py-2" colspan="6">No entries in this period.</td>
                    </tr>
                    @endforelse

                    {{-- Period subtotal row (only when there are entries) --}}
                    @if(count($acct['lines']) > 0)
                    <tr class="bg-gray-50 text-xs font-medium text-gray-500 border-t border-gray-200">
                        <td class="px-4 py-1.5" colspan="2"></td>
                        <td class="px-4 py-1.5 italic">Period totals</td>
                        <td class="px-4 py-1.5 text-right text-blue-700">₦{{ number_format($acct['period_debits'], 2) }}</td>
                        <td class="px-4 py-1.5 text-right text-red-600">₦{{ number_format($acct['period_credits'], 2) }}</td>
                        <td class="px-4 py-1.5 text-right text-gray-500">
                            {{ $acct['net_movement'] >= 0 ? '+' : '' }}₦{{ number_format($acct['net_movement'], 2) }}
                        </td>
                    </tr>
                    @endif

                    {{-- Closing balance row --}}
                    <tr class="{{ $typeColor['light'] }} border-t-2 {{ $typeColor['border'] }} font-semibold text-sm">
                        <td class="px-4 py-2" colspan="2">Closing Balance</td>
                        <td class="px-4 py-2 text-xs text-gray-500 font-normal">Balance carried forward</td>
                        <td class="px-4 py-2"></td>
                        <td class="px-4 py-2"></td>
                        <td class="px-4 py-2 text-right {{ $typeColor['text'] }}">
                            ₦{{ number_format($acct['closing_balance'], 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-400">
        <p class="text-base font-medium">No accounts found for the selected filters.</p>
        <p class="text-sm mt-1">
            Try adjusting the date range or clearing the account filter.
            Or <a href="{{ route('transactions.create') }}" class="text-green-600 hover:underline">post a journal entry</a>.
        </p>
    </div>
    @endforelse

</div>

@endsection
