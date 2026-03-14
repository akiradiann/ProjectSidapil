<?php

namespace App\Filament\Resources\KutipanDuaAktaPerkawinanResource\Pages;

use App\Filament\Resources\KutipanDuaAktaPerkawinanResource;
use App\Models\StatusAjuan;
use App\Models\ServiceRequest;
use App\Models\KategoriLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreateKutipanDuaAktaPerkawinan extends CreateRecord
{
    protected static string $resource = KutipanDuaAktaPerkawinanResource::class;



    protected function afterCreate(): void
    {
        // Create corresponding ServiceRequest
        $kutipan = $this->record;

        // Get kategori layanan for Kutipan Dua Akta Perkawinan
        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'KUTIPAN DUA PERKAWINAN')->first()?->id ?? 7;

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





