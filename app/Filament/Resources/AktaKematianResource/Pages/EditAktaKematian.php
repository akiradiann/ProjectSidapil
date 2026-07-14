<?php

namespace App\Filament\Resources\AktaKematianResource\Pages;

use App\Filament\Resources\AktaKematianResource;
use App\Models\StatusAjuan;
use Filament\Resources\Pages\EditRecord;

class EditAktaKematian extends EditRecord
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['checklist_persyaratan'] = $this->record->serviceRequest?->checklist_persyaratan ?? [];
        return $data;
    }

    protected static string $resource = AktaKematianResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $oldStatus = $this->record->status_ajuan_id;
        $newStatus = $data['status_ajuan_id'] ?? $oldStatus;

        // Placeholder for any custom logic similar to Akta Kelahiran
        if (auth()->check() && auth()->user()->isOperator()) {
            // Will be synced in afterSave
        }

        if (auth()->check() && auth()->user()->isLoket()) {
            if ($newStatus == StatusAjuan::SELESAI) {
                // Will be synced in afterSave
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $serviceRequest = $this->record->serviceRequest ?? $this->record->ensureServiceRequestExists();

        if ($serviceRequest) {
            $updateData = [
                'jenis_produk_id' => $this->record->produk_id,
                'status_ajuan_id' => $this->record->status_ajuan_id,
                'file_produk' => $this->record->file_produk,
                'catatan' => $this->record->catatan,
                'checklist_persyaratan' => $this->data['checklist_persyaratan'] ?? null,
            ];

            if (auth()->check() && auth()->user()->isOperator()) {
                $updateData['operator_id'] = auth()->id();
            }

            if (auth()->check() && auth()->user()->isLoket() &&
                $this->record->status_ajuan_id == StatusAjuan::SELESAI) {
                $updateData['loket_id'] = auth()->id();
                $updateData['selesai_at'] = now();
            }

            $serviceRequest->update($updateData);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}


