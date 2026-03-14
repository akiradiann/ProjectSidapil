<?php

namespace App\Filament\Resources\AktaKematianResource\Pages;

use App\Filament\Resources\AktaKematianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAktaKematian extends ListRecords
{
    protected static string $resource = AktaKematianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Akta Kematian')
                ->visible(fn () => AktaKematianResource::canCreate()),
        ];
    }
}


