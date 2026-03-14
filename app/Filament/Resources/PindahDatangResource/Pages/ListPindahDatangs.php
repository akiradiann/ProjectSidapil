<?php

namespace App\Filament\Resources\PindahDatangResource\Pages;

use App\Filament\Resources\PindahDatangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPindahDatangs extends ListRecords
{
    protected static string $resource = PindahDatangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pindah Datang')
                ->visible(fn () => PindahDatangResource::canCreate()),
        ];
    }
}
