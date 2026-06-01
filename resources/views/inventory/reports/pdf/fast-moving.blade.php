<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    body  { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 15px; }
    h1    { font-size: 14px; margin: 0 0 2px; }
    .meta { font-size: 8px; color: #777; margin-bottom: 12px; }
    .company { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
    table { width: 100%; border-collapse: collapse; }
    th    { background: #008751; color: #fff; padding: 5px 6px; text-align: left; font-size: 8.5px; }
    th.r, td.r { text-align: right; }
    th.c, td.c { text-align: center; }
    td    { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 8px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .top3 { background: #fffbeb !important; }
    .rank { font-weight: bold; font-size: 10px; }
    .summary { display: flex; gap: 20px; margin-bottom: 12px; }
    .stat { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 5px 10px; }
    .stat-label { font-size: 7px; color: #6b7280; text-transform: uppercase; }
    .stat-val { font-size: 12px; font-weight: bold; }
</style>
</head>
<body>
<div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
<h1>Fast-Moving Inventory Report</h1>
<div class="meta">
    Period: last {{ $filters['days'] }} days ({{ $filters['from'] }} to {{ $filters['to'] }}) &bull;
    Generated: {{ now()->format('d M Y H:i') }}
</div>

<div class="summary">
    <div class="stat"><div class="stat-label">Items with Sales</div><div class="stat-val">{{ $items->count() }}</div></div>
    <div class="stat"><div class="stat-label">Total Units Sold</div><div class="stat-val">{{ number_format($items->sum('units_sold'),2) }}</div></div>
    <div class="stat"><div class="stat-label">Total Revenue</div><div class="stat-val" style="color:#008751">₦{{ number_format($items->sum('revenue'),2) }}</div></div>
</div>

<table>
    <thead>
        <tr>
            <th class="c" style="width:30px">#</th>
            <th>Item</th>
            <th>Category</th>
            <th class="r">Units Sold</th>
            <th class="r">Transactions</th>
            <th class="r">Revenue (₦)</th>
            <th class="r">Avg Daily</th>
            <th class="r">Current Stock</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $i => $item)
        <tr class="{{ $i < 3 ? 'top3' : '' }}">
            <td class="c rank">{{ $i + 1 }}</td>
            <td>{{ $item->name }}@if($item->sku) <span style="color:#9ca3af;font-size:7px">({{ $item->sku }})</span>@endif</td>
            <td>{{ $item->category?->name ?? '—' }}</td>
            <td class="r">{{ number_format($item->units_sold, 2) }}</td>
            <td class="r">{{ number_format($item->transaction_count) }}</td>
            <td class="r" style="font-weight:{{ $i < 3 ? 'bold' : 'normal' }}">{{ number_format($item->revenue, 2) }}</td>
            <td class="r">{{ number_format($item->avg_daily_usage, 3) }}</td>
            <td class="r {{ $item->current_stock <= $item->restock_level ? 'style=color:#dc2626' : '' }}">{{ number_format($item->current_stock, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:10px;color:#6b7280">No sales in this period.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
