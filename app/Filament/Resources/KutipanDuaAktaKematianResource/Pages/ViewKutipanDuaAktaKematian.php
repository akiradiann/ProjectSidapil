<?php

namespace App\Filament\Resources\KutipanDuaAktaKematianResource\Pages;

use App\Filament\Resources\KutipanDuaAktaKematianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKutipanDuaAktaKematian extends ViewRecord
{
    protected static string $resource = KutipanDuaAktaKematianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => KutipanDuaAktaKematianResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn () => KutipanDuaAktaKematianResource::canDelete($this->record)),
        ];
    }
}





