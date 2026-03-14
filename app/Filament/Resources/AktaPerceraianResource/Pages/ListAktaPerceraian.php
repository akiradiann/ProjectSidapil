<?php

namespace App\Filament\Resources\AktaPerceraianResource\Pages;

use App\Filament\Resources\AktaPerceraianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAktaPerceraian extends ListRecords
{
    protected static string $resource = AktaPerceraianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Akta Perceraian')
                ->visible(fn () => AktaPerceraianResource::canCreate()),
        ];
    }
}


