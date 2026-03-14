<?php

namespace App\Filament\Resources\KiaResource\Pages;

use App\Filament\Resources\KiaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKias extends ListRecords
{
    protected static string $resource = KiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah KIA')
                ->visible(fn () => KiaResource::canCreate()),
        ];
    }
}
