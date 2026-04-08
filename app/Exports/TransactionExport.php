<?php

namespace App\Exports;

use App\Models\Tenant;
use Illuminate\Support\Collection;
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

class TransactionExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly Collection $transactions,
        private readonly Tenant     $tenant,
        private readonly array      $filters = [],
    ) {}

    public function title(): string
    {
        return 'Transactions';
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 14, 'C' => 16, 'D' => 48, 'E' => 20, 'F' => 12, 'G' => 20];
    }

    public function array(): array
    {
        $rows = [];

        // Header block
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', '', '', '', ''];
        $rows[] = ['Journal Transactions Export', '', '', '', '', '', ''];

        $filterDesc = $this->buildFilterDesc();
        $rows[] = [$filterDesc ?: 'All transactions', '', '', '', '', '', ''];
        $rows[] = ['Generated: ' . now()->format('d M Y H:i'), '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', ''];

        // Column headings
        $rows[] = ['Reference', 'Date', 'Type', 'Description', 'Amount (₦)', 'Status', 'Posted By'];

        // Data
        foreach ($this->transactions as $tx) {
            $rows[] = [
                $tx->reference,
                $tx->transaction_date->format('d M Y'),
                ucfirst(str_replace('_', ' ', $tx->type)),
                $tx->description,
                (float) $tx->amount,
                ucfirst($tx->status),
                $tx->creator->name ?? '—',
            ];

            // Journal entry sub-rows
            foreach ($tx->journalEntries as $entry) {
                $rows[] = [
                    '',
                    '',
                    '  ' . ucfirst($entry->entry_type),
                    '    ' . ($entry->account->code ?? '') . ' — ' . ($entry->account->name ?? '') . ($entry->description ? ' · ' . $entry->description : ''),
                    $entry->entry_type === 'debit' ? (float) $entry->amount : '',
                    $entry->entry_type === 'credit' ? (float) $entry->amount : '',
                    '',
                ];
            }
        }

        // Totals
        $total = $this->transactions->sum('amount');
        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['', '', '', 'Total', (float) $total, '', ''];
        $rows[] = ['', '', '', 'Records: ' . $this->transactions->count(), '', '', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        $transactions = $this->transactions;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($transactions) {
                $sheet  = $event->sheet->getDelegate();
                $maxRow = $sheet->getHighestRow();
                $numFmt = '#,##0.00';
                $green  = '008751';

                // Title block
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->mergeCells('A2:G2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
                foreach ([3, 4] as $r) {
                    $sheet->mergeCells("A{$r}:G{$r}");
                    $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9);
                    $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('6B7280');
                }

                // Column heading row (6)
                $sheet->getStyle('A6:G6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $green]],
                ]);
                $sheet->getStyle('E6:F6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->freezePane('A7');

                // Style data rows
                $isEntryRow = false;
                for ($r = 7; $r <= $maxRow; $r++) {
                    $aVal = (string) $sheet->getCell("A{$r}")->getValue();
                    $bVal = (string) $sheet->getCell("B{$r}")->getValue();
                    $dVal = (string) $sheet->getCell("D{$r}")->getValue();

                    // Total / summary rows
                    if ($dVal === 'Total' || str_starts_with($dVal, 'Records:')) {
                        $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                            'font'    => ['bold' => true],
                            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]],
                        ]);
                        if ($dVal === 'Total') {
                            $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode($numFmt);
                            $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                        continue;
                    }

                    // Transaction header rows (have a reference in col A)
                    if ($aVal !== '') {
                        $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 9.5],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                        ]);
                        $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode($numFmt);
                        $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $isEntryRow = false;
                    } else {
                        // Journal entry sub-rows
                        $sheet->getStyle("A{$r}:G{$r}")->getFont()->setSize(8.5);
                        $sheet->getStyle("A{$r}:G{$r}")->getFont()->getColor()->setRGB('4B5563');
                        foreach (['E', 'F'] as $col) {
                            $v = $sheet->getCell("{$col}{$r}")->getValue();
                            if (is_numeric($v) && $v !== '') {
                                $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode($numFmt);
                                $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            }
                        }
                    }
                }
            },
        ];
    }

    private function buildFilterDesc(): string
    {
        $parts = [];
        if (!empty($this->filters['date_from'])) $parts[] = 'From: ' . $this->filters['date_from'];
        if (!empty($this->filters['date_to']))   $parts[] = 'To: '   . $this->filters['date_to'];
        if (!empty($this->filters['type']))      $parts[] = 'Type: ' . ucfirst($this->filters['type']);
        if (!empty($this->filters['search']))    $parts[] = 'Search: "' . $this->filters['search'] . '"';
        return implode('  |  ', $parts);
    }
}
