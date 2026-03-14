<?php

namespace App\Filament\Resources\KutipanDuaAktaPerceraianResource\Pages;

use App\Filament\Resources\KutipanDuaAktaPerceraianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKutipanDuaAktaPerceraian extends ViewRecord
{
    protected static string $resource = KutipanDuaAktaPerceraianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => KutipanDuaAktaPerceraianResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn () => KutipanDuaAktaPerceraianResource::canDelete($this->record)),
        ];
    }
}





