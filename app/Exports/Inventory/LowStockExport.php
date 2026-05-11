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

class LowStockExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly Collection $items,
        private readonly Tenant     $tenant,
    ) {}

    public function title(): string { return 'Low Stock'; }

    public function columnWidths(): array
    {
        return ['A' => 30, 'B' => 14, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 18];
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', '', '', ''];
        $rows[] = ['Low Stock Report', '', '', '', '', ''];
        $rows[] = ['Generated: ' . now()->format('d M Y H:i'), '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['Item', 'SKU', 'Current Stock', 'Restock Level', 'Shortfall', 'Last Restocked'];

        foreach ($this->items as $item) {
            $rows[] = [
                $item->name,
                $item->sku ?? '—',
                (float) $item->current_stock,
                (float) $item->restock_level,
                (float) $item->shortfall,
                $item->last_restocked?->format('d M Y') ?? 'Never',
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
                $numFmt = '#,##0.000';
                $green  = '008751';

                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 13]]);
                $sheet->mergeCells('A2:F2');
                $sheet->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);
                $sheet->mergeCells('A3:F3');
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);

                $sheet->getStyle('A5:F5')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $green]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                for ($r = 6; $r <= $maxRow; $r++) {
                    foreach (['C', 'D', 'E'] as $col) {
                        $val = $sheet->getCell("{$col}{$r}")->getValue();
                        if (is_numeric($val) && $val !== '') {
                            $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode($numFmt);
                            $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                    }
                    // Highlight rows where shortfall > 0
                    $shortfall = $sheet->getCell("E{$r}")->getValue();
                    if (is_numeric($shortfall) && $shortfall > 0) {
                        $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF2F2']],
                        ]);
                    }
                }
                $sheet->freezePane('A6');
            },
        ];
    }
}
