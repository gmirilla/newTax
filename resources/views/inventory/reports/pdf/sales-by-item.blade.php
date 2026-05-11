<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 15px; }
    h1  { font-size: 13px; margin: 0 0 2px; }
    .meta { font-size: 8px; color: #777; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #008751; color: #fff; padding: 5px 6px; text-align: left; font-size: 8.5px; }
    th.r, td.r { text-align: right; }
    td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 8.5px; }
    tr:nth-child(even) td { background: #f9fafb; }
    tfoot td { font-weight: bold; background: #f0fdf4; border-top: 2px solid #008751; }
    .company { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
    .profit { color: #166534; }
    .loss   { color: #dc2626; }
</style>
</head>
<body>
<div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
<h1>Sales by Item Report</h1>
<div class="meta">Period: {{ $filters['from'] }} to {{ $filters['to'] }} &bull; Generated: {{ now()->format('d M Y H:i') }}</div>

<table>
    <thead>
        <tr>
            <th>Item</th>
            <th class="r">Units Sold</th>
            <th class="r">Revenue (₦)</th>
            <th class="r">COGS (₦)</th>
            <th class="r">Gross Profit (₦)</th>
            <th class="r">Margin %</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td>{{ $row->item?->name ?? '—' }}</td>
            <td class="r">{{ number_format((float)$row->units_sold, 2) }}</td>
            <td class="r">{{ number_format((float)$row->revenue, 2) }}</td>
            <td class="r">{{ number_format((float)$row->cogs, 2) }}</td>
            <td class="r {{ $row->gross_profit >= 0 ? 'profit' : 'loss' }}">{{ number_format($row->gross_profit, 2) }}</td>
            <td class="r">{{ number_format($row->margin_pct, 1) }}%</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:10px;color:#6b7280;">No confirmed sales in this period.</td></tr>
        @endforelse
    </tbody>
    @if($rows->count())
    <tfoot>
        <tr>
            <td>TOTALS</td>
            <td class="r">{{ number_format($totals['units_sold'], 2) }}</td>
            <td class="r">{{ number_format($totals['revenue'], 2) }}</td>
            <td class="r">{{ number_format($totals['cogs'], 2) }}</td>
            <td class="r {{ $totals['gross_profit'] >= 0 ? 'profit' : 'loss' }}">{{ number_format($totals['gross_profit'], 2) }}</td>
            <td class="r">{{ number_format($totals['margin_pct'], 1) }}%</td>
        </tr>
    </tfoot>
    @endif
</table>
</body>
</html>
