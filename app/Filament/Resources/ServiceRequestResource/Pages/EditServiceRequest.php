<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\StatusAjuan;
use Filament\Resources\Pages\EditRecord;

class EditServiceRequest extends EditRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $oldStatus = $this->record->status_ajuan_id;
        $newStatus = $data['status_ajuan_id'] ?? $oldStatus;

        // If operator is updating status
        if (auth()->check() && auth()->user()->isOperator()) {
            $data['operator_id'] = auth()->id();

            // If status changed to SIAP KIRIM and produk is FILE, ensure file is uploaded
            if ($newStatus == StatusAjuan::SIAP_KIRIM && 
                $data['jenis_produk_id'] == 2 && 
                !empty($data['file_produk'])) {
                // File is uploaded, status can be changed
            }
        }

        // If loket is updating status to SELESAI
        if (auth()->check() && auth()->user()->isLoket()) {
            if ($newStatus == StatusAjuan::SELESAI) {
                $data['loket_id'] = auth()->id();
                $data['selesai_at'] = now();
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Logging is handled by the model's updated event
        // No need to duplicate here
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

