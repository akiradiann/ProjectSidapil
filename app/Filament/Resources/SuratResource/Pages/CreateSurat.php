<?php

namespace App\Filament\Resources\SuratResource\Pages;

use App\Filament\Resources\SuratResource;
use App\Models\StatusAjuan;
use App\Models\ServiceRequest;
use App\Models\KategoriLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreateSurat extends CreateRecord
{
    protected static string $resource = SuratResource::class;



    protected function afterCreate(): void
    {
        // Create corresponding ServiceRequest
        $surat = $this->record;

        // Get kategori layanan for SURAT
        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'SURAT')->first()?->id ?? 14;

        $serviceRequest = ServiceRequest::create([
            'nomor_layanan' => null, // Will be auto-generated
            'kategori_layanan_id' => $kategoriLayananId,
            'jenis_layanan_id' => $surat->layanan_id,
            'jenis_produk_id' => $surat->produk_id ?? null,
            'status_pelapor_id' => $surat->status_pelapor_id ?? null,
            'status_ajuan_id' => $surat->status_ajuan_id,
            'fo_id' => auth()->id(),
            'file_produk' => $surat->file_produk ?? null,
            'catatan' => $surat->catatan ?? null,
        ]);

        // Link surat to service request
        $surat->service_request_id = $serviceRequest->id;
        $surat->saveQuietly();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan'),
            $this->getCancelFormAction(),
        ];
    }
}
