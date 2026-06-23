<?php

namespace App\Filament\Resources\KartuKeluargaResource\Pages;

use App\Filament\Resources\KartuKeluargaResource;
use App\Models\StatusAjuan;
use App\Models\ServiceRequest;
use App\Models\KategoriLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreateKartuKeluarga extends CreateRecord
{
    protected static string $resource = KartuKeluargaResource::class;



    protected function afterCreate(): void
    {
        // Create corresponding ServiceRequest
        $kk = $this->record;

        // Get kategori layanan for Kartu Keluarga (ID 10 from database)
        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'KARTU KELUARGA')->first()?->id ?? 10;

        $serviceRequest = ServiceRequest::create([
            'nomor_layanan' => null, // Will be auto-generated
            'kategori_layanan_id' => $kategoriLayananId,
            'jenis_layanan_id' => $kk->layanan_id,
            'jenis_produk_id' => $kk->produk_id ?? null,
            'status_pelapor_id' => $kk->status_pelapor_id ?? null,
            'status_ajuan_id' => $kk->status_ajuan_id,
            'fo_id' => auth()->id(),
            'catatan' => $kk->catatan ?? null,
            'no_hp' => $kk->no_hp,
            'nama_pemohon' => $kk->nama_pemohon,
        ]);

        // Link kartu keluarga to service request
        $kk->service_request_id = $serviceRequest->id;
        $kk->saveQuietly();
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
