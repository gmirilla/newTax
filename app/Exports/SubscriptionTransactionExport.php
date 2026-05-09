<?php

namespace App\Exports;

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

class SubscriptionTransactionExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly Collection $payments,
        private readonly array      $filters = [],
    ) {}

    public function title(): string
    {
        return 'Subscription Transactions';
    }

    public function columnWidths(): array
    {
        return ['A' => 30, 'B' => 20, 'C' => 24, 'D' => 14, 'E' => 18, 'F' => 12, 'G' => 16];
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['AccountTaxNG — Platform Admin', '', '', '', '', '', ''];
        $rows[] = ['Subscription Transactions Export', '', '', '', '', '', ''];
        $rows[] = [$this->buildFilterDesc() ?: 'All transactions', '', '', '', '', '', ''];
        $rows[] = ['Generated: ' . now()->format('d M Y H:i'), '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', ''];

        $rows[] = ['Company', 'Plan', 'Type', 'Cycle', 'Amount (₦)', 'Status', 'Date'];

        foreach ($this->payments as $payment) {
            $rows[] = [
                $payment->tenant?->name ?? '—',
                $payment->plan?->name  ?? '—',
                $payment->typeLabel(),
                ucfirst($payment->billing_cycle ?? 'monthly'),
                (float) $payment->amount,
                ucfirst($payment->status),
                $payment->paid_at?->format('d M Y') ?? '—',
            ];
        }

        $total = $this->payments->where('status', 'success')->sum('amount');
        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['', '', '', 'Total (successful)', (float) $total, '', ''];
        $rows[] = ['', '', '', 'Records: ' . $this->payments->count(), '', '', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $maxRow = $sheet->getHighestRow();
                $numFmt = '#,##0.00';
                $dark   = '1F2937';

                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A1')->getFont()->getColor()->setRGB('008751');

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
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $dark]],
                ]);
                $sheet->getStyle('E6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->freezePane('A7');

                for ($r = 7; $r <= $maxRow; $r++) {
                    $dVal = (string) $sheet->getCell("D{$r}")->getValue();

                    if (str_starts_with($dVal, 'Total') || str_starts_with($dVal, 'Records:')) {
                        $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                            'font'    => ['bold' => true],
                            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]],
                        ]);
                        if (str_starts_with($dVal, 'Total')) {
                            $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode($numFmt);
                            $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                        continue;
                    }

                    // Alternate row shading
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                        ]);
                    }

                    $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode($numFmt);
                    $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Colour-code status cell (col F)
                    $status = strtolower((string) $sheet->getCell("F{$r}")->getValue());
                    if ($status === 'success') {
                        $sheet->getStyle("F{$r}")->getFont()->getColor()->setRGB('166534');
                    } elseif ($status === 'failed') {
                        $sheet->getStyle("F{$r}")->getFont()->getColor()->setRGB('991B1B');
                    }
                }
            },
        ];
    }

    private function buildFilterDesc(): string
    {
        $parts = [];
        if (!empty($this->filters['search']))    $parts[] = 'Company: "' . $this->filters['search'] . '"';
        if (!empty($this->filters['status']))    $parts[] = 'Status: ' . ucfirst($this->filters['status']);
        if (!empty($this->filters['plan']))      $parts[] = 'Plan ID: ' . $this->filters['plan'];
        if (!empty($this->filters['cycle']))     $parts[] = 'Cycle: ' . ucfirst($this->filters['cycle']);
        if (!empty($this->filters['date_from'])) $parts[] = 'From: ' . $this->filters['date_from'];
        if (!empty($this->filters['date_to']))   $parts[] = 'To: '   . $this->filters['date_to'];
        return implode('  |  ', $parts);
    }
}
