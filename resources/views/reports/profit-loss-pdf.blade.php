<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; }

    .header { padding: 16px 20px 10px; border-bottom: 3px solid #008751; }
    .company { font-size: 15px; font-weight: bold; color: #008751; }
    .report-title { font-size: 12px; font-weight: bold; margin-top: 3px; }
    .meta { font-size: 8.5px; color: #6b7280; margin-top: 5px; display: flex; gap: 16px; }
    .badge { display: inline-block; padding: 1px 7px; border-radius: 10px; font-size: 8px; font-weight: bold; }
    .badge-accrual { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .badge-cash    { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

    .body { padding: 14px 20px; }

    .section-label {
        font-size: 8px; font-weight: bold; text-transform: uppercase;
        letter-spacing: .6px; color: #374151;
        border-bottom: 2px solid #e5e7eb; padding-bottom: 3px; margin-bottom: 4px;
    }

    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 9px; }
    tr.data-row td { padding: 3.5px 4px; border-bottom: 1px solid #f3f4f6; }
    td.code  { color: #9ca3af; font-family: monospace; width: 50px; }
    td.name  { color: #374151; }
    td.src   { color: #9ca3af; font-size: 8px; text-align: center; width: 64px; }
    td.amt   { text-align: right; width: 110px; }

    tr.total-row td {
        padding: 4px 4px; font-weight: bold;
        border-top: 1px solid #d1d5db;
        border-bottom: 2px solid #d1d5db;
    }

    .total-revenue { background: #f0fdf4; }
    .total-revenue .amt { color: #15803d; font-size: 10px; }
    .total-expense { background: #fef2f2; }
    .total-expense .amt { color: #b91c1c; font-size: 10px; }

    .net-box {
        border-radius: 6px; padding: 10px 14px;
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 6px;
    }
    .net-profit { background: #f0fdf4; border: 1.5px solid #86efac; }
    .net-loss   { background: #fef2f2; border: 1.5px solid #fca5a5; }
    .net-label  { font-size: 12px; font-weight: bold; }
    .net-profit .net-label { color: #15803d; }
    .net-loss   .net-label { color: #b91c1c; }
    .net-amount { font-size: 16px; font-weight: bold; }
    .net-profit .net-amount { color: #15803d; }
    .net-loss   .net-amount { color: #b91c1c; }
    .net-sub { font-size: 7.5px; color: #6b7280; margin-top: 3px; }

    .warning-box {
        margin-top: 10px; padding: 7px 10px;
        background: #fffbeb; border: 1px solid #fde68a;
        border-radius: 4px; font-size: 8px; color: #92400e;
    }

    .source-legend {
        margin-top: 12px; display: flex; gap: 12px; font-size: 7.5px; color: #6b7280;
    }
    .dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 3px; vertical-align: middle; }

    .footer {
        position: fixed; bottom: 0; left: 0; right: 0;
        padding: 6px 20px; border-top: 1px solid #e5e7eb;
        font-size: 7.5px; color: #9ca3af;
        display: flex; justify-content: space-between;
    }

    tr.data-row:nth-child(even) td { background: #fafafa; }
    .empty-note { color: #9ca3af; font-style: italic; font-size: 8.5px; padding: 6px 4px; }
</style>
</head>
<body>

<div class="header">
    <div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
    <div class="report-title">Profit & Loss Statement</div>
    <div class="meta">
        <span>
            Period: {{ \Carbon\Carbon::parse($report['period_start'])->format('d M Y') }}
            — {{ \Carbon\Carbon::parse($report['period_end'])->format('d M Y') }}
        </span>
        <span>
            <span class="badge {{ $report['basis'] === 'cash' ? 'badge-cash' : 'badge-accrual' }}">
                {{ $report['basis'] === 'cash' ? 'Cash Basis' : 'Accrual Basis' }}
            </span>
        </span>
        <span>TIN: {{ $tenant->tin ?? 'N/A' }}</span>
        <span>Generated: {{ now()->format('d M Y H:i') }}</span>
    </div>
</div>

<div class="body">

    {{-- Revenue --}}
    <div class="section-label">Revenue</div>
    <table>
        @forelse($report['revenue'] as $row)
        <tr class="data-row">
            <td class="code">{{ $row['code'] }}</td>
            <td class="name">{{ $row['name'] }}</td>
            <td class="src">{{ ucfirst($row['source'] ?? '') }}</td>
            <td class="amt">₦{{ number_format($row['balance'], 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="empty-note">No revenue recorded for this period.</td></tr>
        @endforelse
        <tr class="total-row total-revenue">
            <td colspan="3" style="padding-left:4px">Total Revenue</td>
            <td class="amt">₦{{ number_format($report['total_revenue'], 2) }}</td>
        </tr>
    </table>

    {{-- Expenses --}}
    <div class="section-label">Expenses</div>
    <table>
        @forelse($report['expenses'] as $row)
        <tr class="data-row">
            <td class="code">{{ $row['code'] }}</td>
            <td class="name">{{ $row['name'] }}</td>
            <td class="src">{{ ucfirst($row['source'] ?? '') }}</td>
            <td class="amt">₦{{ number_format($row['balance'], 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="empty-note">No expenses recorded for this period.</td></tr>
        @endforelse
        <tr class="total-row total-expense">
            <td colspan="3" style="padding-left:4px">Total Expenses</td>
            <td class="amt">₦{{ number_format($report['total_expenses'], 2) }}</td>
        </tr>
    </table>

    {{-- Net result --}}
    <div class="net-box {{ $report['is_profit'] ? 'net-profit' : 'net-loss' }}">
        <div>
            <div class="net-label">{{ $report['is_profit'] ? 'Net Profit' : 'Net Loss' }}</div>
            <div class="net-sub">
                Revenue ₦{{ number_format($report['total_revenue'], 2) }}
                &minus; Expenses ₦{{ number_format($report['total_expenses'], 2) }}
            </div>
        </div>
        <div class="net-amount">₦{{ number_format(abs($report['net_profit']), 2) }}</div>
    </div>

    @if(!$report['is_profit'])
    <div class="warning-box">
        ⚠ Company is in a loss position. No CIT is due; however, minimum tax of 0.5% of gross
        turnover may apply under the Nigeria Tax Act 2025.
    </div>
    @endif

    @if($report['basis'] === 'cash')
    <div class="warning-box" style="margin-top:6px; background:#fffbeb; border-color:#fde68a; color:#78350f;">
        ⚠ This report uses <strong>cash basis</strong> accounting and is provided for management
        information only. FIRS CIT assessment uses <strong>accrual basis</strong>.
    </div>
    @endif

    {{-- Source legend --}}
    @php
        $sources = collect(array_merge($report['revenue'], $report['expenses']))
            ->pluck('source')->unique()->filter()->values();
        $srcColors = ['journal'=>'#818cf8','invoices'=>'#4ade80','expenses'=>'#fb923c','payroll'=>'#60a5fa','payments'=>'#34d399'];
    @endphp
    @if($sources->isNotEmpty())
    <div class="source-legend">
        <span>Data sources:</span>
        @foreach($sources as $src)
        <span>
            <span class="dot" style="background:{{ $srcColors[$src] ?? '#9ca3af' }}"></span>
            {{ ucfirst($src) }}
        </span>
        @endforeach
    </div>
    @endif

</div>

<div class="footer">
    <span>{{ $tenant->company_name ?? $tenant->name }} · {{ $report['basis'] === 'cash' ? 'Cash Basis' : 'Accrual Basis (IFRS/FIRS)' }}</span>
    <span>Confidential — management use only</span>
    <span>{{ now()->format('d M Y H:i') }}</span>
</div>

</body>
</html>
