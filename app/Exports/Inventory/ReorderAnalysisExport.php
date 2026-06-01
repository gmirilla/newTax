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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReorderAnalysisExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly Collection $items,
        private readonly array      $filters,
        private readonly Tenant     $tenant,
    ) {}

    public function title(): string { return 'Reorder Analysis'; }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 14, 'C' => 16, 'D' => 16, 'E' => 18, 'F' => 18, 'G' => 16];
    }

    public function array(): array
    {
        $rows   = [];
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', '', '', '', ''];
        $rows[] = ['Reorder Analysis Report', '', '', '', '', '', ''];
        $rows[] = ['Based on last ' . $this->filters['days'] . ' days of sales data', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['Item', 'Category', 'Current Stock', 'Restock Level', 'Avg Daily Usage', 'Days of Stock', 'Suggested Reorder Qty', 'Status'];

        // Fix column widths to match 8 cols
        foreach ($this->items as $item) {
            $rows[] = [
                $item->name,
                $item->category?->name ?? '—',
                (float) $item->current_stock,
                (float) $item->restock_level,
                (float) $item->avg_daily_usage,
                $item->days_of_stock ?? 'N/A',
                (float) $item->suggested_reorder_qty,
                $item->reorder_status,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array { return []; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $maxRow = $sheet->getHighestRow();
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

                $numFmt = '#,##0.00';
                for ($r = 6; $r <= $maxRow; $r++) {
                    foreach (['C', 'D', 'E', 'G'] as $col) {
                        $val = $sheet->getCell("{$col}{$r}")->getValue();
                        if (is_numeric($val)) {
                            $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode($numFmt);
                            $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                    }
                    $status = $sheet->getCell("H{$r}")->getValue();
                    $bg = match ($status) {
                        'Out of Stock'  => 'FEE2E2',
                        'Reorder Now'   => 'FEF3C7',
                        'Reorder Soon'  => 'FFF9C4',
                        default         => null,
                    };
                    if ($bg) {
                        $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                        ]);
                    }
                }
                $sheet->freezePane('A6');
            },
        ];
    }
}
