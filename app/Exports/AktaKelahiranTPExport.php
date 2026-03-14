<?php

namespace App\Exports;

use App\Models\AktaKelahiran;
use App\Services\WilayahService;
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
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class AktaKelahiranTPExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithMapping, WithEvents
{
    protected $data;
    protected $bulan;
    protected $tahun;
    protected $total;

    public function __construct($data, $bulan, $tahun)
    {
        $this->data = $data;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->total = $data->count();
    }

    public function collection()
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Data Akta Kelahiran TP';
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Akta',
            'Nama',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Kecamatan',
            'Desa',
            'Nama Pelapor',
            'No HP',
            'Loket',
            'Status Pelapor',
            'Status Ajuan',
            'Tanggal Dibuat',
        ];
    }

    public function map($akta): array
    {
        static $no = 0;
        $no++;

        // Get kecamatan and desa names
        $kecamatanName = $akta->kecamatan_name ?? $akta->kecamatan_id;
        $desaName = $akta->desa_name ?? $akta->desa_id;

        return [
            $no,
            $akta->nomor ?? '',
            $akta->nama ?? '',
            $akta->tempat_lahir ?? '',
            $akta->tanggal_lahir ? $akta->tanggal_lahir->format('d/m/Y') : '',
            $kecamatanName,
            $desaName,
            $akta->nama_pelapor ?? '',
            $akta->no_hp ?? '',
            $akta->jenisLayanan->nama_layanan ?? '',
            $akta->statusPelapor->nama_status ?? '',
            $akta->statusAjuan->nama_status ?? '',
            $akta->created_at ? $akta->created_at->format('d/m/Y H:i') : '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 18,  // Nomor Akta
            'C' => 20,  // Nama
            'D' => 15,  // Tempat Lahir
            'E' => 12,  // Tanggal Lahir
            'F' => 18,  // Kecamatan
            'G' => 22,  // Desa
            'H' => 20,  // Nama Pelapor
            'I' => 12,  // No HP
            'J' => 18,  // Loket
            'K' => 18,  // Status Pelapor
            'L' => 15,  // Status Ajuan
            'M' => 18,  // Tanggal Dibuat
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Insert title row at the beginning
                $sheet->insertNewRowBefore(1, 3);
                
                // Title row (A1:M1 merged) - bulan harus uppercase
                $bulanUpper = strtoupper($this->bulan);
                $sheet->mergeCells('A1:M1');
                $sheet->setCellValue('A1', "DATA AKTA KELAHIRAN KODE TP PERIODE {$bulanUpper} TAHUN {$this->tahun}");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Total row (A2:M2 merged)
                $sheet->mergeCells('A2:M2');
                $sheet->setCellValue('A2', "Total : {$this->total}");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Row 3 is empty (already empty)

                // Header row (row 4) - now at row 4 after insertion
                $headerRow = 4;
                $sheet->getStyle("A{$headerRow}:M{$headerRow}")->applyFromArray([
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

                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(4)->setRowHeight(20);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styles are handled in registerEvents
    }
}

