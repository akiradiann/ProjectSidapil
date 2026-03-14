<?php

namespace App\Filament\Resources\KutipanDuaAktaPerceraianResource\Pages;

use App\Filament\Resources\KutipanDuaAktaPerceraianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKutipanDuaAktaPerceraians extends ListRecords
{
    protected static string $resource = KutipanDuaAktaPerceraianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kutipan Dua Akta Perceraian')
                ->visible(fn () => KutipanDuaAktaPerceraianResource::canCreate()),
        ];
    }
}





