<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AjuanPerBulanChart extends ChartWidget
{
    protected static ?string $heading = 'Total Ajuan per Bulan';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Get current year
        $currentYear = now()->year;

        // Array nama bulan dalam bahasa Indonesia
        $namaBulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        // Query untuk mendapatkan total ajuan per bulan (exclude SURAT category id: 14)
        $ajuanPerBulan = ServiceRequest::select(
            DB::raw('MONTH(created_at) as bulan'),
            DB::raw('COUNT(*) as total')
        )
            ->whereYear('created_at', $currentYear)
            ->where('kategori_layanan_id', '!=', 14)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->pluck('total', 'bulan')
            ->toArray();

        // Prepare data untuk 12 bulan (isi dengan 0 jika tidak ada data)
        $data = [];
        $labels = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $labels[] = $namaBulan[$bulan];
            $data[] = $ajuanPerBulan[$bulan] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Ajuan',
                    'data' => $data,
                    'borderColor' => '#9185be',
                    'backgroundColor' => 'rgba(145, 133, 190, 0.1)',
                    'fill' => true,
                    'tension' => 0.4, // Smooth line
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0, // Tidak ada desimal
                    ],
                ],
            ],
        ];
    }
}
