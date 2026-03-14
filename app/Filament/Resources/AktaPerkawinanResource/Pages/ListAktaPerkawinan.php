<?php

namespace App\Filament\Resources\AktaPerkawinanResource\Pages;

use App\Filament\Resources\AktaPerkawinanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAktaPerkawinan extends ListRecords
{
    protected static string $resource = AktaPerkawinanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Akta Perkawinan')
                ->visible(fn () => AktaPerkawinanResource::canCreate()),
        ];
    }
}


