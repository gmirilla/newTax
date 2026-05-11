<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 15px; }
    h1  { font-size: 14px; margin: 0 0 2px; }
    h2  { font-size: 10px; font-weight: normal; margin: 0 0 8px; color: #555; }
    .meta { font-size: 8px; color: #777; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #008751; color: #fff; padding: 5px 6px; text-align: left; font-size: 8.5px; }
    th.r, td.r { text-align: right; }
    td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 8px; }
    tr:nth-child(even) td { background: #f9fafb; }
    tfoot td { font-weight: bold; background: #f0fdf4; border-top: 2px solid #008751; }
    .company { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
</style>
</head>
<body>
<div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
<h1>Stock Valuation Report</h1>
<div class="meta">Generated: {{ now()->format('d M Y H:i') }}</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Item</th>
            <th>Category</th>
            <th>Unit</th>
            <th class="r">Qty on Hand</th>
            <th class="r">Avg Cost (₦)</th>
            <th class="r">Stock Value (₦)</th>
            <th class="r">Selling Price (₦)</th>
            <th class="r">Potential Rev (₦)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item->name }}{{ $item->sku ? " ({$item->sku})" : '' }}</td>
            <td>{{ $item->category?->name ?? '—' }}</td>
            <td>{{ $item->unit ?? '—' }}</td>
            <td class="r">{{ number_format((float)$item->current_stock, 3) }}</td>
            <td class="r">{{ number_format((float)$item->avg_cost, 2) }}</td>
            <td class="r">{{ number_format($item->stock_value, 2) }}</td>
            <td class="r">{{ number_format((float)$item->selling_price, 2) }}</td>
            <td class="r">{{ number_format($item->potential_revenue, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="r">TOTALS</td>
            <td class="r">{{ number_format($totals['total_stock_value'], 2) }}</td>
            <td></td>
            <td class="r">{{ number_format($totals['total_potential_revenue'], 2) }}</td>
        </tr>
    </tfoot>
</table>
</body>
</html>
