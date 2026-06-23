<?php

namespace App\Filament\Resources\AktaPerkawinanResource\Pages;

use App\Filament\Resources\AktaPerkawinanResource;
use App\Models\KategoriLayanan;
use App\Models\ServiceRequest;
use App\Models\StatusAjuan;
use Filament\Resources\Pages\CreateRecord;

class CreateAktaPerkawinan extends CreateRecord
{
    protected static string $resource = AktaPerkawinanResource::class;



    protected function afterCreate(): void
    {
        $akta = $this->record;

        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'AKTA PERKAWINAN')->first()?->id ?? 3;

        $serviceRequest = ServiceRequest::create([
            'nomor_layanan' => null,
            'kategori_layanan_id' => $kategoriLayananId,
            'jenis_layanan_id' => $akta->layanan_id,
            'jenis_produk_id' => $akta->produk_id ?? null,
            'status_pelapor_id' => $akta->status_pelapor_id ?? null,
            'status_ajuan_id' => $akta->status_ajuan_id,
            'fo_id' => auth()->id(),
            'file_produk' => $akta->file_produk ?? null,
            'catatan' => $akta->catatan ?? null,
            'no_hp' => $akta->no_hp,
            'nama_pemohon' => $akta->nama_pelapor,
        ]);

        $akta->service_request_id = $serviceRequest->id;
        $akta->saveQuietly();
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


