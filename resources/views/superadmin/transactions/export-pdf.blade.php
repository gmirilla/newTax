<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; }

    .header { padding: 12px 16px 8px; border-bottom: 3px solid #1f2937; margin-bottom: 10px; }
    .brand  { font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
    .title  { font-size: 14px; font-weight: bold; color: #111827; margin-top: 2px; }
    .meta   { font-size: 8px; color: #6b7280; margin-top: 4px; }

    .stats { display: flex; gap: 12px; margin-bottom: 12px; }
    .stat-box { flex: 1; border: 1px solid #e5e7eb; border-radius: 4px; padding: 6px 10px; }
    .stat-label { font-size: 7px; text-transform: uppercase; color: #9ca3af; font-weight: bold; letter-spacing: .4px; }
    .stat-value { font-size: 13px; font-weight: bold; color: #111827; margin-top: 1px; }
    .stat-sub   { font-size: 7px; color: #6b7280; margin-top: 1px; }

    table { width: 100%; border-collapse: collapse; }
    thead th {
        background: #1f2937; color: #fff; font-size: 7.5px;
        text-transform: uppercase; letter-spacing: .3px;
        padding: 5px 7px; text-align: left;
    }
    th.r, td.r { text-align: right; }
    th.c, td.c { text-align: center; }

    tbody tr:nth-child(even) { background: #f9fafb; }
    tbody td { padding: 4px 7px; font-size: 8px; border-bottom: 1px solid #f3f4f6; }

    .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7px; font-weight: bold; }
    .badge-success  { background: #dcfce7; color: #166534; }
    .badge-failed   { background: #fee2e2; color: #991b1b; }
    .badge-pending  { background: #fef9c3; color: #854d0e; }
    .badge-refunded { background: #ede9fe; color: #5b21b6; }

    .badge-cycle-yearly  { background: #eff6ff; color: #1d4ed8; }
    .badge-cycle-monthly { background: #f3f4f6; color: #4b5563; }

    tfoot td { padding: 5px 7px; font-size: 8px; font-weight: bold; background: #f0fdf4; border-top: 2px solid #d1fae5; }

    .footer { margin-top: 12px; font-size: 7px; color: #9ca3af; text-align: center; }
</style>
</head>
<body>

<div class="header">
    <div class="brand">NaijaBooks — Platform Admin</div>
    <div class="title">Subscription Transactions</div>
    <div class="meta">
        Generated {{ now()->format('d M Y, H:i') }}
        @if(!empty($filters['date_from']) || !empty($filters['date_to']))
            &nbsp;·&nbsp; Period:
            {{ !empty($filters['date_from']) ? $filters['date_from'] : '—' }}
            to
            {{ !empty($filters['date_to']) ? $filters['date_to'] : 'present' }}
        @endif
        @if(!empty($filters['search']))
            &nbsp;·&nbsp; Company: "{{ $filters['search'] }}"
        @endif
        @if(!empty($filters['status']))
            &nbsp;·&nbsp; Status: {{ ucfirst($filters['status']) }}
        @endif
        @if(!empty($filters['cycle']))
            &nbsp;·&nbsp; Cycle: {{ ucfirst($filters['cycle']) }}
        @endif
    </div>
</div>

<div class="stats">
    <div class="stat-box">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">₦{{ number_format($payments->where('status', 'success')->sum('amount'), 2) }}</div>
        <div class="stat-sub">successful payments</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Transactions</div>
        <div class="stat-value">{{ $payments->count() }}</div>
        <div class="stat-sub">in this export</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Successful</div>
        <div class="stat-value" style="color:#166534">{{ $payments->where('status', 'success')->count() }}</div>
        <div class="stat-sub">of {{ $payments->count() }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Failed</div>
        <div class="stat-value" style="color:#991b1b">{{ $payments->where('status', 'failed')->count() }}</div>
        <div class="stat-sub">transactions</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Company</th>
            <th>Plan</th>
            <th>Type</th>
            <th class="c">Cycle</th>
            <th class="r">Amount (₦)</th>
            <th class="c">Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($payments as $payment)
        <tr>
            <td>{{ $payment->tenant?->name ?? '—' }}</td>
            <td>{{ $payment->plan?->name ?? '—' }}</td>
            <td>{{ $payment->typeLabel() }}</td>
            <td class="c">
                @php $cycle = $payment->billing_cycle ?? 'monthly'; @endphp
                <span class="badge badge-cycle-{{ $cycle }}">{{ ucfirst($cycle) }}</span>
            </td>
            <td class="r">₦{{ number_format($payment->amount, 2) }}</td>
            <td class="c">
                <span class="badge badge-{{ $payment->status }}">{{ ucfirst($payment->status) }}</span>
            </td>
            <td>{{ $payment->paid_at?->format('d M Y') ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:16px;color:#9ca3af;">No transactions found.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">Total ({{ $payments->count() }} records, {{ $payments->where('status', 'success')->count() }} successful)</td>
            <td class="r">₦{{ number_format($payments->where('status', 'success')->sum('amount'), 2) }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">NaijaBooks Platform &nbsp;·&nbsp; Confidential &nbsp;·&nbsp; {{ now()->format('d M Y') }}</div>

</body>
</html>
