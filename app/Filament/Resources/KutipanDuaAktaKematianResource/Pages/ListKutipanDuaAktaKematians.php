<?php

namespace App\Filament\Resources\KutipanDuaAktaKematianResource\Pages;

use App\Filament\Resources\KutipanDuaAktaKematianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKutipanDuaAktaKematians extends ListRecords
{
    protected static string $resource = KutipanDuaAktaKematianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kutipan Dua Akta Kematian')
                ->visible(fn () => KutipanDuaAktaKematianResource::canCreate()),
        ];
    }
}





