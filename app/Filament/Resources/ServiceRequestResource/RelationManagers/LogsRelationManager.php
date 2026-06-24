<?php

namespace App\Filament\Resources\ServiceRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Riwayat Perubahan Status';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only, no form needed
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('created_at')
            ->columns([
                Tables\Columns\TextColumn::make('statusAjuan.nama_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => match($record->status_ajuan_id) {
                        1 => 'info', // DIPROSES
                        2 => 'danger', // DITOLAK
                        3 => 'warning', // SIAP KIRIM
                        4 => 'success', // SIAP DIAMBIL
                        5 => 'gray', // SELESAI
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => \App\Models\User::getRoles()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        \App\Models\User::ROLE_ADMIN => 'danger',
                        \App\Models\User::ROLE_FRONT_OFFICE => 'info',
                        \App\Models\User::ROLE_OPERATOR => 'success',
                        \App\Models\User::ROLE_CUSTOMER_SERVICE => 'warning',
                        \App\Models\User::ROLE_LOKET => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->catatan)
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No actions
            ])
            ->actions([
                // No actions
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->defaultSort('created_at', 'desc');
    }
}

