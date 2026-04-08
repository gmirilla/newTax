<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; }

    .header { padding: 12px 16px 8px; border-bottom: 3px solid #008751; margin-bottom: 8px; }
    .company { font-size: 13px; font-weight: bold; color: #008751; }
    .title   { font-size: 11px; font-weight: bold; margin-top: 2px; }
    .meta    { font-size: 8px; color: #6b7280; margin-top: 3px; }

    .summary { background: #f0fdf4; border: 1px solid #86efac; border-radius: 4px;
               padding: 5px 10px; margin-bottom: 10px;
               display: flex; gap: 20px; font-size: 8px; }
    .summary .lbl { color: #6b7280; }
    .summary .val { font-weight: bold; }
    .summary .val.dr { color: #1d4ed8; }
    .summary .val.cr { color: #b91c1c; }
    .summary .val.ok { color: #15803d; }
    .summary .val.warn { color: #c2410c; }

    .filter-badges { margin-bottom: 8px; font-size: 7.5px; color: #6b7280; }
    .filter-badges span { background: #e5e7eb; padding: 1px 6px; border-radius: 8px; margin-right: 4px; }

    .account-block { margin-bottom: 12px; page-break-inside: avoid; }

    .account-header {
        padding: 4px 8px; background: #1f2937; color: #ffffff;
        display: flex; justify-content: space-between; align-items: center;
    }
    .acct-left  { display: flex; align-items: center; gap: 6px; }
    .acct-code  { font-family: monospace; font-size: 8px; color: #9ca3af; }
    .acct-name  { font-weight: bold; font-size: 9px; }
    .acct-type  { font-size: 7px; padding: 1px 5px; border-radius: 8px; background: rgba(255,255,255,0.15); }
    .acct-right { font-size: 7.5px; color: #d1fae5; display: flex; gap: 12px; }

    table { width: 100%; border-collapse: collapse; font-size: 8px; }
    thead th {
        background: #f3f4f6; color: #6b7280; font-size: 7px;
        text-transform: uppercase; letter-spacing: .3px;
        padding: 3px 5px; text-align: left; border-bottom: 1px solid #e5e7eb;
    }
    th.r, td.r { text-align: right; }

    tr.data td { padding: 2.5px 5px; border-bottom: 1px solid #f3f4f6; }
    tr.data:nth-child(even) td { background: #fafafa; }
    tr.obal td  { background: #f9fafb; font-style: italic; color: #6b7280; padding: 2.5px 5px; }
    tr.totrow td { background: #f3f4f6; font-style: italic; color: #374151; padding: 2px 5px; font-size: 7.5px; }
    tr.cbal td  { background: #f0fdf4; font-weight: bold; color: #166534;
                  border-top: 1.5px solid #86efac; padding: 2.5px 5px; }

    .debit  { color: #1d4ed8; }
    .credit { color: #b91c1c; }
    .bal    { color: #111827; font-weight: 600; }
    .neg    { color: #dc2626; }

    .footer { position: fixed; bottom: 0; left: 0; right: 0;
              padding: 4px 16px; border-top: 1px solid #e5e7eb;
              font-size: 7px; color: #9ca3af;
              display: flex; justify-content: space-between; }
    .empty { color: #9ca3af; font-style: italic; text-align: center; padding: 20px; }
</style>
</head>
<body>

<div class="header">
    <div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
    <div class="title">General Ledger</div>
    <div class="meta">
        Period: {{ \Carbon\Carbon::parse($report['period_start'])->format('d M Y') }}
        to {{ \Carbon\Carbon::parse($report['period_end'])->format('d M Y') }}
        &nbsp;·&nbsp; TIN: {{ $tenant->tin ?? 'N/A' }}
        &nbsp;·&nbsp; Generated: {{ now()->format('d M Y H:i') }}
    </div>
</div>

{{-- Active filters --}}
@if($report['account_code'] || $report['account_type'] || $report['search'])
<div class="filter-badges">
    Filters:
    @if($report['account_type'])<span>Type: {{ ucfirst($report['account_type']) }}</span>@endif
    @if($report['account_code'])<span>Account: {{ $report['account_code'] }}</span>@endif
    @if($report['search'])<span>Search: "{{ $report['search'] }}"</span>@endif
</div>
@endif

{{-- Summary --}}
@if(count($report['accounts']) > 0)
<div class="summary">
    <div><span class="lbl">Accounts shown: </span><span class="val">{{ count($report['accounts']) }}</span></div>
    <div><span class="lbl">Period debits: </span><span class="val dr">₦{{ number_format($report['total_debits'], 2) }}</span></div>
    <div><span class="lbl">Period credits: </span><span class="val cr">₦{{ number_format($report['total_credits'], 2) }}</span></div>
    @php $balanced = round($report['total_debits'], 2) === round($report['total_credits'], 2); @endphp
    <div><span class="lbl">Balance check: </span>
        <span class="val {{ $balanced ? 'ok' : 'warn' }}">{{ $balanced ? '✓ Balanced' : '⚠ Difference: ₦' . number_format(abs($report['total_debits'] - $report['total_credits']), 2) }}</span>
    </div>
</div>
@endif

@forelse($report['accounts'] as $acct)
<div class="account-block">
    <div class="account-header">
        <div class="acct-left">
            <span class="acct-code">{{ $acct['code'] }}</span>
            <span class="acct-name">{{ $acct['name'] }}</span>
            <span class="acct-type">{{ ucfirst($acct['type']) }}</span>
        </div>
        <div class="acct-right">
            <span>Opening: ₦{{ number_format($acct['opening_balance'], 2) }}</span>
            @if(count($acct['lines']) > 0)
            <span>DR: <span class="debit">₦{{ number_format($acct['period_debits'], 2) }}</span></span>
            <span>CR: <span class="credit">₦{{ number_format($acct['period_credits'], 2) }}</span></span>
            @endif
            <span>Closing: ₦{{ number_format($acct['closing_balance'], 2) }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:66px">Date</th>
                <th style="width:88px">Reference</th>
                <th>Description</th>
                <th class="r" style="width:76px">Debit (₦)</th>
                <th class="r" style="width:76px">Credit (₦)</th>
                <th class="r" style="width:86px">Balance (₦)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="obal">
                <td colspan="2">Opening Balance</td>
                <td>Balance brought forward</td>
                <td class="r"></td>
                <td class="r"></td>
                <td class="r {{ $acct['opening_balance'] < 0 ? 'neg' : 'bal' }}">
                    ₦{{ number_format($acct['opening_balance'], 2) }}
                </td>
            </tr>

            @forelse($acct['lines'] as $line)
            <tr class="data">
                <td>{{ \Carbon\Carbon::parse($line['date'])->format('d/m/Y') }}</td>
                <td style="font-family:monospace; font-size:7.5px">{{ $line['reference'] }}</td>
                <td>{{ \Illuminate\Support\Str::limit($line['description'], 72) }}</td>
                <td class="r debit">@if($line['debit'] !== null)₦{{ number_format($line['debit'], 2) }}@endif</td>
                <td class="r credit">@if($line['credit'] !== null)₦{{ number_format($line['credit'], 2) }}@endif</td>
                <td class="r {{ $line['balance'] < 0 ? 'neg' : 'bal' }}">₦{{ number_format($line['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="empty">No entries in this period.</td></tr>
            @endforelse

            @if(count($acct['lines']) > 0)
            <tr class="totrow">
                <td colspan="2"></td>
                <td>Period totals</td>
                <td class="r debit">₦{{ number_format($acct['period_debits'], 2) }}</td>
                <td class="r credit">₦{{ number_format($acct['period_credits'], 2) }}</td>
                <td class="r">{{ $acct['net_movement'] >= 0 ? '+' : '' }}₦{{ number_format($acct['net_movement'], 2) }}</td>
            </tr>
            @endif

            <tr class="cbal">
                <td colspan="2">Closing Balance</td>
                <td>Balance carried forward</td>
                <td class="r"></td>
                <td class="r"></td>
                <td class="r">₦{{ number_format($acct['closing_balance'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@empty
<p class="empty">No accounts found for the selected filters.</p>
@endforelse

<div class="footer">
    <span>{{ $tenant->company_name ?? $tenant->name }} · TIN: {{ $tenant->tin ?? 'N/A' }}</span>
    <span>General Ledger — Confidential</span>
    <span>{{ now()->format('d M Y H:i') }}</span>
</div>

</body>
</html>
