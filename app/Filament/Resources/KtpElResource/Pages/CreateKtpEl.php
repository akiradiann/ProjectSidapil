<?php

namespace App\Filament\Resources\KtpElResource\Pages;

use App\Filament\Resources\KtpElResource;
use App\Models\StatusAjuan;
use App\Models\ServiceRequest;
use App\Models\KategoriLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreateKtpEl extends CreateRecord
{
    protected static string $resource = KtpElResource::class;



    protected function afterCreate(): void
    {
        // Create corresponding ServiceRequest
        $ktp = $this->record;

        // Get kategori layanan for KTP EL (ID 12 from database)
        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'KTP-EL')->first()?->id ?? 12;

        $serviceRequest = ServiceRequest::create([
            'nomor_layanan' => null, // Will be auto-generated
            'kategori_layanan_id' => $kategoriLayananId,
            'jenis_layanan_id' => $ktp->layanan_id,
            'jenis_produk_id' => $ktp->produk_id ?? null,
            'status_pelapor_id' => $ktp->status_pelapor_id ?? null,
            'status_ajuan_id' => $ktp->status_ajuan_id,
            'fo_id' => auth()->id(),
            'catatan' => $ktp->catatan ?? null,
            'no_hp' => $ktp->no_hp,
            'nama_pemohon' => $ktp->nama,
        ]);

        // Link KTP EL to service request
        $ktp->service_request_id = $serviceRequest->id;
        $ktp->saveQuietly();
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
