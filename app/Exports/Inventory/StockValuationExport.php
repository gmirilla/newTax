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

class StockValuationExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly Collection $items,
        private readonly array      $totals,
        private readonly Tenant     $tenant,
    ) {}

    public function title(): string { return 'Stock Valuation'; }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 30, 'C' => 18, 'D' => 8, 'E' => 14, 'F' => 14, 'G' => 14, 'H' => 18];
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', '', '', '', '', ''];
        $rows[] = ['Stock Valuation Report', '', '', '', '', '', '', ''];
        $rows[] = ['Generated: ' . now()->format('d M Y H:i'), '', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', '', ''];
        $rows[] = ['#', 'Item', 'Category', 'Unit', 'Qty on Hand', 'Avg Cost (₦)', 'Stock Value (₦)', 'Potential Revenue (₦)'];

        $i = 1;
        foreach ($this->items as $item) {
            $rows[] = [
                $i++,
                $item->name,
                $item->category?->name ?? '—',
                $item->unit ?? '—',
                (float) $item->current_stock,
                (float) $item->avg_cost,
                (float) $item->stock_value,
                (float) $item->potential_revenue,
            ];
        }

        $rows[] = ['', 'TOTALS', '', '', '', '', (float) $this->totals['total_stock_value'], (float) $this->totals['total_potential_revenue']];
        $rows[] = ['', '', '', '', '', '', '', ''];
        $rows[] = ['', "Total Items: {$this->totals['total_items']}", '', '', '', '', '', ''];

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

                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 13]]);
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);
                $sheet->mergeCells('A3:H3');
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);

                $sheet->getStyle('A5:H5')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $green]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                for ($r = 6; $r <= $maxRow; $r++) {
                    $b = (string) $sheet->getCell("B{$r}")->getValue();
                    if ($b === 'TOTALS') {
                        $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                            'font'    => ['bold' => true],
                            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
                        ]);
                    }
                    foreach (['E', 'F', 'G', 'H'] as $col) {
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
