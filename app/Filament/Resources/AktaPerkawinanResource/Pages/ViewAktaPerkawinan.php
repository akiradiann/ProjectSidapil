<?php

namespace App\Filament\Resources\AktaPerkawinanResource\Pages;

use App\Filament\Resources\AktaPerkawinanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAktaPerkawinan extends ViewRecord
{
    protected static string $resource = AktaPerkawinanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}


