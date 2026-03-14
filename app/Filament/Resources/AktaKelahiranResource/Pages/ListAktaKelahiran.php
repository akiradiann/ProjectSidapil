<?php

namespace App\Filament\Resources\AktaKelahiranResource\Pages;

use App\Filament\Resources\AktaKelahiranResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAktaKelahiran extends ListRecords
{
    protected static string $resource = AktaKelahiranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Akta Kelahiran')
                ->visible(fn () => AktaKelahiranResource::canCreate()),
        ];
    }
}

