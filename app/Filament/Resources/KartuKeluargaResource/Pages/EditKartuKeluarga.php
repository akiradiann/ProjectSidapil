<?php

namespace App\Filament\Resources\KartuKeluargaResource\Pages;

use App\Filament\Resources\KartuKeluargaResource;
use App\Models\StatusAjuan;
use Filament\Resources\Pages\EditRecord;

class EditKartuKeluarga extends EditRecord
{
    protected static string $resource = KartuKeluargaResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $oldStatus = $this->record->status_ajuan_id;
        $newStatus = $data['status_ajuan_id'] ?? $oldStatus;

        // If status changed to SELESAI, set selesai_at
        if ($oldStatus != $newStatus && $newStatus == StatusAjuan::SELESAI) {
            $data['selesai_at'] = now();
        }

        // If status changed from SELESAI to something else, clear selesai_at
        if ($oldStatus == StatusAjuan::SELESAI && $newStatus != StatusAjuan::SELESAI) {
            $data['selesai_at'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Sync service request data with kartu keluarga
        if ($this->record->serviceRequest) {
            $updateData = [
                'jenis_produk_id' => $this->record->produk_id,
                'status_ajuan_id' => $this->record->status_ajuan_id,
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
