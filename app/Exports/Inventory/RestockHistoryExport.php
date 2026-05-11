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

class RestockHistoryExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly Collection $requests,
        private readonly array      $filters,
        private readonly Tenant     $tenant,
    ) {}

    public function title(): string { return 'Restock History'; }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 26, 'C' => 12, 'D' => 14, 'E' => 14, 'F' => 20, 'G' => 16, 'H' => 16, 'I' => 14];
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = [$this->tenant->company_name ?? $this->tenant->name, '', '', '', '', '', '', '', ''];
        $rows[] = ['Restock History Report', '', '', '', '', '', '', '', ''];
        $rows[] = ['Period: ' . $this->filters['from'] . ' to ' . $this->filters['to'], '', '', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', '', '', ''];
        $rows[] = ['Request No.', 'Item', 'Qty', 'Unit Cost (₦)', 'Total Cost (₦)', 'Supplier', 'Requested By', 'Approved By', 'Received Date'];

        foreach ($this->requests as $rr) {
            $rows[] = [
                $rr->request_number,
                $rr->item?->name ?? '—',
                (float) $rr->quantity_requested,
                (float) $rr->unit_cost,
                (float) $rr->totalCost(),
                $rr->supplier_name ?? '—',
                $rr->requester?->name ?? '—',
                $rr->approver?->name ?? '—',
                $rr->received_at?->format('d M Y') ?? '—',
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
                $numFmt = '#,##0.00';
                $green  = '008751';

                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 13]]);
                $sheet->mergeCells('A2:I2');
                $sheet->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);
                $sheet->mergeCells('A3:I3');
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);

                $sheet->getStyle('A5:I5')->applyFromArray([
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
                }
                $sheet->freezePane('A6');
            },
        ];
    }
}
