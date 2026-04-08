<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; }

    .header { padding: 12px 16px 8px; border-bottom: 3px solid #008751; margin-bottom: 10px; }
    .company { font-size: 13px; font-weight: bold; color: #008751; }
    .title   { font-size: 11px; font-weight: bold; margin-top: 2px; }
    .meta    { font-size: 8px; color: #6b7280; margin-top: 3px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }

    thead th {
        background: #1f2937; color: #fff; font-size: 7.5px;
        text-transform: uppercase; letter-spacing: .3px;
        padding: 4px 6px; text-align: left;
    }
    th.r, td.r { text-align: right; }

    tr.tx-row td {
        padding: 4px 6px; font-size: 8.5px; font-weight: bold;
        background: #f9fafb; border-bottom: 1px solid #e5e7eb;
    }
    tr.entry-row td {
        padding: 2px 6px 2px 16px; font-size: 7.5px;
        color: #4b5563; background: #fff; border-bottom: 1px solid #f3f4f6;
    }

    .badge {
        display: inline-block; padding: 1px 6px; border-radius: 8px;
        font-size: 7px; font-weight: bold;
    }
    .badge-posted   { background: #dcfce7; color: #166534; }
    .badge-draft    { background: #fef9c3; color: #854d0e; }
    .badge-voided   { background: #fee2e2; color: #991b1b; }

    .type-badge {
        display: inline-block; padding: 1px 5px; border-radius: 8px;
        font-size: 7px; background: #dbeafe; color: #1e40af;
    }

    .debit  { color: #1d4ed8; }
    .credit { color: #b91c1c; }

    .summary {
        background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 5px;
        padding: 6px 12px; display: flex; justify-content: space-between;
        font-size: 9px; font-weight: bold; color: #15803d;
        margin: 10px 0;
    }

    .footer { position: fixed; bottom: 0; left: 0; right: 0;
              padding: 4px 16px; border-top: 1px solid #e5e7eb;
              font-size: 7px; color: #9ca3af;
              display: flex; justify-content: space-between; }

    .empty { color: #9ca3af; font-style: italic; text-align: center; padding: 20px; font-size: 9px; }
</style>
</head>
<body>

<div class="header">
    <div class="company">{{ $tenant->company_name ?? $tenant->name }}</div>
    <div class="title">Journal Transactions</div>
    <div class="meta">
        @if(!empty($filters['date_from']) || !empty($filters['date_to']))
            Period:
            {{ !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') : 'Inception' }}
            —
            {{ !empty($filters['date_to'])   ? \Carbon\Carbon::parse($filters['date_to'])->format('d M Y')   : 'Today' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;
        @endif
        @if(!empty($filters['type']))Type: {{ ucfirst($filters['type']) }} &nbsp;&nbsp;|&nbsp;&nbsp; @endif
        @if(!empty($filters['search']))Search: "{{ $filters['search'] }}" &nbsp;&nbsp;|&nbsp;&nbsp; @endif
        TIN: {{ $tenant->tin ?? 'N/A' }}
        &nbsp;&nbsp;|&nbsp;&nbsp; Generated: {{ now()->format('d M Y H:i') }}
    </div>
</div>

@if($transactions->isEmpty())
<p class="empty">No transactions match the selected filters.</p>
@else

<div class="summary">
    <span>{{ $transactions->count() }} transaction(s)</span>
    <span>Total: ₦{{ number_format($transactions->sum('amount'), 2) }}</span>
</div>

<table>
    <thead>
        <tr>
            <th style="width:90px">Reference</th>
            <th style="width:70px">Date</th>
            <th style="width:60px">Type</th>
            <th>Description / Journal Entries</th>
            <th class="r" style="width:80px">Amount (₦)</th>
            <th style="width:50px">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $tx)
        <tr class="tx-row">
            <td style="font-family:monospace; font-size:8px; color:#059669">{{ $tx->reference }}</td>
            <td>{{ $tx->transaction_date->format('d/m/Y') }}</td>
            <td><span class="type-badge">{{ ucfirst(str_replace('_',' ',$tx->type)) }}</span></td>
            <td>{{ \Illuminate\Support\Str::limit($tx->description, 80) }}</td>
            <td class="r">₦{{ number_format($tx->amount, 2) }}</td>
            <td>
                <span class="badge badge-{{ $tx->status }}">{{ ucfirst($tx->status) }}</span>
            </td>
        </tr>
        @foreach($tx->journalEntries as $entry)
        <tr class="entry-row">
            <td colspan="3" style="padding-left:18px">
                <span class="{{ $entry->entry_type === 'debit' ? 'debit' : 'credit' }}">
                    {{ ucfirst($entry->entry_type) }}
                </span>
                &nbsp;
                <span style="font-family:monospace; color:#9ca3af">{{ $entry->account->code ?? '' }}</span>
                {{ $entry->account->name ?? '' }}
            </td>
            <td style="color:#6b7280">{{ \Illuminate\Support\Str::limit($entry->description ?? '', 60) }}</td>
            <td class="r {{ $entry->entry_type === 'debit' ? 'debit' : 'credit' }}">
                ₦{{ number_format($entry->amount, 2) }}
            </td>
            <td></td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>

@endif

<div class="footer">
    <span>{{ $tenant->company_name ?? $tenant->name }} · TIN: {{ $tenant->tin ?? 'N/A' }}</span>
    <span>Journal Transactions — Confidential</span>
    <span>{{ now()->format('d M Y H:i') }}</span>
</div>

</body>
</html>
