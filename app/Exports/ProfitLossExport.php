<?php

namespace App\Exports;

use App\Models\Tenant;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLossExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly array  $report,
        private readonly Tenant $tenant,
    ) {}

    public function title(): string
    {
        return 'P&L Statement';
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 42, 'C' => 10, 'D' => 20];
    }

    public function array(): array
    {
        $basis      = ucfirst($this->report['basis']);
        $periodFrom = \Carbon\Carbon::parse($this->report['period_start'])->format('d M Y');
        $periodTo   = \Carbon\Carbon::parse($this->report['period_end'])->format('d M Y');

        $rows = [];

        // ── Header block ──────────────────────────────────────────────────────
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', ''];
        $rows[] = ['Profit & Loss Statement', '', '', ''];
        $rows[] = ["Period: {$periodFrom} to {$periodTo}", '', '', ''];
        $rows[] = ["Accounting basis: {$basis}", '', '', ''];
        $rows[] = ['', '', '', ''];

        // ── Column headings ───────────────────────────────────────────────────
        $rows[] = ['Code', 'Account', 'Source', 'Amount (₦)'];

        // ── Revenue section ───────────────────────────────────────────────────
        $rows[] = ['REVENUE', '', '', ''];
        foreach ($this->report['revenue'] as $line) {
            $rows[] = [
                $line['code'],
                $line['name'],
                ucfirst($line['source'] ?? ''),
                (float) $line['balance'],
            ];
        }
        $rows[] = ['', 'Total Revenue', '', (float) $this->report['total_revenue']];
        $rows[] = ['', '', '', ''];

        // ── Expenses section ──────────────────────────────────────────────────
        $rows[] = ['EXPENSES', '', '', ''];
        foreach ($this->report['expenses'] as $line) {
            $rows[] = [
                $line['code'],
                $line['name'],
                ucfirst($line['source'] ?? ''),
                (float) $line['balance'],
            ];
        }
        $rows[] = ['', 'Total Expenses', '', (float) $this->report['total_expenses']];
        $rows[] = ['', '', '', ''];

        // ── Net result ────────────────────────────────────────────────────────
        $label = $this->report['is_profit'] ? 'NET PROFIT' : 'NET LOSS';
        $rows[] = ['', $label, '', (float) abs($this->report['net_profit'])];

        // ── Footer ────────────────────────────────────────────────────────────
        $rows[] = ['', '', '', ''];
        $rows[] = ['', 'Generated: ' . now()->format('d M Y H:i'), '', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Applied via registerEvents for full control
        return [];
    }

    public function registerEvents(): array
    {
        $report = $this->report;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($report) {
                $sheet   = $event->sheet->getDelegate();
                $maxRow  = $sheet->getHighestRow();
                $numFmt  = '#,##0.00';
                $green   = '008751';
                $lightG  = 'F0FDF4';
                $red     = 'FEF2F2';

                // ── Company name (row 1) ──────────────────────────────────────
                $sheet->mergeCells('A1:D1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // ── Title (row 2) ─────────────────────────────────────────────
                $sheet->mergeCells('A2:D2');
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // ── Period + basis (rows 3–4) ─────────────────────────────────
                foreach ([3, 4] as $r) {
                    $sheet->mergeCells("A{$r}:D{$r}");
                    $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9);
                    $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('6B7280');
                }

                // ── Column heading row (row 6) ────────────────────────────────
                $sheet->getStyle('A6:D6')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $green]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('D6')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // ── Walk all rows to style section headers, data, totals ──────
                for ($r = 7; $r <= $maxRow; $r++) {
                    $a = (string) $sheet->getCell("A{$r}")->getValue();
                    $b = (string) $sheet->getCell("B{$r}")->getValue();

                    // Section header rows (REVENUE / EXPENSES)
                    if (in_array($a, ['REVENUE', 'EXPENSES'])) {
                        $sheet->mergeCells("A{$r}:D{$r}");
                        $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                            'font'      => ['bold' => true, 'size' => 10],
                            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                        ]);
                        continue;
                    }

                    // Total rows
                    if (str_starts_with($b, 'Total ')) {
                        $bgColor = str_contains($b, 'Revenue') ? $lightG : $red;
                        $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                            'font'  => ['bold' => true],
                            'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]],
                        ]);
                        $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode($numFmt);
                        $sheet->getStyle("D{$r}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        continue;
                    }

                    // NET PROFIT / NET LOSS row
                    if (in_array($b, ['NET PROFIT', 'NET LOSS'])) {
                        $bgColor = $b === 'NET PROFIT' ? $lightG : 'FEE2E2';
                        $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                            'font'    => ['bold' => true, 'size' => 12],
                            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
                        ]);
                        $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode($numFmt);
                        $sheet->getStyle("D{$r}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        continue;
                    }

                    // Data rows — format the amount column
                    $d = $sheet->getCell("D{$r}")->getValue();
                    if (is_numeric($d) && $d != '') {
                        $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode($numFmt);
                        $sheet->getStyle("D{$r}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }

                // ── Freeze pane below header block ────────────────────────────
                $sheet->freezePane('A7');
            },
        ];
    }
}
