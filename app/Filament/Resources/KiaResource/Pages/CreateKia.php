<?php

namespace App\Filament\Resources\KiaResource\Pages;

use App\Filament\Resources\KiaResource;
use App\Models\StatusAjuan;
use App\Models\ServiceRequest;
use App\Models\KategoriLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreateKia extends CreateRecord
{
    protected static string $resource = KiaResource::class;



    protected function afterCreate(): void
    {
        // Create corresponding ServiceRequest
        $kia = $this->record;

        // Get kategori layanan for KIA (ID 13 from database)
        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'KIA')->first()?->id ?? 13;

        $serviceRequest = ServiceRequest::create([
            'nomor_layanan' => null, // Will be auto-generated
            'kategori_layanan_id' => $kategoriLayananId,
            'jenis_layanan_id' => $kia->layanan_id,
            'jenis_produk_id' => $kia->produk_id ?? null,
            'status_pelapor_id' => $kia->status_pelapor_id ?? null,
            'status_ajuan_id' => $kia->status_ajuan_id,
            'fo_id' => auth()->id(),
            'catatan' => $kia->catatan ?? null,
            'no_hp' => $kia->no_hp,
            'nama_pemohon' => $kia->nama,
        ]);

        // Link KIA to service request
        $kia->service_request_id = $serviceRequest->id;
        $kia->saveQuietly();
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
