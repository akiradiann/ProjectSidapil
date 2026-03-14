<?php

namespace App\Filament\Resources\CatatanPinggirResource\Pages;

use App\Filament\Resources\CatatanPinggirResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCatatanPinggirs extends ListRecords
{
    protected static string $resource = CatatanPinggirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Catatan Pinggir')
                ->visible(fn () => CatatanPinggirResource::canCreate()),
        ];
    }
}





