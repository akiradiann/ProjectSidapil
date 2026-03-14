<?php

namespace App\Filament\Resources\KtpElResource\Pages;

use App\Filament\Resources\KtpElResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKtpEls extends ListRecords
{
    protected static string $resource = KtpElResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah KTP EL')
                ->visible(fn () => KtpElResource::canCreate()),
        ];
    }
}
