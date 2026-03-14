<?php

namespace App\Filament\Resources\PengirimanResource\Pages;

use App\Filament\Resources\PengirimanResource;
use App\Models\StatusAjuan;
use Filament\Resources\Pages\EditRecord;

class EditPengiriman extends EditRecord
{
    protected static string $resource = PengirimanResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set CS ID when marking as selesai
        if (isset($data['status_ajuan_id']) && $data['status_ajuan_id'] == StatusAjuan::SELESAI) {
            $data['cs_id'] = auth()->id();
            $data['selesai_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Log status change if status was updated to SELESAI
        if ($this->record->wasChanged('status_ajuan_id') && 
            $this->record->status_ajuan_id == StatusAjuan::SELESAI) {
            // Sync with akta kelahiran if exists
            if ($this->record->aktaKelahiran) {
                $this->record->aktaKelahiran->update([
                    'status_ajuan_id' => StatusAjuan::SELESAI,
                ]);
            }

            // Sync with akta kematian if exists
            if ($this->record->aktaKematian) {
                $this->record->aktaKematian->update([
                    'status_ajuan_id' => StatusAjuan::SELESAI,
                ]);
            }

            // Sync with akta perkawinan if exists
            if ($this->record->aktaPerkawinan) {
                $this->record->aktaPerkawinan->update([
                    'status_ajuan_id' => StatusAjuan::SELESAI,
                ]);
            }

            // Sync with akta perceraian if exists
            if ($this->record->aktaPerceraian) {
                $this->record->aktaPerceraian->update([
                    'status_ajuan_id' => StatusAjuan::SELESAI,
                ]);
            }

            // Sync with kutipan dua akta kelahiran if exists
            if ($this->record->kutipanDuaAktaKelahiran) {
                $this->record->kutipanDuaAktaKelahiran->update([
                    'status_ajuan_id' => StatusAjuan::SELESAI,
                ]);
            }

            // Sync with kutipan dua akta kematian if exists
            if ($this->record->kutipanDuaAktaKematian) {
                $this->record->kutipanDuaAktaKematian->update([
                    'status_ajuan_id' => StatusAjuan::SELESAI,
                ]);
            }

            // Sync with kutipan dua akta perkawinan if exists
            if ($this->record->kutipanDuaAktaPerkawinan) {
                $this->record->kutipanDuaAktaPerkawinan->update([
                    'status_ajuan_id' => StatusAjuan::SELESAI,
                ]);
            }

            // Sync with kutipan dua akta perceraian if exists
            if ($this->record->kutipanDuaAktaPerceraian) {
                $this->record->kutipanDuaAktaPerceraian->update([
                    'status_ajuan_id' => StatusAjuan::SELESAI,
                ]);
            }

            // Sync with catatan pinggir if exists
            if ($this->record->catatanPinggir) {
                $this->record->catatanPinggir->update([
                    'status_ajuan_id' => StatusAjuan::SELESAI,
                ]);
            }

            \App\Models\ServiceRequestLog::create([
                'service_request_id' => $this->record->id,
                'status_ajuan_id' => StatusAjuan::SELESAI,
                'user_id' => auth()->id(),
                'catatan' => $this->record->catatan ?? 'Selesai dikirim oleh CS',
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

