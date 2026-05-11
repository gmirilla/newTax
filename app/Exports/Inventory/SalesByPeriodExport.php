<?php

namespace App\Exports\Inventory;

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

class SalesByPeriodExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array      $totals,
        private readonly array      $filters,
        private readonly Tenant     $tenant,
    ) {}

    public function title(): string { return 'Sales by Period'; }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 10, 'C' => 18, 'D' => 18, 'E' => 18];
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', '', ''];
        $rows[] = ['Sales by Period Report', '', '', '', ''];
        $rows[] = ['Period: ' . $this->filters['from'] . ' to ' . $this->filters['to'] . ' | Grouped by: ' . ucfirst($this->filters['group_by']), '', '', '', ''];
        $rows[] = ['', '', '', '', ''];
        $rows[] = ['Period', 'Orders', 'Revenue (₦)', 'COGS (₦)', 'Gross Profit (₦)'];

        foreach ($this->rows as $row) {
            $rows[] = [
                $row->period,
                (int) $row->orders,
                (float) $row->revenue,
                (float) $row->cogs,
                (float) $row->gross_profit,
            ];
        }

        $rows[] = [
            'TOTALS',
            (int) $this->totals['orders'],
            (float) $this->totals['revenue'],
            (float) $this->totals['cogs'],
            (float) $this->totals['gross_profit'],
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array { return []; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $maxRow = $sheet->getHighestRow();
                $numFmt = '#,##0.00';
                $green  = '008751';

                $sheet->mergeCells('A1:E1');
                $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 13]]);
                $sheet->mergeCells('A2:E2');
                $sheet->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);
                $sheet->mergeCells('A3:E3');
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);

                $sheet->getStyle('A5:E5')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $green]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                for ($r = 6; $r <= $maxRow; $r++) {
                    $a = (string) $sheet->getCell("A{$r}")->getValue();
                    if ($a === 'TOTALS') {
                        $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
                            'font'    => ['bold' => true],
                            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
                        ]);
                    }
                    foreach (['C', 'D', 'E'] as $col) {
                        $val = $sheet->getCell("{$col}{$r}")->getValue();
                        if (is_numeric($val) && $val !== '') {
                            $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode($numFmt);
                            $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                    }
                }
                $sheet->freezePane('A6');
            },
        ];
    }
}
