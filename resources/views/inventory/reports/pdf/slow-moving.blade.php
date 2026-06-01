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
    .dead { background: #fef2f2 !important; }
    .badge { display: inline-block; padding: 1px 5px; border-radius: 10px; font-size: 7px; font-weight: bold; }
    .b-dead { background: #fee2e2; color: #991b1b; }
    .b-slow { background: #fef3c7; color: #92400e; }
    .b-mod  { background: #dbeafe; color: #1e40af; }
    .b-fast { background: #d1fae5; color: #065f46; }
    .summary { display: flex; gap: 20px; margin-bottom: 12px; }
    .stat { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 5px 10px; }
    .stat-label { font-size: 7px; color: #6b7280; text-transform: uppercase; }
    .stat-val { font-size: 12px; font-weight: bold; }
</style>
</head>
<body>
<div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
<h1>Slow-Moving Inventory Report</h1>
<div class="meta">
    Period: last {{ $filters['days'] }} days ({{ $filters['from'] }} to {{ $filters['to'] }}) &bull;
    Generated: {{ now()->format('d M Y H:i') }}
</div>

@php
    $deadCount = $items->where('velocity_label','Dead Stock')->count();
    $slowCount = $items->where('velocity_label','Slow')->count();
@endphp

<div class="summary">
    <div class="stat"><div class="stat-label">Total Items</div><div class="stat-val">{{ $items->count() }}</div></div>
    <div class="stat"><div class="stat-label">Dead Stock</div><div class="stat-val" style="color:#dc2626">{{ $deadCount }}</div></div>
    <div class="stat"><div class="stat-label">Slow Moving</div><div class="stat-val" style="color:#d97706">{{ $slowCount }}</div></div>
</div>

<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Category</th>
            <th>SKU</th>
            <th class="r">Current Stock</th>
            <th class="r">Units Sold</th>
            <th class="r">Avg Daily</th>
            <th class="c">Days Since Sale</th>
            <th class="c">Velocity</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
        @php $isDead = $item->velocity_label === 'Dead Stock'; @endphp
        <tr class="{{ $isDead ? 'dead' : '' }}">
            <td>{{ $item->name }}</td>
            <td>{{ $item->category?->name ?? '—' }}</td>
            <td>{{ $item->sku ?? '—' }}</td>
            <td class="r">{{ number_format($item->current_stock, 2) }}</td>
            <td class="r">{{ number_format($item->units_sold, 2) }}</td>
            <td class="r">{{ number_format($item->avg_daily_usage, 3) }}</td>
            <td class="c">{{ $item->days_since_last_sale !== null ? $item->days_since_last_sale . 'd' : 'Never' }}</td>
            <td class="c">
                @php $bc = match($item->velocity_label) { 'Dead Stock'=>'b-dead','Slow'=>'b-slow','Moderate'=>'b-mod',default=>'b-fast' }; @endphp
                <span class="badge {{ $bc }}">{{ $item->velocity_label }}</span>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:10px;color:#6b7280">No items found.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
