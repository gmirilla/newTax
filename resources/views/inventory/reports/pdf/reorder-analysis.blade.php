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
    .oos  { background: #fef2f2 !important; }
    .now  { background: #fffbeb !important; }
    .soon { background: #fefce8 !important; }
    .badge { display: inline-block; padding: 1px 5px; border-radius: 10px; font-size: 7px; font-weight: bold; }
    .b-oos  { background: #fee2e2; color: #991b1b; }
    .b-now  { background: #fef3c7; color: #92400e; }
    .b-soon { background: #fef9c3; color: #854d0e; }
    .b-suff { background: #d1fae5; color: #065f46; }
    .b-none { background: #f3f4f6; color: #6b7280; }
    .summary { display: flex; gap: 15px; margin-bottom: 12px; }
    .stat { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 5px 10px; }
    .stat-label { font-size: 7px; color: #6b7280; text-transform: uppercase; }
    .stat-val { font-size: 12px; font-weight: bold; }
</style>
</head>
<body>
<div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
<h1>Reorder Analysis Report</h1>
<div class="meta">
    Based on last {{ $filters['days'] }} days of sales &bull; Generated: {{ now()->format('d M Y H:i') }}
</div>

@php
    $oos  = $items->where('reorder_status','Out of Stock')->count();
    $now  = $items->where('reorder_status','Reorder Now')->count();
    $soon = $items->where('reorder_status','Reorder Soon')->count();
@endphp

<div class="summary">
    <div class="stat"><div class="stat-label">Out of Stock</div><div class="stat-val" style="color:#dc2626">{{ $oos }}</div></div>
    <div class="stat"><div class="stat-label">Reorder Now</div><div class="stat-val" style="color:#d97706">{{ $now }}</div></div>
    <div class="stat"><div class="stat-label">Reorder Soon</div><div class="stat-val" style="color:#ca8a04">{{ $soon }}</div></div>
    <div class="stat"><div class="stat-label">Total Items</div><div class="stat-val">{{ $items->count() }}</div></div>
</div>

<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Category</th>
            <th class="r">Current Stock</th>
            <th class="r">Restock Level</th>
            <th class="r">Avg Daily Usage</th>
            <th class="r">Days of Stock</th>
            <th class="r">Suggested Reorder Qty</th>
            <th class="c">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
        @php
            $rowClass   = match($item->reorder_status) { 'Out of Stock'=>'oos','Reorder Now'=>'now','Reorder Soon'=>'soon',default=>'' };
            $badgeClass = match($item->reorder_status) { 'Out of Stock'=>'b-oos','Reorder Now'=>'b-now','Reorder Soon'=>'b-soon','No Movement'=>'b-none',default=>'b-suff' };
        @endphp
        <tr class="{{ $rowClass }}">
            <td>{{ $item->name }}</td>
            <td>{{ $item->category?->name ?? '—' }}</td>
            <td class="r {{ $item->current_stock <= 0 ? 'style=color:#dc2626;font-weight:bold' : '' }}">{{ number_format($item->current_stock, 2) }}</td>
            <td class="r">{{ number_format($item->restock_level, 2) }}</td>
            <td class="r">{{ number_format($item->avg_daily_usage, 3) }}</td>
            <td class="r">{{ $item->days_of_stock !== null ? number_format($item->days_of_stock) . 'd' : '—' }}</td>
            <td class="r">{{ $item->suggested_reorder_qty > 0 ? number_format($item->suggested_reorder_qty, 2) : '—' }}</td>
            <td class="c"><span class="badge {{ $badgeClass }}">{{ $item->reorder_status }}</span></td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:10px;color:#6b7280">No items found.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:10px;font-size:7px;color:#9ca3af">
    Suggested reorder qty = 30 days of avg demand (or historical restock qty, whichever is higher). Days of stock = current stock ÷ avg daily usage.
</div>
</body>
</html>
