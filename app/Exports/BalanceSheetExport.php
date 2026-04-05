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

class BalanceSheetExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly array  $report,
        private readonly Tenant $tenant,
    ) {}

    public function title(): string
    {
        return 'Balance Sheet';
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 40, 'C' => 12, 'D' => 22];
    }

    public function array(): array
    {
        $asOf = \Carbon\Carbon::parse($this->report['as_of'])->format('d M Y');
        $rows = [];

        // Header block
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', ''];
        $rows[] = ['Balance Sheet', '', '', ''];
        $rows[] = ["As of: {$asOf}", '', '', ''];
        $rows[] = [$this->report['is_approximate'] ? 'Note: approximate — not all transactions are journalised' : '', '', '', ''];
        $rows[] = ['', '', '', ''];

        // Column headings
        $rows[] = ['Code', 'Account', 'Source', 'Balance (₦)'];

        // Assets
        $rows[] = ['ASSETS', '', '', ''];
        foreach ($this->report['assets'] as $line) {
            $rows[] = [$line['code'], $line['name'], ucfirst($line['source'] ?? ''), (float) $line['balance']];
        }
        $rows[] = ['', 'Total Assets', '', (float) $this->report['total_assets']];
        $rows[] = ['', '', '', ''];

        // Liabilities
        $rows[] = ['LIABILITIES', '', '', ''];
        foreach ($this->report['liabilities'] as $line) {
            $rows[] = [$line['code'], $line['name'], ucfirst($line['source'] ?? ''), (float) $line['balance']];
        }
        $rows[] = ['', 'Total Liabilities', '', (float) $this->report['total_liabilities']];
        $rows[] = ['', '', '', ''];

        // Equity
        $rows[] = ['EQUITY', '', '', ''];
        foreach ($this->report['equity'] as $line) {
            $rows[] = [$line['code'], $line['name'], ucfirst($line['source'] ?? ''), (float) $line['balance']];
        }
        $rows[] = ['', 'Total Equity', '', (float) $this->report['total_equity']];
        $rows[] = ['', '', '', ''];

        // Total Liabilities + Equity
        $rows[] = ['', 'Total Liabilities + Equity', '', (float) ($this->report['total_liabilities'] + $this->report['total_equity'])];
        $rows[] = ['', '', '', ''];
        $rows[] = ['', 'Generated: ' . now()->format('d M Y H:i'), '', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
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

                // Title rows
                $sheet->mergeCells('A1:D1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->mergeCells('A2:D2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                foreach ([3, 4] as $r) {
                    $sheet->mergeCells("A{$r}:D{$r}");
                    $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9);
                    $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('6B7280');
                }

                // Column heading row (6)
                $sheet->getStyle('A6:D6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $green]],
                ]);
                $sheet->getStyle('D6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sectionColors = ['ASSETS' => 'EFF6FF', 'LIABILITIES' => 'FEF2F2', 'EQUITY' => 'F0FDF4'];

                for ($r = 7; $r <= $maxRow; $r++) {
                    $a = (string) $sheet->getCell("A{$r}")->getValue();
                    $b = (string) $sheet->getCell("B{$r}")->getValue();

                    if (isset($sectionColors[$a])) {
                        $sheet->mergeCells("A{$r}:D{$r}");
                        $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                        ]);
                        continue;
                    }

                    if (str_starts_with($b, 'Total ')) {
                        $isFinal = $b === 'Total Liabilities + Equity';
                        $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                            'font'    => ['bold' => true, 'size' => $isFinal ? 11 : 10],
                            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isFinal ? 'F0FDF4' : 'F9FAFB']],
                            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]],
                        ]);
                        $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode($numFmt);
                        $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        continue;
                    }

                    $d = $sheet->getCell("D{$r}")->getValue();
                    if (is_numeric($d) && $d != '') {
                        $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode($numFmt);
                        $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }

                $sheet->freezePane('A7');
            },
        ];
    }
}
