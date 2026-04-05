<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class PayrollExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    WithColumnWidths,
    WithEvents
{
    public function __construct(
        private readonly Payroll $payroll
    ) {
        $this->payroll->loadMissing('items.employee');
    }

    public function title(): string
    {
        return $this->payroll->getMonthName();
    }

    public function collection(): Collection
    {
        return $this->payroll->items->map(function ($item) {
            return [
                $item->employee->employee_id,
                $item->employee->full_name,
                $item->employee->job_title,
                $item->employee->department ?? '—',
                (float) $item->basic_salary,
                (float) $item->gross_pay,
                (float) $item->pension_employee,
                (float) $item->pension_employer,
                (float) $item->nhf,
                (float) ($item->nhis ?? 0),
                (float) $item->taxable_income,
                (float) $item->paye_tax,
                (float) ($item->bonus ?? 0),
                (float) ($item->overtime ?? 0),
                (float) (
                    ($item->loan_deduction    ?? 0) +
                    ($item->advance_deduction ?? 0) +
                    ($item->penalty_deduction ?? 0) +
                    ($item->other_deductions  ?? 0)
                ),
                (float) $item->net_pay,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Full Name',
            'Job Title',
            'Department',
            'Basic Salary (₦)',
            'Gross Pay (₦)',
            'Pension – Employee 8% (₦)',
            'Pension – Employer 10% (₦)',
            'NHF 2.5% (₦)',
            'NHIS / HMO (₦)',
            'Taxable Income (₦)',
            'PAYE Tax (₦)',
            'Bonus (₦)',
            'Overtime (₦)',
            'Other Deductions (₦)',
            'Net Pay (₦)',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 28,
            'C' => 24,
            'D' => 18,
            'E' => 20,
            'F' => 18,
            'G' => 26,
            'H' => 26,
            'I' => 16,
            'J' => 16,
            'K' => 20,
            'L' => 18,
            'M' => 16,
            'N' => 16,
            'O' => 22,
            'P' => 18,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->payroll->items->count() + 3; // header at row 2, data starts at row 3

        // Currency format for columns E–P
        $currencyFmt = '#,##0.00';
        foreach (range('E', 'P') as $col) {
            $sheet->getStyle("{$col}3:{$col}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode($currencyFmt);
        }

        return [
            // Header row styling
            2 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '008751']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Freeze header row
        ];
    }

    public function registerEvents(): array
    {
        $payroll = $this->payroll;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($payroll) {
                $sheet = $event->sheet->getDelegate();

                // ── Title block (row 1) ───────────────────────────────────────
                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:P1');
                $sheet->setCellValue('A1', "{$payroll->tenant->company_name} — Payroll: {$payroll->getMonthName()}");
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                $sheet->mergeCells('A2:P2');
                $sheet->setCellValue('A2', "Pay Date: {$payroll->pay_date->format('d M Y')}   |   Status: " . ucfirst($payroll->status) . "   |   Total Employees: {$payroll->items->count()}");
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 10, 'italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // Header is now row 3 (after insert), data starts row 4
                $lastDataRow = $payroll->items->count() + 3;

                // ── Totals row ────────────────────────────────────────────────
                $totalsRow = $lastDataRow + 1;
                $sheet->setCellValue("A{$totalsRow}", 'TOTALS');
                $sheet->getStyle("A{$totalsRow}")->getFont()->setBold(true);

                $totalCols = ['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
                foreach ($totalCols as $col) {
                    $sheet->setCellValue("{$col}{$totalsRow}", "=SUM({$col}4:{$col}{$lastDataRow})");
                    $sheet->getStyle("{$col}{$totalsRow}")->applyFromArray([
                        'font'          => ['bold' => true],
                        'numberFormat'  => ['formatCode' => '#,##0.00'],
                    ]);
                }

                $sheet->getStyle("A{$totalsRow}:P{$totalsRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                ]);

                // ── Alternate row shading for data rows ───────────────────────
                for ($r = 4; $r <= $lastDataRow; $r += 2) {
                    $sheet->getStyle("A{$r}:P{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                    ]);
                }

                // ── Freeze panes at row 4 (below title + header) ──────────────
                $sheet->freezePane('A4');

                // ── Auto-filter on header row ─────────────────────────────────
                $sheet->setAutoFilter("A3:P3");
            },
        ];
    }
}
