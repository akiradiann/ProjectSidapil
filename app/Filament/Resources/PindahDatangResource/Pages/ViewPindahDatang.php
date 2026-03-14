<?php

namespace App\Filament\Resources\PindahDatangResource\Pages;

use App\Filament\Resources\PindahDatangResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPindahDatang extends ViewRecord
{
    protected static string $resource = PindahDatangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => PindahDatangResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn () => PindahDatangResource::canDelete($this->record)),
        ];
    }
}
