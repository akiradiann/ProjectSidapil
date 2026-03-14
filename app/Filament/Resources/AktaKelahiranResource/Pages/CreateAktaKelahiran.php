<?php

namespace App\Filament\Resources\AktaKelahiranResource\Pages;

use App\Filament\Resources\AktaKelahiranResource;
use App\Models\StatusAjuan;
use App\Models\ServiceRequest;
use App\Models\KategoriLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreateAktaKelahiran extends CreateRecord
{
    protected static string $resource = AktaKelahiranResource::class;



    protected function afterCreate(): void
    {
        // Create corresponding ServiceRequest
        $akta = $this->record;

        // Get kategori layanan for Akta Kelahiran (ID 1 from database)
        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'AKTA KELAHIRAN')->first()?->id ?? 1;

        $serviceRequest = ServiceRequest::create([
            'nomor_layanan' => null, // Will be auto-generated
            'kategori_layanan_id' => $kategoriLayananId,
            'jenis_layanan_id' => $akta->layanan_id,
            'jenis_produk_id' => $akta->produk_id ?? null,
            'status_pelapor_id' => $akta->status_pelapor_id ?? null,
            'status_ajuan_id' => $akta->status_ajuan_id,
            'fo_id' => auth()->id(),
            'file_produk' => $akta->file_produk ?? null,
            'catatan' => $akta->catatan ?? null,
        ]);

        // Link akta to service request
        $akta->service_request_id = $serviceRequest->id;
        $akta->saveQuietly();
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

