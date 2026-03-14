<?php

namespace App\Filament\Resources\KutipanDuaAktaKelahiranResource\Pages;

use App\Filament\Resources\KutipanDuaAktaKelahiranResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKutipanDuaAktaKelahirans extends ListRecords
{
    protected static string $resource = KutipanDuaAktaKelahiranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kutipan Dua Akta Kelahiran')
                ->visible(fn () => KutipanDuaAktaKelahiranResource::canCreate()),
        ];
    }
}
