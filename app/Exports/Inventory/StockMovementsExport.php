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

class StockMovementsExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly Collection $movements,
        private readonly array      $filters,
        private readonly Tenant     $tenant,
    ) {}

    public function title(): string { return 'Stock Movements'; }

    public function columnWidths(): array
    {
        return ['A' => 12, 'B' => 26, 'C' => 12, 'D' => 12, 'E' => 12, 'F' => 14, 'G' => 22, 'H' => 18];
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', '', '', '', '', ''];
        $rows[] = ['Stock Movements Report', '', '', '', '', '', '', ''];
        $rows[] = ['Period: ' . $this->filters['from'] . ' to ' . $this->filters['to'], '', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', '', ''];
        $rows[] = ['Date', 'Item', 'Type', 'Qty In', 'Qty Out', 'Balance', 'Reference / Notes', 'Recorded By'];

        foreach ($this->movements as $m) {
            $isIn  = in_array($m->type, ['restock', 'adjustment_in', 'opening']);
            $qtyIn  = $isIn  ? (float) $m->quantity : '';
            $qtyOut = !$isIn ? (float) $m->quantity : '';

            $rows[] = [
                $m->created_at->format('d M Y H:i'),
                $m->item?->name ?? '—',
                ucfirst(str_replace('_', ' ', $m->type)),
                $qtyIn,
                $qtyOut,
                (float) $m->running_balance,
                $m->notes ?? '—',
                $m->creator?->name ?? '—',
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
                $numFmt = '#,##0.000';
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

                $maxRow = $sheet->getHighestRow();
                for ($r = 6; $r <= $maxRow; $r++) {
                    foreach (['D', 'E', 'F'] as $col) {
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
