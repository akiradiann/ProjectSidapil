<?php

namespace App\Filament\Resources\KartuKeluargaResource\Pages;

use App\Filament\Resources\KartuKeluargaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKartuKeluarga extends ViewRecord
{
    protected static string $resource = KartuKeluargaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => KartuKeluargaResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn () => KartuKeluargaResource::canDelete($this->record)),
        ];
    }
}
