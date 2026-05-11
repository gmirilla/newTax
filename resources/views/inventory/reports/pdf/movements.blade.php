<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; margin: 0; padding: 15px; }
    h1  { font-size: 13px; margin: 0 0 2px; }
    .meta { font-size: 7.5px; color: #777; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #008751; color: #fff; padding: 4px 5px; text-align: left; font-size: 8px; }
    th.r, td.r { text-align: right; }
    td { padding: 3px 5px; border-bottom: 1px solid #e5e7eb; font-size: 7.5px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .company { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
    .in  { color: #166534; }
    .out { color: #dc2626; }
</style>
</head>
<body>
<div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
<h1>Stock Movements Report</h1>
<div class="meta">Period: {{ $filters['from'] }} to {{ $filters['to'] }} &bull; Generated: {{ now()->format('d M Y H:i') }}</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Item</th>
            <th>Type</th>
            <th class="r">Qty In</th>
            <th class="r">Qty Out</th>
            <th class="r">Balance</th>
            <th>Notes / Reference</th>
            <th>By</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movements as $m)
        @php $isIn = in_array($m->type, ['restock', 'adjustment_in', 'opening']); @endphp
        <tr>
            <td style="white-space:nowrap">{{ $m->created_at->format('d M Y H:i') }}</td>
            <td>{{ $m->item?->name ?? '—' }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $m->type)) }}</td>
            <td class="r in">{{ $isIn  ? number_format((float)$m->quantity, 3) : '' }}</td>
            <td class="r out">{{ !$isIn ? number_format((float)$m->quantity, 3) : '' }}</td>
            <td class="r" style="font-weight:600">{{ number_format((float)$m->running_balance, 3) }}</td>
            <td>{{ $m->notes ?? '—' }}</td>
            <td>{{ $m->creator?->name ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:10px;color:#6b7280;">No movements found.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
