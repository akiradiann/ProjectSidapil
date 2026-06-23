<?php

namespace App\Filament\Resources\KutipanDuaAktaPerceraianResource\Pages;

use App\Filament\Resources\KutipanDuaAktaPerceraianResource;
use App\Models\StatusAjuan;
use App\Models\ServiceRequest;
use App\Models\KategoriLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreateKutipanDuaAktaPerceraian extends CreateRecord
{
    protected static string $resource = KutipanDuaAktaPerceraianResource::class;



    protected function afterCreate(): void
    {
        // Create corresponding ServiceRequest
        $kutipan = $this->record;

        // Get kategori layanan for Kutipan Dua Akta Perceraian
        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'KUTIPAN DUA PERCERAIAN')->first()?->id ?? 8;

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
            'no_hp' => $kutipan->no_hp,
            'nama_pemohon' => $kutipan->nama_pelapor,
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





