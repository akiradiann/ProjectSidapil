<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PendaftaranPendudukChart extends ChartWidget
{
    protected static ?string $heading = 'Layanan Pendaftaran Penduduk';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        // Get current month and year
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Array kategori layanan Pendaftaran Penduduk (id: 10-13)
        $kategoriPendaftaranPenduduk = [
            10 => 'Kartu Keluarga',
            11 => 'Pindah Datang',
            12 => 'KTP-el',
            13 => 'KIA',
        ];

        // Warna berbeda untuk setiap segmen
        $colors = [
            '#3b82f6', // Blue - Kartu Keluarga
            '#10b981', // Green - Pindah Datang
            '#f59e0b', // Amber - KTP-el
            '#8b5cf6', // Violet - KIA
        ];

        // Query untuk mendapatkan total ajuan per kategori bulan ini
        $ajuanPerKategori = ServiceRequest::select(
            'kategori_layanan_id',
            DB::raw('COUNT(*) as total')
        )
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->whereIn('kategori_layanan_id', [10, 11, 12, 13])
            ->groupBy('kategori_layanan_id')
            ->orderBy('kategori_layanan_id')
            ->pluck('total', 'kategori_layanan_id')
            ->toArray();

        // Prepare data untuk 4 kategori (isi dengan 0 jika tidak ada data)
        $data = [];
        $labels = [];

        foreach ($kategoriPendaftaranPenduduk as $id => $nama) {
            $labels[] = $nama;
            $data[] = $ajuanPerKategori[$id] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Ajuan',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        return 'Data bulan ' . now()->format('F Y');
    }
}
