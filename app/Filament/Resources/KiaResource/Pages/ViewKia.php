<?php

namespace App\Filament\Resources\KiaResource\Pages;

use App\Filament\Resources\KiaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKia extends ViewRecord
{
    protected static string $resource = KiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => KiaResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn () => KiaResource::canDelete($this->record)),
        ];
    }
}
