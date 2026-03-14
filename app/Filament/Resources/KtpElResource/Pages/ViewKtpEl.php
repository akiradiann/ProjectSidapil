<?php

namespace App\Filament\Resources\KtpElResource\Pages;

use App\Filament\Resources\KtpElResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKtpEl extends ViewRecord
{
    protected static string $resource = KtpElResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => KtpElResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn () => KtpElResource::canDelete($this->record)),
        ];
    }
}
