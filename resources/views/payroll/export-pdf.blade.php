<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }

    /* Header */
    .header { padding: 14px 16px 10px; border-bottom: 3px solid #008751; margin-bottom: 10px; }
    .company-name { font-size: 15px; font-weight: bold; color: #008751; }
    .payroll-title { font-size: 11px; font-weight: bold; margin-top: 2px; }
    .meta { font-size: 8px; color: #6b7280; margin-top: 4px; }

    /* Summary band */
    .summary { display: flex; gap: 8px; padding: 8px 16px; background: #f9fafb;
               border-bottom: 1px solid #e5e7eb; margin-bottom: 10px; }
    .sum-box { flex: 1; text-align: center; padding: 6px 4px; border-radius: 4px; }
    .sum-box .lbl { font-size: 7px; color: #6b7280; text-transform: uppercase; letter-spacing: .4px; }
    .sum-box .val { font-size: 10px; font-weight: bold; margin-top: 1px; }
    .bg-gray   { background: #f3f4f6; }
    .bg-orange { background: #fff7ed; color: #c2410c; }
    .bg-blue   { background: #eff6ff; color: #1d4ed8; }
    .bg-purple { background: #faf5ff; color: #7e22ce; }
    .bg-green  { background: #f0fdf4; color: #15803d; }

    /* Table */
    .wrap { padding: 0 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 8px; }
    thead tr { background: #008751; color: #fff; }
    thead th { padding: 5px 4px; text-align: right; font-weight: 600; font-size: 7.5px; white-space: nowrap; }
    thead th:first-child, thead th:nth-child(2) { text-align: left; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    tbody td { padding: 4px 4px; vertical-align: top; border-bottom: 1px solid #f3f4f6; text-align: right; }
    tbody td:first-child, tbody td:nth-child(2) { text-align: left; }
    .name { font-weight: 600; }
    .sub  { font-size: 7px; color: #9ca3af; }

    /* Totals row */
    .tfoot td { padding: 5px 4px; font-weight: bold; background: #f0fdf4;
                border-top: 2px solid #008751; text-align: right; }
    .tfoot td:first-child, .tfoot td:nth-child(2) { text-align: left; }

    /* Footer */
    .footer { margin-top: 14px; padding: 8px 16px; border-top: 1px solid #e5e7eb;
              font-size: 7.5px; color: #9ca3af; display: flex; justify-content: space-between; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="company-name">{{ $payroll->tenant->company_name }}</div>
    <div class="payroll-title">Payroll Register — {{ $payroll->getMonthName() }}</div>
    <div class="meta">
        Pay Date: {{ $payroll->pay_date->format('d F Y') }}
        &nbsp;|&nbsp; Status: {{ ucfirst($payroll->status) }}
        &nbsp;|&nbsp; Employees: {{ $payroll->items->count() }}
        &nbsp;|&nbsp; Generated: {{ now()->format('d M Y H:i') }}
    </div>
</div>

{{-- Summary band --}}
<div class="summary">
    <div class="sum-box bg-gray">
        <div class="lbl">Total Gross</div>
        <div class="val">₦{{ number_format($payroll->total_gross, 2) }}</div>
    </div>
    <div class="sum-box bg-orange">
        <div class="lbl">PAYE Tax</div>
        <div class="val">₦{{ number_format($payroll->total_paye, 2) }}</div>
    </div>
    <div class="sum-box bg-blue">
        <div class="lbl">Pension (Emp)</div>
        <div class="val">₦{{ number_format($payroll->total_pension, 2) }}</div>
    </div>
    <div class="sum-box bg-blue">
        <div class="lbl">Pension (Empl)</div>
        <div class="val">₦{{ number_format($payroll->total_employer_pension, 2) }}</div>
    </div>
    <div class="sum-box bg-purple">
        <div class="lbl">NHF</div>
        <div class="val">₦{{ number_format($payroll->total_nhf, 2) }}</div>
    </div>
    <div class="sum-box bg-green">
        <div class="lbl">Net Pay</div>
        <div class="val">₦{{ number_format($payroll->total_net, 2) }}</div>
    </div>
</div>

{{-- Detail table --}}
<div class="wrap">
<table>
    <thead>
        <tr>
            <th>Employee</th>
            <th>Dept</th>
            <th>Basic</th>
            <th>Gross</th>
            <th>Bonus</th>
            <th>Pension</th>
            <th>NHF</th>
            <th>Taxable</th>
            <th>PAYE</th>
            <th>Deductions</th>
            <th>Net Pay</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payroll->items as $item)
        <tr>
            <td>
                <span class="name">{{ $item->employee->full_name }}</span><br>
                <span class="sub">{{ $item->employee->employee_id }} · {{ $item->employee->job_title }}</span>
            </td>
            <td>{{ $item->employee->department ?? '—' }}</td>
            <td>{{ number_format($item->basic_salary, 2) }}</td>
            <td>{{ number_format($item->gross_pay, 2) }}</td>
            <td>{{ number_format(($item->bonus ?? 0) + ($item->overtime ?? 0), 2) }}</td>
            <td>{{ number_format($item->pension_employee, 2) }}</td>
            <td>{{ number_format($item->nhf, 2) }}</td>
            <td>{{ number_format($item->taxable_income, 2) }}</td>
            <td><strong>{{ number_format($item->paye_tax, 2) }}</strong></td>
            <td>{{ number_format(
                ($item->loan_deduction ?? 0) + ($item->advance_deduction ?? 0) +
                ($item->penalty_deduction ?? 0) + ($item->other_deductions ?? 0),
                2) }}</td>
            <td><strong>{{ number_format($item->net_pay, 2) }}</strong></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot class="tfoot">
        <tr>
            <td colspan="2">TOTALS ({{ $payroll->items->count() }} employees)</td>
            <td>{{ number_format($payroll->items->sum('basic_salary'), 2) }}</td>
            <td>{{ number_format($payroll->total_gross, 2) }}</td>
            <td>{{ number_format($payroll->items->sum('bonus') + $payroll->items->sum('overtime'), 2) }}</td>
            <td>{{ number_format($payroll->total_pension, 2) }}</td>
            <td>{{ number_format($payroll->total_nhf, 2) }}</td>
            <td></td>
            <td>{{ number_format($payroll->total_paye, 2) }}</td>
            <td></td>
            <td>{{ number_format($payroll->total_net, 2) }}</td>
        </tr>
    </tfoot>
</table>
</div>

<div class="footer">
    <span>{{ $payroll->tenant->company_name }} · TIN: {{ $payroll->tenant->tin ?? 'N/A' }}</span>
    <span>PAYE computed under NTA 2025 · {{ now()->format('d M Y H:i') }}</span>
    <span>Confidential — payroll use only</span>
</div>

</body>
</html>
