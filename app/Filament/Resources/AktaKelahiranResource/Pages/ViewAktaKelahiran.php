<?php

namespace App\Filament\Resources\AktaKelahiranResource\Pages;

use App\Filament\Resources\AktaKelahiranResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAktaKelahiran extends ViewRecord
{
    protected static string $resource = AktaKelahiranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => AktaKelahiranResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn () => AktaKelahiranResource::canDelete($this->record)),
        ];
    }
}

