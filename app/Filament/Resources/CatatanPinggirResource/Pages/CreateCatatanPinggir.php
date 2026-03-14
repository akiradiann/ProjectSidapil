<?php

namespace App\Filament\Resources\CatatanPinggirResource\Pages;

use App\Filament\Resources\CatatanPinggirResource;
use App\Models\StatusAjuan;
use App\Models\ServiceRequest;
use App\Models\KategoriLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreateCatatanPinggir extends CreateRecord
{
    protected static string $resource = CatatanPinggirResource::class;



    protected function afterCreate(): void
    {
        // Create corresponding ServiceRequest
        $catatan = $this->record;

        // Get kategori layanan for Catatan Pinggir
        // Perlu dicek apakah ada kategori khusus atau menggunakan kategori umum
        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'LIKE', '%CATATAN PINGGIR%')
            ->orWhere('nama_kategori', 'CATATAN PINGGIR')
            ->first()?->id ?? 1; // Default ke ID 1 jika tidak ditemukan

        $serviceRequest = ServiceRequest::create([
            'nomor_layanan' => null, // Will be auto-generated
            'kategori_layanan_id' => $kategoriLayananId,
            'jenis_layanan_id' => $catatan->layanan_id,
            'jenis_produk_id' => $catatan->produk_id ?? null,
            'status_pelapor_id' => $catatan->status_pelapor_id ?? null,
            'status_ajuan_id' => $catatan->status_ajuan_id,
            'fo_id' => auth()->id(),
            'file_produk' => $catatan->file_produk ?? null,
            'catatan' => $catatan->catatan ?? null,
        ]);

        // Link catatan to service request
        $catatan->service_request_id = $serviceRequest->id;
        $catatan->saveQuietly();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Hapus button "Create Another" - hanya tampilkan Simpan dan Batal
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan'),
            $this->getCancelFormAction(),
        ];
    }
}





