<?php

namespace App\Filament\Resources\CatatanPinggirResource\Pages;

use App\Filament\Resources\CatatanPinggirResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCatatanPinggir extends ViewRecord
{
    protected static string $resource = CatatanPinggirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => CatatanPinggirResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn () => CatatanPinggirResource::canDelete($this->record)),
        ];
    }
}





