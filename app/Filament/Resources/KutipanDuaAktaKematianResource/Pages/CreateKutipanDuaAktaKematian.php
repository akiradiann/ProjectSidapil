<?php

namespace App\Filament\Resources\KutipanDuaAktaKematianResource\Pages;

use App\Filament\Resources\KutipanDuaAktaKematianResource;
use App\Models\StatusAjuan;
use App\Models\ServiceRequest;
use App\Models\KategoriLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreateKutipanDuaAktaKematian extends CreateRecord
{
    protected static string $resource = KutipanDuaAktaKematianResource::class;



    protected function afterCreate(): void
    {
        // Create corresponding ServiceRequest
        $kutipan = $this->record;

        // Get kategori layanan for Kutipan Dua Akta Kematian
        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'KUTIPAN DUA KEMATIAN')->first()?->id ?? 6;

        $serviceRequest = ServiceRequest::create([
            'nomor_layanan' => null, // Will be auto-generated
            'kategori_layanan_id' => $kategoriLayananId,
            'jenis_layanan_id' => $kutipan->layanan_id,
            'jenis_produk_id' => $kutipan->produk_id ?? null,
            'status_pelapor_id' => $kutipan->status_pelapor_id ?? null,
            'status_ajuan_id' => $kutipan->status_ajuan_id,
            'fo_id' => auth()->id(),
            'file_produk' => $kutipan->file_produk ?? null,
            'catatan' => $kutipan->catatan ?? null,
        ]);

        // Link kutipan to service request
        $kutipan->service_request_id = $serviceRequest->id;
        $kutipan->saveQuietly();
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





