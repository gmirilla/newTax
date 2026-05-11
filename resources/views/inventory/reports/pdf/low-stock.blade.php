<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 15px; }
    h1  { font-size: 14px; margin: 0 0 2px; }
    .meta { font-size: 8px; color: #777; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #008751; color: #fff; padding: 5px 6px; text-align: left; font-size: 8.5px; }
    th.r, td.r { text-align: right; }
    td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 8px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .company { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
    .shortfall { color: #dc2626; font-weight: bold; }
    .zero { color: #dc2626; font-weight: bold; }
</style>
</head>
<body>
<div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
<h1>Low Stock Report</h1>
<div class="meta">Generated: {{ now()->format('d M Y H:i') }} &bull; {{ $items->count() }} item(s) below restock level</div>

<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Category</th>
            <th>SKU</th>
            <th class="r">Current Stock</th>
            <th class="r">Restock Level</th>
            <th class="r">Shortfall</th>
            <th>Last Restocked</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td>{{ $item->category?->name ?? '—' }}</td>
            <td>{{ $item->sku ?? '—' }}</td>
            <td class="r {{ (float)$item->current_stock <= 0 ? 'zero' : '' }}">{{ number_format((float)$item->current_stock, 3) }}</td>
            <td class="r">{{ number_format((float)$item->restock_level, 3) }}</td>
            <td class="r shortfall">{{ number_format($item->shortfall, 3) }}</td>
            <td>{{ $item->last_restocked?->format('d M Y') ?? 'Never' }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:12px;color:#6b7280;">All items sufficiently stocked.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
