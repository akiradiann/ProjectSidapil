<?php

namespace App\Filament\Resources\KutipanDuaAktaKelahiranResource\Pages;

use App\Filament\Resources\KutipanDuaAktaKelahiranResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKutipanDuaAktaKelahiran extends ViewRecord
{
    protected static string $resource = KutipanDuaAktaKelahiranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => KutipanDuaAktaKelahiranResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn () => KutipanDuaAktaKelahiranResource::canDelete($this->record)),
        ];
    }
}
