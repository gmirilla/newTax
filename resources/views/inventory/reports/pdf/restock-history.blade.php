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
    .badge { padding: 1px 5px; border-radius: 9px; font-weight: 600; font-size: 7px; }
    .pending   { background: #fef3c7; color: #92400e; }
    .approved  { background: #dbeafe; color: #1e40af; }
    .received  { background: #d1fae5; color: #065f46; }
    .rejected  { background: #fee2e2; color: #991b1b; }
    .cancelled { background: #f3f4f6; color: #6b7280; }
</style>
</head>
<body>
<div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
<h1>Restock History Report</h1>
<div class="meta">
    Period: {{ $filters['from'] }} to {{ $filters['to'] }}
    @if($filters['status']) &bull; Status: {{ ucfirst($filters['status']) }} @endif
    &bull; Generated: {{ now()->format('d M Y H:i') }}
</div>

<table>
    <thead>
        <tr>
            <th>Request No.</th>
            <th>Item</th>
            <th class="r">Qty</th>
            <th class="r">Unit Cost</th>
            <th class="r">Total Cost</th>
            <th>Supplier</th>
            <th>Status</th>
            <th>Requested By</th>
            <th>Approved By</th>
            <th>Received</th>
        </tr>
    </thead>
    <tbody>
        @forelse($requests as $rr)
        <tr>
            <td style="white-space:nowrap">{{ $rr->request_number }}</td>
            <td>{{ $rr->item?->name ?? '—' }}</td>
            <td class="r">{{ number_format((float)$rr->quantity_requested, 3) }}</td>
            <td class="r">{{ number_format((float)$rr->unit_cost, 2) }}</td>
            <td class="r" style="font-weight:600">{{ number_format($rr->totalCost(), 2) }}</td>
            <td>{{ $rr->supplier_name ?? '—' }}</td>
            <td><span class="badge {{ $rr->status }}">{{ ucfirst($rr->status) }}</span></td>
            <td>{{ $rr->requester?->name ?? '—' }}</td>
            <td>{{ $rr->approver?->name ?? '—' }}</td>
            <td style="white-space:nowrap">{{ $rr->received_at?->format('d M Y') ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;padding:10px;color:#6b7280;">No restock requests found.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
