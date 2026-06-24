<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PencatatanSipilChart extends ChartWidget
{
    protected static ?string $heading = 'Layanan Pencatatan Sipil';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        // Get selected or default month and year
        $activeFilter = $this->filter ?? now()->format('Y-m');
        [$currentYear, $currentMonth] = explode('-', $activeFilter);
        $currentMonth = (int) $currentMonth;
        $currentYear = (int) $currentYear;

        // Array kategori layanan Pencatatan Sipil (id: 1-9)
        $kategoriPencatatanSipil = [
            1 => 'Akta Kelahiran',
            2 => 'Akta Kematian',
            3 => 'Akta Perkawinan',
            4 => 'Akta Perceraian',
            5 => 'Kutipan Dua Akta Kelahiran',
            6 => 'Kutipan Dua Akta Kematian',
            7 => 'Kutipan Dua Akta Perkawinan',
            8 => 'Kutipan Dua Akta Perceraian',
            9 => 'Catatan Pinggir',
        ];

        // Warna berbeda untuk setiap bar
        $colors = [
            '#3b82f6', // Blue
            '#ef4444', // Red
            '#10b981', // Green
            '#f59e0b', // Amber
            '#8b5cf6', // Violet
            '#ec4899', // Pink
            '#06b6d4', // Cyan
            '#f97316', // Orange
            '#6366f1', // Indigo
        ];

        // Query untuk mendapatkan total ajuan per kategori bulan ini
        $ajuanPerKategori = ServiceRequest::select(
            'kategori_layanan_id',
            DB::raw('COUNT(*) as total')
        )
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->whereIn('kategori_layanan_id', [1, 2, 3, 4, 5, 6, 7, 8, 9])
            ->groupBy('kategori_layanan_id')
            ->orderBy('kategori_layanan_id')
            ->pluck('total', 'kategori_layanan_id')
            ->toArray();

        // Prepare data untuk 9 kategori (isi dengan 0 jika tidak ada data)
        $data = [];
        $labels = [];

        foreach ($kategoriPencatatanSipil as $id => $nama) {
            $labels[] = $nama;
            $data[] = $ajuanPerKategori[$id] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Ajuan',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false, // Hide legend karena warna sudah berbeda per bar
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0, // Tidak ada desimal
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45,
                    ],
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $activeFilter = $this->filter ?? now()->format('Y-m');
        $date = \Carbon\Carbon::createFromFormat('Y-m', $activeFilter);
        return 'Data bulan ' . $date->translatedFormat('F Y');
    }

    protected function getFilters(): ?array
    {
        $filters = [];
        $start = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $date = $start->copy()->subMonths($i);
            $key = $date->format('Y-m');
            $filters[$key] = $date->translatedFormat('F Y');
        }
        return $filters;
    }
}
