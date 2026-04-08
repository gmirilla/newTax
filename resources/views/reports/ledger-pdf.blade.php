<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; }

    .header { padding: 12px 16px 8px; border-bottom: 3px solid #008751; margin-bottom: 10px; }
    .company { font-size: 13px; font-weight: bold; color: #008751; }
    .title   { font-size: 11px; font-weight: bold; margin-top: 2px; }
    .meta    { font-size: 8px; color: #6b7280; margin-top: 3px; }

    .account-block { margin-bottom: 14px; page-break-inside: avoid; }

    .account-header {
        padding: 4px 8px; background: #1f2937; color: #ffffff;
        display: flex; justify-content: space-between; align-items: center;
        border-radius: 3px 3px 0 0;
    }
    .account-header .code  { font-family: monospace; font-size: 8px; color: #9ca3af; margin-right: 6px; }
    .account-header .name  { font-weight: bold; font-size: 9px; }
    .account-header .type  { font-size: 7px; padding: 1px 5px; border-radius: 8px; background: rgba(255,255,255,0.15); }
    .account-header .balances { font-size: 7.5px; color: #d1fae5; }

    table { width: 100%; border-collapse: collapse; font-size: 8px; }
    thead th {
        background: #f3f4f6; color: #6b7280; font-size: 7px;
        text-transform: uppercase; letter-spacing: .3px;
        padding: 3px 5px; text-align: left; border-bottom: 1px solid #e5e7eb;
    }
    th.r, td.r { text-align: right; }

    tr.data td { padding: 2.5px 5px; border-bottom: 1px solid #f3f4f6; }
    tr.data:nth-child(even) td { background: #fafafa; }

    tr.obal td, tr.cbaltd { background: #f9fafb; font-style: italic; color: #6b7280; padding: 2.5px 5px; }
    tr.cbal td { background: #f0fdf4; font-weight: bold; font-style: normal; color: #166534;
                 border-top: 1.5px solid #86efac; padding: 2.5px 5px; }

    .debit  { color: #1d4ed8; }
    .credit { color: #b91c1c; }
    .bal    { color: #111827; font-weight: 600; }
    .neg    { color: #dc2626; }

    .footer { position: fixed; bottom: 0; left: 0; right: 0;
              padding: 4px 16px; border-top: 1px solid #e5e7eb;
              font-size: 7px; color: #9ca3af;
              display: flex; justify-content: space-between; }

    .empty { color: #9ca3af; font-style: italic; text-align: center; padding: 10px; }
</style>
</head>
<body>

<div class="header">
    <div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
    <div class="title">General Ledger</div>
    <div class="meta">
        Period: {{ \Carbon\Carbon::parse($report['period_start'])->format('d M Y') }}
        to {{ \Carbon\Carbon::parse($report['period_end'])->format('d M Y') }}
        @if($report['account_code'])
            &mdash; Account: {{ $report['account_code'] }}
        @endif
        &nbsp;&nbsp;|&nbsp;&nbsp; TIN: {{ $tenant->tin ?? 'N/A' }}
        &nbsp;&nbsp;|&nbsp;&nbsp; Generated: {{ now()->format('d M Y H:i') }}
    </div>
</div>

@forelse($report['accounts'] as $acct)
<div class="account-block">
    <div class="account-header">
        <div>
            <span class="code">{{ $acct['code'] }}</span>
            <span class="name">{{ $acct['name'] }}</span>
            <span class="type">{{ ucfirst($acct['type']) }}</span>
        </div>
        <div class="balances">
            Opening: ₦{{ number_format($acct['opening_balance'], 2) }}
            &nbsp;&nbsp;
            Closing: ₦{{ number_format($acct['closing_balance'], 2) }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:70px">Date</th>
                <th style="width:90px">Reference</th>
                <th>Description</th>
                <th class="r" style="width:80px">Debit (₦)</th>
                <th class="r" style="width:80px">Credit (₦)</th>
                <th class="r" style="width:90px">Balance (₦)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="obal">
                <td colspan="2">Opening Balance</td>
                <td>Balance brought forward</td>
                <td class="r"></td>
                <td class="r"></td>
                <td class="r bal">₦{{ number_format($acct['opening_balance'], 2) }}</td>
            </tr>

            @foreach($acct['lines'] as $line)
            <tr class="data">
                <td>{{ \Carbon\Carbon::parse($line['date'])->format('d/m/Y') }}</td>
                <td style="font-family:monospace; font-size:7.5px">{{ $line['reference'] }}</td>
                <td>{{ \Illuminate\Support\Str::limit($line['description'], 70) }}</td>
                <td class="r debit">
                    @if($line['debit'] !== null)₦{{ number_format($line['debit'], 2) }}@endif
                </td>
                <td class="r credit">
                    @if($line['credit'] !== null)₦{{ number_format($line['credit'], 2) }}@endif
                </td>
                <td class="r {{ $line['balance'] < 0 ? 'neg' : 'bal' }}">
                    ₦{{ number_format($line['balance'], 2) }}
                </td>
            </tr>
            @endforeach

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
<p class="empty">No journal entries found for the selected period and account filter.</p>
@endforelse

<div class="footer">
    <span>{{ $tenant->company_name ?? $tenant->name }} · TIN: {{ $tenant->tin ?? 'N/A' }}</span>
    <span>General Ledger — Confidential</span>
    <span>{{ now()->format('d M Y H:i') }}</span>
</div>

</body>
</html>
