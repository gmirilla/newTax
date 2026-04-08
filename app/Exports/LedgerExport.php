<?php

namespace App\Exports;

use App\Models\Tenant;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LedgerExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly array  $report,
        private readonly Tenant $tenant,
    ) {}

    public function title(): string
    {
        return 'General Ledger';
    }

    public function columnWidths(): array
    {
        return ['A' => 12, 'B' => 18, 'C' => 44, 'D' => 18, 'E' => 18, 'F' => 20];
    }

    public function array(): array
    {
        $from = Carbon::parse($this->report['period_start'])->format('d M Y');
        $to   = Carbon::parse($this->report['period_end'])->format('d M Y');
        $rows = [];

        // Header block
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', '', '', ''];
        $rows[] = ['General Ledger', '', '', '', '', ''];
        $rows[] = ["Period: {$from} to {$to}", '', '', '', '', ''];
        if ($this->report['account_code']) {
            $rows[] = ['Account filter: ' . $this->report['account_code'], '', '', '', '', ''];
        }
        $rows[] = ['', '', '', '', '', ''];

        // Column headings
        $rows[] = ['Date', 'Reference', 'Description', 'Debit (₦)', 'Credit (₦)', 'Balance (₦)'];

        foreach ($this->report['accounts'] as $acct) {
            // Account header
            $rows[] = [
                "{$acct['code']} — {$acct['name']}",
                '', '', '', '',
                ucfirst($acct['type']),
            ];

            // Opening balance
            $rows[] = ['', 'OPENING', 'Balance brought forward', '', '', (float) $acct['opening_balance']];

            foreach ($acct['lines'] as $line) {
                $rows[] = [
                    $line['date'],
                    $line['reference'],
                    $line['description'],
                    $line['debit']  !== null ? (float) $line['debit']  : '',
                    $line['credit'] !== null ? (float) $line['credit'] : '',
                    (float) $line['balance'],
                ];
            }

            // Closing balance
            $rows[] = ['', 'CLOSING', 'Balance carried forward', '', '', (float) $acct['closing_balance']];
            $rows[] = ['', '', '', '', '', ''];
        }

        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['', 'Generated: ' . now()->format('d M Y H:i'), '', '', '', ''];

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
                $sheet  = $event->sheet->getDelegate();
                $maxRow = $sheet->getHighestRow();
                $numFmt = '#,##0.00';
                $green  = '008751';

                // Title rows
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->mergeCells('A2:F2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
                foreach ([3, 4, 5] as $r) {
                    if ((string) $sheet->getCell("A{$r}")->getValue() === '') break;
                    $sheet->mergeCells("A{$r}:F{$r}");
                    $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9);
                    $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('6B7280');
                }

                // Find and style the column heading row (first row where A="Date")
                $headingRow = null;
                for ($r = 1; $r <= min($maxRow, 8); $r++) {
                    if ((string) $sheet->getCell("A{$r}")->getValue() === 'Date') {
                        $headingRow = $r;
                        break;
                    }
                }
                if ($headingRow) {
                    $sheet->getStyle("A{$headingRow}:F{$headingRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $green]],
                    ]);
                    foreach (['D', 'E', 'F'] as $col) {
                        $sheet->getStyle("{$col}{$headingRow}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                    $sheet->freezePane("A" . ($headingRow + 1));
                }

                // Style all data rows
                for ($r = ($headingRow ?? 6) + 1; $r <= $maxRow; $r++) {
                    $a = (string) $sheet->getCell("A{$r}")->getValue();
                    $b = (string) $sheet->getCell("B{$r}")->getValue();

                    // Account header rows (code — name in col A)
                    if (str_contains($a, ' — ')) {
                        $sheet->mergeCells("A{$r}:E{$r}");
                        $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                        ]);
                        continue;
                    }

                    // Opening / Closing balance rows
                    if ($b === 'OPENING' || $b === 'CLOSING') {
                        $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'italic' => true, 'size' => 9],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAFAFA']],
                        ]);
                        if ($b === 'CLOSING') {
                            $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getBottom()
                                ->setBorderStyle(Border::BORDER_MEDIUM);
                        }
                        $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode($numFmt);
                        $sheet->getStyle("F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        continue;
                    }

                    // Regular entry rows
                    foreach (['D', 'E', 'F'] as $col) {
                        $val = $sheet->getCell("{$col}{$r}")->getValue();
                        if (is_numeric($val) && $val !== '') {
                            $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode($numFmt);
                            $sheet->getStyle("{$col}{$r}")->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                    }
                }
            },
        ];
    }
}
