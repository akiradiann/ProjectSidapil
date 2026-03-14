<?php

namespace App\Filament\Resources\KutipanDuaAktaPerkawinanResource\Pages;

use App\Filament\Resources\KutipanDuaAktaPerkawinanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKutipanDuaAktaPerkawinans extends ListRecords
{
    protected static string $resource = KutipanDuaAktaPerkawinanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kutipan Dua Akta Perkawinan')
                ->visible(fn () => KutipanDuaAktaPerkawinanResource::canCreate()),
        ];
    }
}





