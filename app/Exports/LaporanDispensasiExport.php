<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Carbon;

class LaporanDispensasiExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithMapping, WithEvents
{
    protected $data;
    protected $tanggal;
    protected $total;

    public function __construct($data, $tanggal)
    {
        $this->data = $data;
        $this->tanggal = $tanggal;
        $this->total = $data->count();
    }

    public function collection()
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Data Dispensasi';
    }

    public function headings(): array
    {
        return [
            'No',
            'NAMA',
            'TEMPAT LAHIR',
            'TANGGAL LAHIR',
        ];
    }

    public function map($record): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $record->nama ?? '',
            $record->tempat_lahir ?? '',
            $record->tanggal_lahir ? $record->tanggal_lahir->format('d/m/Y') : '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,  // No
            'B' => 30, // NAMA
            'C' => 25, // TEMPAT LAHIR
            'D' => 20, // TANGGAL LAHIR
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styles handled in registerEvents
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = 'D'; // Only 4 columns
    
                // Insert rows for header
                $sheet->insertNewRowBefore(1, 3);

                // Title Row 1
                // JUDUL (DATA AJUAN AKTA KELAHIRAN TANGGAL (TANGGAL YANG DIINPUT) KODE TP, TP/LN, TP/SPTJM
                $tanggalStr = Carbon::parse($this->tanggal)->format('d/m/Y');
                $title = "DATA AJUAN AKTA KELAHIRAN TANGGAL {$tanggalStr} KODE TP, TP/LN, TP/SPTJM";

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->setCellValue('A1', $title);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Total Row 2
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->setCellValue('A2', "Total : {$this->total}");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT, // Requested aligned left implicitly by example
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Row 3 is empty space
    
                // Table Header Row 4
                $headerRow = 4;
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'], // Blue background
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Add Borders to table
                $lastRow = 4 + $this->total;
                $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);

                // Center align No and Tanggal Lahir
                $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D5:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(4)->setRowHeight(25);
            },
        ];
    }
}
