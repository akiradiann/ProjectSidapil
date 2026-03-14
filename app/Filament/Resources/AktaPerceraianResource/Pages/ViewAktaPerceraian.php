<?php

namespace App\Filament\Resources\AktaPerceraianResource\Pages;

use App\Filament\Resources\AktaPerceraianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAktaPerceraian extends ViewRecord
{
    protected static string $resource = AktaPerceraianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}


