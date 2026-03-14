<?php

namespace App\Filament\Resources\AktaKelahiranResource\Pages;

use App\Filament\Resources\AktaKelahiranResource;
use App\Models\StatusAjuan;
use Filament\Resources\Pages\EditRecord;

class EditAktaKelahiran extends EditRecord
{
    protected static string $resource = AktaKelahiranResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $oldStatus = $this->record->status_ajuan_id;
        $newStatus = $data['status_ajuan_id'] ?? $oldStatus;

        // If operator is updating status
        if (auth()->check() && auth()->user()->isOperator()) {
            // Will be synced in afterSave
        }

        // If loket is updating status to SELESAI
        if (auth()->check() && auth()->user()->isLoket()) {
            if ($newStatus == StatusAjuan::SELESAI) {
                // Will be synced in afterSave
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Sync service request data with akta kelahiran
        if ($this->record->serviceRequest) {
            $updateData = [
                'jenis_produk_id' => $this->record->produk_id,
                'status_ajuan_id' => $this->record->status_ajuan_id,
                'file_produk' => $this->record->file_produk,
                'catatan' => $this->record->catatan,
            ];

            // If operator is updating, set operator_id
            if (auth()->check() && auth()->user()->isOperator()) {
                $updateData['operator_id'] = auth()->id();
            }

            // If loket is updating to SELESAI, set loket_id and selesai_at
            if (auth()->check() && auth()->user()->isLoket() && 
                $this->record->status_ajuan_id == StatusAjuan::SELESAI) {
                $updateData['loket_id'] = auth()->id();
                $updateData['selesai_at'] = now();
            }

            $this->record->serviceRequest->update($updateData);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

