<?php

namespace App\Filament\Resources\PindahDatangResource\Pages;

use App\Filament\Resources\PindahDatangResource;
use App\Models\StatusAjuan;
use App\Models\ServiceRequest;
use App\Models\KategoriLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreatePindahDatang extends CreateRecord
{
    protected static string $resource = PindahDatangResource::class;



    protected function afterCreate(): void
    {
        // Create corresponding ServiceRequest
        $pd = $this->record;

        // Get kategori layanan for Pindah Datang (ID 11 from database)
        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'PINDAH DATANG')->first()?->id ?? 11;

        $serviceRequest = ServiceRequest::create([
            'nomor_layanan' => null, // Will be auto-generated
            'kategori_layanan_id' => $kategoriLayananId,
            'jenis_layanan_id' => $pd->layanan_id,
            'jenis_produk_id' => $pd->produk_id ?? null,
            'status_pelapor_id' => $pd->status_pelapor_id ?? null,
            'status_ajuan_id' => $pd->status_ajuan_id,
            'fo_id' => auth()->id(),
            'catatan' => $pd->catatan ?? null,
        ]);

        // Link pindah datang to service request
        $pd->service_request_id = $serviceRequest->id;
        $pd->saveQuietly();
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
