<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Card 1: Total Ajuan Hari Ini (exclude SURAT category id: 14)
        $totalHariIni = ServiceRequest::whereDate('created_at', today())
            ->where('kategori_layanan_id', '!=', 14)
            ->count();

        // Card 2: Total Ajuan Bulan Ini (exclude SURAT category id: 14)
        $totalBulanIni = ServiceRequest::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('kategori_layanan_id', '!=', 14)
            ->count();

        // Card 3: Persentase Tingkat Penyelesaian (exclude SURAT category id: 14)
        $totalAjuan = ServiceRequest::where('kategori_layanan_id', '!=', 14)->count();
        $totalSelesai = ServiceRequest::where('status_ajuan_id', 5) // 5 = SELESAI
            ->where('kategori_layanan_id', '!=', 14)
            ->count();

        $persentasePenyelesaian = $totalAjuan > 0
            ? round(($totalSelesai / $totalAjuan) * 100, 1)
            : 0;

        // Card 4: Total Ajuan Dalam Proses (status: DIPROSES, DITOLAK, SIAP KIRIM, SIAP DIAMBIL)
        // Exclude status SELESAI (id: 5) and SURAT category (id: 14)
        $totalDalamProses = ServiceRequest::whereIn('status_ajuan_id', [1, 2, 3, 4])
            ->where('kategori_layanan_id', '!=', 14)
            ->count();

        return [
            Stat::make('Total Ajuan Hari Ini', $totalHariIni)
                ->description('Ajuan yang masuk hari ini')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Total Ajuan Bulan Ini', $totalBulanIni)
                ->description('Ajuan bulan ' . now()->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('Tingkat Penyelesaian', $persentasePenyelesaian . '%')
                ->description('Ajuan yang sudah selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Ajuan Dalam Proses', $totalDalamProses)
                ->description('Belum selesai')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}

