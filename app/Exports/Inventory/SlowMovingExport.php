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

class SlowMovingExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly Collection $items,
        private readonly array      $filters,
        private readonly Tenant     $tenant,
    ) {}

    public function title(): string { return 'Slow Moving Inventory'; }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 14, 'C' => 14, 'D' => 14, 'E' => 18, 'F' => 16, 'G' => 14, 'H' => 14];
    }

    public function array(): array
    {
        $rows   = [];
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', '', '', '', '', ''];
        $rows[] = ['Slow-Moving Inventory Report', '', '', '', '', '', '', ''];
        $rows[] = ['Period: last ' . $this->filters['days'] . ' days (' . $this->filters['from'] . ' to ' . $this->filters['to'] . ')', '', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', '', ''];
        $rows[] = ['Item', 'Category', 'SKU', 'Current Stock', 'Units Sold', 'Avg Daily Usage', 'Days Since Last Sale', 'Velocity'];

        foreach ($this->items as $item) {
            $rows[] = [
                $item->name,
                $item->category?->name ?? '—',
                $item->sku ?? '—',
                (float) $item->current_stock,
                (float) $item->units_sold,
                (float) $item->avg_daily_usage,
                $item->days_since_last_sale ?? 'Never sold',
                $item->velocity_label,
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
                $cols   = 8;
                $range  = 'A1:H1';

                $sheet->mergeCells("A1:H1");
                $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 13]]);
                $sheet->mergeCells("A2:H2");
                $sheet->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);
                $sheet->mergeCells("A3:H3");
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);

                $sheet->getStyle('A5:H5')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $green]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $numFmt = '#,##0.000';
                for ($r = 6; $r <= $maxRow; $r++) {
                    foreach (['D', 'E', 'F'] as $col) {
                        $val = $sheet->getCell("{$col}{$r}")->getValue();
                        if (is_numeric($val)) {
                            $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode($numFmt);
                            $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                    }
                    // Highlight dead stock rows
                    if ($sheet->getCell("H{$r}")->getValue() === 'Dead Stock') {
                        $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF2F2']],
                        ]);
                    }
                }
                $sheet->freezePane('A6');
            },
        ];
    }
}
