<?php

namespace App\Filament\Resources\KutipanDuaAktaPerkawinanResource\Pages;

use App\Filament\Resources\KutipanDuaAktaPerkawinanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKutipanDuaAktaPerkawinan extends ViewRecord
{
    protected static string $resource = KutipanDuaAktaPerkawinanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => KutipanDuaAktaPerkawinanResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn () => KutipanDuaAktaPerkawinanResource::canDelete($this->record)),
        ];
    }
}





