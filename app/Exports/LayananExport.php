<?php

namespace App\Exports;

use App\Models\ServiceRequest;
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

class LayananExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithMapping, WithEvents
{
    protected $data;
    protected $kategoriNama;
    protected $bulan;
    protected $tahun;
    protected $total;
    protected $headings;
    protected $columnWidths;

    public function __construct($data, $kategoriNama, $bulan, $tahun)
    {
        $this->data = $data;
        $this->kategoriNama = $kategoriNama;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->total = $data->count();

        // Set headings and column widths based on category
        $this->setHeadingsAndWidths();
    }

    protected function setHeadingsAndWidths(): void
    {
        $kategoriUpper = strtoupper($this->kategoriNama);

        if ($kategoriUpper === 'CATATAN PINGGIR') {
            $this->headings = [
                'No',
                'Kode',
                'Nomor',
                'Nama',
                'No HP',
                'Loket Layanan',
                'Status Pelapor',
                'Produk',
                'Status Ajuan',
                'Dibuat',
            ];
            $this->columnWidths = [
                'A' => 6,   // No
                'B' => 15,  // Kode
                'C' => 20,  // Nomor
                'D' => 25,  // Nama
                'E' => 15,  // No HP
                'F' => 20,  // Loket Layanan
                'G' => 18,  // Status Pelapor
                'H' => 15,  // Produk
                'I' => 15,  // Status Ajuan
                'J' => 18,  // Dibuat
            ];
        } else {
            // Get category-specific columns
            $columns = $this->getCategoryColumns($kategoriUpper);
            $this->headings = array_merge(['No'], $columns['headings'], [
                'Loket Layanan',
                'Status Pelapor',
                'Produk',
                'Status Ajuan',
                'Dibuat',
            ]);

            // Convert column widths to letter keys
            $letterKeys = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];
            $widthsArray = ['A' => 6]; // No column
            $idx = 1; // Start from B

            // Add category-specific column widths
            foreach ($columns['headings'] as $heading) {
                $widthsArray[$letterKeys[$idx]] = $columns['widths'][$heading] ?? 15;
                $idx++;
            }

            // Add common column widths
            $commonWidths = [
                'Loket Layanan' => 20,
                'Status Pelapor' => 18,
                'Produk' => 15,
                'Status Ajuan' => 15,
                'Dibuat' => 18,
            ];
            foreach (['Loket Layanan', 'Status Pelapor', 'Produk', 'Status Ajuan', 'Dibuat'] as $heading) {
                $widthsArray[$letterKeys[$idx]] = $commonWidths[$heading];
                $idx++;
            }

            $this->columnWidths = $widthsArray;
        }
    }

    protected function getCategoryColumns(string $kategoriNama): array
    {
        return match ($kategoriNama) {
            'AKTA KELAHIRAN' => [
                'headings' => [
                    'Nomor Akta',
                    'Nama',
                    'Tempat Lahir',
                    'Tanggal Lahir',
                    'Kecamatan',
                    'Desa',
                    'Nama Pelapor',
                    'No HP',
                ],
                'widths' => [
                    'Nomor Akta' => 18,
                    'Nama' => 25,
                    'Tempat Lahir' => 18,
                    'Tanggal Lahir' => 15,
                    'Kecamatan' => 20,
                    'Desa' => 20,
                    'Nama Pelapor' => 25,
                    'No HP' => 15,
                ],
            ],
            'AKTA KEMATIAN' => [
                'headings' => [
                    'Nomor Akta',
                    'Nama Jenazah',
                    'Jenis Kelamin',
                    'Tanggal Kematian',
                    'Kecamatan',
                    'Desa',
                    'Nama Pelapor',
                    'No HP',
                ],
                'widths' => [
                    'Nomor Akta' => 18,
                    'Nama Jenazah' => 25,
                    'Jenis Kelamin' => 15,
                    'Tanggal Kematian' => 18,
                    'Kecamatan' => 20,
                    'Desa' => 20,
                    'Nama Pelapor' => 25,
                    'No HP' => 15,
                ],
            ],
            'AKTA PERKAWINAN' => [
                'headings' => [
                    'Nomor Akta',
                    'Nama Mempelai Laki-Laki',
                    'Nama Mempelai Perempuan',
                    'Tempat Perkawinan Agama',
                    'Tanggal Perkawinan',
                    'Tanggal Pencatatan',
                    'Nama Pelapor',
                    'No HP',
                ],
                'widths' => [
                    'Nomor Akta' => 18,
                    'Nama Mempelai Laki-Laki' => 25,
                    'Nama Mempelai Perempuan' => 25,
                    'Tempat Perkawinan Agama' => 25,
                    'Tanggal Perkawinan' => 18,
                    'Tanggal Pencatatan' => 18,
                    'Nama Pelapor' => 25,
                    'No HP' => 15,
                ],
            ],
            'AKTA PERCERAIAN' => [
                'headings' => [
                    'Nomor Akta',
                    'No Akta Perkawinan',
                    'Tanggal Perkawinan',
                    'Nama Suami',
                    'Nama Istri',
                    'No Penetapan Pengadilan',
                    'Nama Pelapor',
                    'No HP',
                ],
                'widths' => [
                    'Nomor Akta' => 18,
                    'No Akta Perkawinan' => 20,
                    'Tanggal Perkawinan' => 18,
                    'Nama Suami' => 25,
                    'Nama Istri' => 25,
                    'No Penetapan Pengadilan' => 22,
                    'Nama Pelapor' => 25,
                    'No HP' => 15,
                ],
            ],
            'KUTIPAN DUA KELAHIRAN' => [
                'headings' => [
                    'Nomor Kutipan',
                    'No Akta',
                    'Nama',
                    'Kecamatan',
                    'Desa',
                    'Nama Pelapor',
                    'No HP',
                    'Alasan',
                ],
                'widths' => [
                    'Nomor Kutipan' => 18,
                    'No Akta' => 18,
                    'Nama' => 25,
                    'Kecamatan' => 20,
                    'Desa' => 20,
                    'Nama Pelapor' => 25,
                    'No HP' => 15,
                    'Alasan' => 25,
                ],
            ],
            'KUTIPAN DUA KEMATIAN' => [
                'headings' => [
                    'Nomor Kutipan',
                    'No Akta',
                    'Nama',
                    'Kecamatan',
                    'Desa',
                    'Nama Pelapor',
                    'No HP',
                    'Alasan',
                ],
                'widths' => [
                    'Nomor Kutipan' => 18,
                    'No Akta' => 18,
                    'Nama' => 25,
                    'Kecamatan' => 20,
                    'Desa' => 20,
                    'Nama Pelapor' => 25,
                    'No HP' => 15,
                    'Alasan' => 25,
                ],
            ],
            'KUTIPAN DUA PERKAWINAN' => [
                'headings' => [
                    'Nomor Kutipan',
                    'No Akta',
                    'Nama Suami',
                    'Nama Istri',
                    'Nama Pelapor',
                    'No HP',
                    'Alasan',
                ],
                'widths' => [
                    'Nomor Kutipan' => 18,
                    'No Akta' => 18,
                    'Nama Suami' => 25,
                    'Nama Istri' => 25,
                    'Nama Pelapor' => 25,
                    'No HP' => 15,
                    'Alasan' => 25,
                ],
            ],
            'SURAT' => [
                'headings' => [
                    'Nomor',
                    'Jenis',
                    'Nama',
                    'No Akta',
                    'Tujuan',
                    'Nama Pemohon',
                    'No. HP',
                ],
                'widths' => [
                    'Nomor' => 20,
                    'Jenis' => 15,
                    'Nama' => 25,
                    'No Akta' => 20,
                    'Tujuan' => 20,
                    'Nama Pemohon' => 25,
                    'No. HP' => 15,
                ],
            ],
            'KUTIPAN DUA PERCERAIAN' => [
                'headings' => [
                    'Nomor Kutipan',
                    'No Akta',
                    'Nama Suami',
                    'Nama Istri',
                    'Nama Pelapor',
                    'No HP',
                    'Alasan',
                ],
                'widths' => [
                    'Nomor Kutipan' => 18,
                    'No Akta' => 18,
                    'Nama Suami' => 25,
                    'Nama Istri' => 25,
                    'Nama Pelapor' => 25,
                    'No HP' => 15,
                    'Alasan' => 25,
                ],
            ],
            'KARTU KELUARGA' => [
                'headings' => [
                    'No KK',
                    'Nama Kepala Keluarga',
                    'Nama Pemohon',
                ],
                'widths' => [
                    'No KK' => 18,
                    'Nama Kepala Keluarga' => 25,
                    'Nama Pemohon' => 25,
                ],
            ],
            'PINDAH DATANG' => [
                'headings' => [
                    'Ajuan',
                    'No KK',
                    'NIK',
                    'Nama Kepala Keluarga',
                    'Nama Pemohon',
                ],
                'widths' => [
                    'Ajuan' => 15,
                    'No KK' => 18,
                    'NIK' => 18,
                    'Nama Kepala Keluarga' => 25,
                    'Nama Pemohon' => 25,
                ],
            ],
            'KTP-EL' => [
                'headings' => [
                    'Nomor',
                    'NIK',
                    'Nama',
                ],
                'widths' => [
                    'Nomor' => 15,
                    'NIK' => 18,
                    'Nama' => 25,
                ],
            ],
            'KIA' => [
                'headings' => [
                    'Nomor',
                    'NIK',
                    'Nama',
                ],
                'widths' => [
                    'Nomor' => 15,
                    'NIK' => 18,
                    'Nama' => 25,
                ],
            ],
            default => [
                'headings' => [],
                'widths' => [],
            ],
        };
    }

    public function collection()
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Data Layanan';
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($record): array
    {
        static $no = 0;
        $no++;

        $kategoriUpper = strtoupper($this->kategoriNama);

        if ($kategoriUpper === 'CATATAN PINGGIR') {
            return $this->mapCatatanPinggir($record, $no);
        }

        // Map based on category
        $categoryData = $this->getCategoryData($record, $kategoriUpper);
        $commonData = [
            $record->jenisLayanan->nama_layanan ?? '',
            $record->statusPelapor->nama_status ?? '',
            $record->jenisProduk->nama_produk ?? '',
            $record->statusAjuan->nama_status ?? '',
            $record->created_at ? $record->created_at->format('d/m/Y H:i') : '',
        ];

        return array_merge([$no], $categoryData, $commonData);
    }

    protected function mapCatatanPinggir($record, $no): array
    {
        $catatan = $record->catatanPinggir;

        return [
            $no,
            $catatan->kode ?? '',
            $catatan->nomor ?? '',
            $catatan->nama ?? '',
            $catatan->no_hp ?? '',
            $record->jenisLayanan->nama_layanan ?? '',
            $record->statusPelapor->nama_status ?? '',
            $record->jenisProduk->nama_produk ?? '',
            $record->statusAjuan->nama_status ?? '',
            $record->created_at ? $record->created_at->format('d/m/Y H:i') : '',
        ];
    }

    protected function getCategoryData($record, string $kategoriNama): array
    {
        return match ($kategoriNama) {
            'AKTA KELAHIRAN' => [
                $record->aktaKelahiran->nomor ?? '',
                $record->aktaKelahiran->nama ?? '',
                $record->aktaKelahiran->tempat_lahir ?? '',
                $record->aktaKelahiran->tanggal_lahir ? $record->aktaKelahiran->tanggal_lahir->format('d/m/Y') : '',
                $record->aktaKelahiran->kecamatan_name ?? '',
                $record->aktaKelahiran->desa_name ?? '',
                $record->aktaKelahiran->nama_pelapor ?? '',
                $record->aktaKelahiran->no_hp ?? '',
            ],
            'AKTA KEMATIAN' => [
                $record->aktaKematian->nomor ?? '',
                $record->aktaKematian->nama ?? '',
                $record->aktaKematian->jenis_kelamin ?? '',
                $record->aktaKematian->tanggal_kematian ? $record->aktaKematian->tanggal_kematian->format('d/m/Y') : '',
                $record->aktaKematian->kecamatan_name ?? '',
                $record->aktaKematian->desa_name ?? '',
                $record->aktaKematian->nama_pelapor ?? '',
                $record->aktaKematian->no_hp ?? '',
            ],
            'AKTA PERKAWINAN' => [
                $record->aktaPerkawinan->nomor ?? '',
                $record->aktaPerkawinan->nama_mempelai_laki ?? '',
                $record->aktaPerkawinan->nama_mempelai_perempuan ?? '',
                $record->aktaPerkawinan->tempat_perkawinan_agama ?? '',
                $record->aktaPerkawinan->tanggal_perkawinan ? $record->aktaPerkawinan->tanggal_perkawinan->format('d/m/Y') : '',
                $record->aktaPerkawinan->tanggal_pencatatan ? $record->aktaPerkawinan->tanggal_pencatatan->format('d/m/Y') : '',
                $record->aktaPerkawinan->nama_pelapor ?? '',
                $record->aktaPerkawinan->no_hp ?? '',
            ],
            'AKTA PERCERAIAN' => [
                $record->aktaPerceraian->nomor ?? '',
                $record->aktaPerceraian->nomor_akta_perkawinan ?? '',
                $record->aktaPerceraian->tanggal_perkawinan ? $record->aktaPerceraian->tanggal_perkawinan->format('d/m/Y') : '',
                $record->aktaPerceraian->nama_suami ?? '',
                $record->aktaPerceraian->nama_istri ?? '',
                $record->aktaPerceraian->nomor_penetapan_pengadilan ?? '',
                $record->aktaPerceraian->nama_pelapor ?? '',
                $record->aktaPerceraian->no_hp ?? '',
            ],
            'KUTIPAN DUA KELAHIRAN' => [
                $record->kutipanDuaAktaKelahiran->nomor ?? '',
                $record->kutipanDuaAktaKelahiran->no_akta ?? '',
                $record->kutipanDuaAktaKelahiran->nama ?? '',
                $record->kutipanDuaAktaKelahiran->kecamatan_id ? (\App\Services\WilayahService::getDistrict($record->kutipanDuaAktaKelahiran->kecamatan_id)['name'] ?? $record->kutipanDuaAktaKelahiran->kecamatan_id) : '',
                ($record->kutipanDuaAktaKelahiran->kecamatan_id && $record->kutipanDuaAktaKelahiran->desa_id) ? (\App\Services\WilayahService::getVillage($record->kutipanDuaAktaKelahiran->kecamatan_id, $record->kutipanDuaAktaKelahiran->desa_id)['name'] ?? $record->kutipanDuaAktaKelahiran->desa_id) : '',
                $record->kutipanDuaAktaKelahiran->nama_pelapor ?? '',
                $record->kutipanDuaAktaKelahiran->no_hp ?? '',
                $record->kutipanDuaAktaKelahiran->alasan ?? '',
            ],
            'KUTIPAN DUA KEMATIAN' => [
                $record->kutipanDuaAktaKematian->nomor ?? '',
                $record->kutipanDuaAktaKematian->no_akta ?? '',
                $record->kutipanDuaAktaKematian->nama ?? '',
                $record->kutipanDuaAktaKematian->kecamatan_id ? (\App\Services\WilayahService::getDistrict($record->kutipanDuaAktaKematian->kecamatan_id)['name'] ?? $record->kutipanDuaAktaKematian->kecamatan_id) : '',
                ($record->kutipanDuaAktaKematian->kecamatan_id && $record->kutipanDuaAktaKematian->desa_id) ? (\App\Services\WilayahService::getVillage($record->kutipanDuaAktaKematian->kecamatan_id, $record->kutipanDuaAktaKematian->desa_id)['name'] ?? $record->kutipanDuaAktaKematian->desa_id) : '',
                $record->kutipanDuaAktaKematian->nama_pelapor ?? '',
                $record->kutipanDuaAktaKematian->no_hp ?? '',
                $record->kutipanDuaAktaKematian->alasan ?? '',
            ],
            'KUTIPAN DUA PERKAWINAN' => [
                $record->kutipanDuaAktaPerkawinan->nomor ?? '',
                $record->kutipanDuaAktaPerkawinan->no_akta ?? '',
                $record->kutipanDuaAktaPerkawinan->nama_suami ?? '',
                $record->kutipanDuaAktaPerkawinan->nama_istri ?? '',
                $record->kutipanDuaAktaPerkawinan->nama_pelapor ?? '',
                $record->kutipanDuaAktaPerkawinan->no_hp ?? '',
                $record->kutipanDuaAktaPerkawinan->alasan ?? '',
            ],
            'SURAT' => [
                $record->surat->nomor ?? '',
                $record->surat->jenis ?? '',
                $record->surat->nama ?? '',
                $record->surat->no_akta ?? '',
                $record->surat->tujuan ?? '',
                $record->surat->nama_pemohon ?? '',
                $record->surat->no_hp ?? '',
            ],
            'KUTIPAN DUA PERCERAIAN' => [
                $record->kutipanDuaAktaPerceraian->nomor ?? '',
                $record->kutipanDuaAktaPerceraian->nomor_akta ?? '',
                $record->kutipanDuaAktaPerceraian->nama_suami ?? '',
                $record->kutipanDuaAktaPerceraian->nama_istri ?? '',
                $record->kutipanDuaAktaPerceraian->nama_pelapor ?? '',
                $record->kutipanDuaAktaPerceraian->no_hp ?? '',
                $record->kutipanDuaAktaPerceraian->alasan ?? '',
            ],
            'KARTU KELUARGA' => [
                $record->kartuKeluarga->no_kk ?? '',
                $record->kartuKeluarga->nama_kepala_keluarga ?? '',
                $record->kartuKeluarga->nama_pemohon ?? '',
            ],
            'PINDAH DATANG' => [
                $record->pindahDatang->ajuan ?? '',
                $record->pindahDatang->no_kk ?? '',
                $record->pindahDatang->nik ?? '',
                $record->pindahDatang->nama_kepala_keluarga ?? '',
                $record->pindahDatang->nama_pemohon ?? '',
            ],
            'KTP-EL' => [
                $record->ktpEl->nik ?? '',
                $record->ktpEl->nama ?? '',
            ],
            'KIA' => [
                $record->kia->nik ?? '',
                $record->kia->nama ?? '',
            ],
            default => [],
        };
    }

    public function columnWidths(): array
    {
        return $this->columnWidths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings));

                // Insert title row at the beginning
                $sheet->insertNewRowBefore(1, 3);

                // Title row - Format: DATA LAYANAN {KATEGORI} BULAN {BULAN} TAHUN {TAHUN}
                if ($this->bulan && strtoupper($this->bulan) !== 'TAHUNAN') {
                    $bulanUpper = strtoupper($this->bulan);
                    $title = "DATA LAYANAN {$this->kategoriNama} BULAN {$bulanUpper} TAHUN {$this->tahun}";
                } else {
                    $title = "DATA LAYANAN {$this->kategoriNama} TAHUN {$this->tahun}";
                }
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->setCellValue('A1', $title);
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

                // Total row
                $sheet->mergeCells("A2:{$lastColumn}2");
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

                // Row 3 is empty
    
                // Header row (row 4) - now at row 4 after insertion
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

