<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; }

    .header { padding: 14px 18px 10px; border-bottom: 3px solid #008751; }
    .company { font-size: 15px; font-weight: bold; color: #008751; }
    .title   { font-size: 12px; font-weight: bold; margin-top: 3px; }
    .meta    { font-size: 8.5px; color: #6b7280; margin-top: 4px; display: flex; gap: 16px; align-items: center; }
    .badge   { padding: 1px 8px; border-radius: 10px; font-size: 7.5px; font-weight: bold;
               background: #fef3c7; color: #78350f; border: 1px solid #fde68a; }

    .columns { display: flex; gap: 0; padding: 12px 18px; }
    .col     { flex: 1; }
    .col + .col { padding-left: 20px; border-left: 1px solid #e5e7eb; margin-left: 20px; }

    .section-label {
        font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px;
        color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 3px; margin-bottom: 4px;
    }

    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9px; }
    td.code { color: #9ca3af; font-family: monospace; width: 44px; }
    td.name { color: #374151; }
    td.src  { color: #9ca3af; font-size: 7.5px; text-align: center; width: 56px; }
    td.amt  { text-align: right; width: 96px; }
    tr.data td { padding: 3px 3px; border-bottom: 1px solid #f3f4f6; }
    tr.data:nth-child(even) td { background: #fafafa; }

    tr.subtotal td {
        padding: 4px 3px; font-weight: bold;
        border-top: 1px solid #d1d5db; border-bottom: 2px solid #d1d5db;
    }
    .sub-assets  td { background: #eff6ff; }
    .sub-liab    td { background: #fef2f2; }
    .sub-equity  td { background: #f0fdf4; }

    .grand-total {
        margin: 12px 18px 0;
        background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 6px;
        padding: 8px 12px; display: flex; justify-content: space-between; align-items: center;
    }
    .grand-total .lbl { font-size: 11px; font-weight: bold; color: #15803d; }
    .grand-total .val { font-size: 13px; font-weight: bold; color: #15803d; }

    .balance-warn { margin: 8px 18px 0; padding: 6px 10px;
        background: #fff7ed; border: 1px solid #fed7aa; border-radius: 4px;
        font-size: 8px; color: #9a3412; }

    .note-bar { margin: 8px 18px 0; padding: 5px 10px;
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;
        font-size: 7.5px; color: #64748b; }

    .legend { margin: 8px 18px 0; display: flex; gap: 10px; font-size: 7.5px; color: #6b7280; }
    .dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 2px; vertical-align: middle; }

    .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 5px 18px;
        border-top: 1px solid #e5e7eb; font-size: 7.5px; color: #9ca3af;
        display: flex; justify-content: space-between; }

    .empty { color: #9ca3af; font-style: italic; font-size: 8px; padding: 5px 3px; }
</style>
</head>
<body>

<div class="header">
    <div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
    <div class="title">Balance Sheet</div>
    <div class="meta">
        <span>As of: {{ \Carbon\Carbon::parse($report['as_of'])->format('d F Y') }}</span>
        @if($report['is_approximate'])
        <span class="badge">Approximate</span>
        @endif
        <span>TIN: {{ $tenant->tin ?? 'N/A' }}</span>
        <span>Generated: {{ now()->format('d M Y H:i') }}</span>
    </div>
</div>

<div class="columns">

    {{-- LEFT: Assets --}}
    <div class="col">
        <div class="section-label">Assets</div>
        <table>
            @forelse($report['assets'] as $row)
            <tr class="data">
                <td class="code">{{ $row['code'] }}</td>
                <td class="name">{{ $row['name'] }}</td>
                <td class="src">{{ ucfirst($row['source'] ?? '') }}</td>
                <td class="amt">₦{{ number_format($row['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="empty">No asset balances recorded.</td></tr>
            @endforelse
            <tr class="subtotal sub-assets">
                <td colspan="3">Total Assets</td>
                <td class="amt">₦{{ number_format($report['total_assets'], 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- RIGHT: Liabilities + Equity --}}
    <div class="col">
        <div class="section-label">Liabilities</div>
        <table>
            @forelse($report['liabilities'] as $row)
            <tr class="data">
                <td class="code">{{ $row['code'] }}</td>
                <td class="name">{{ $row['name'] }}</td>
                <td class="src">{{ ucfirst($row['source'] ?? '') }}</td>
                <td class="amt">₦{{ number_format($row['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="empty">No liabilities recorded.</td></tr>
            @endforelse
            <tr class="subtotal sub-liab">
                <td colspan="3">Total Liabilities</td>
                <td class="amt">₦{{ number_format($report['total_liabilities'], 2) }}</td>
            </tr>
        </table>

        <div class="section-label" style="margin-top:10px">Equity</div>
        <table>
            @forelse($report['equity'] as $row)
            <tr class="data">
                <td class="code">{{ $row['code'] }}</td>
                <td class="name">{{ $row['name'] }}</td>
                <td class="src">{{ ucfirst($row['source'] ?? '') }}</td>
                <td class="amt">₦{{ number_format($row['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="empty">No equity recorded.</td></tr>
            @endforelse
            <tr class="subtotal sub-equity">
                <td colspan="3">Total Equity</td>
                <td class="amt">₦{{ number_format($report['total_equity'], 2) }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- Grand total --}}
<div class="grand-total">
    <span class="lbl">Total Liabilities + Equity</span>
    <span class="val">₦{{ number_format($report['total_liabilities'] + $report['total_equity'], 2) }}</span>
</div>

@if(!$report['is_balanced'])
<div class="balance-warn">
    ⚠ Assets (₦{{ number_format($report['total_assets'], 2) }}) do not equal
    Liabilities + Equity (₦{{ number_format($report['total_liabilities'] + $report['total_equity'], 2) }}).
    Difference: ₦{{ number_format(abs($report['total_assets'] - $report['total_liabilities'] - $report['total_equity']), 2) }}.
    Post manual journal entries via Transactions to correct this.
</div>
@endif

@if($report['is_approximate'])
<div class="note-bar">
    This balance sheet is supplemented from invoice, expense, and payroll records.
    For a fully audited balance sheet, ensure all transactions are posted as double-entry journal entries.
</div>
@endif

@php
    $sources = collect(array_merge($report['assets'], $report['liabilities'], $report['equity']))
        ->pluck('source')->unique()->filter()->values();
    $srcColors = ['journal'=>'#818cf8','invoices'=>'#4ade80','expenses'=>'#fb923c','payroll'=>'#60a5fa','payments'=>'#34d399','operations'=>'#a78bfa'];
@endphp
@if($sources->isNotEmpty())
<div class="legend">
    <span>Sources:</span>
    @foreach($sources as $src)
    <span>
        <span class="dot" style="background:{{ $srcColors[$src] ?? '#9ca3af' }}"></span>
        {{ ucfirst($src) }}
    </span>
    @endforeach
</div>
@endif

<div class="footer">
    <span>{{ $tenant->company_name ?? $tenant->name }} · TIN: {{ $tenant->tin ?? 'N/A' }}</span>
    <span>{{ $report['is_balanced'] ? '✓ Balanced' : '⚠ Approximate' }}</span>
    <span>{{ now()->format('d M Y H:i') }}</span>
</div>

</body>
</html>
