<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\StatusAjuan;
use App\Models\User;

class HandledRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceRequestsAsOperator'; // Default fallback relation

    protected static ?string $title = 'Riwayat Ajuan yang Ditangani';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        // Admin, FO, Operator, CS, and Loket roles can have handled requests
        return in_array($ownerRecord->role, [
            User::ROLE_FRONT_OFFICE,
            User::ROLE_OPERATOR,
            User::ROLE_CUSTOMER_SERVICE,
            User::ROLE_LOKET,
        ]);
    }

    public function table(Table $table): Table
    {
        // Dynamically bind the relationship query based on the user's role
        $role = $this->getOwnerRecord()->role;
        $relationshipName = match ($role) {
            User::ROLE_FRONT_OFFICE => 'serviceRequestsAsFo',
            User::ROLE_OPERATOR => 'serviceRequestsAsOperator',
            User::ROLE_CUSTOMER_SERVICE => 'serviceRequestsAsCs',
            User::ROLE_LOKET => 'serviceRequestsAsLoket',
            default => 'serviceRequestsAsOperator',
        };

        // Override relationship dynamically
        static::$relationship = $relationshipName;

        return $table
            ->recordTitleAttribute('nomor_layanan')
            ->columns([
                Tables\Columns\TextColumn::make('nomor_layanan')
                    ->label('Nomor Layanan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('kategoriLayanan.nama_kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('nama_pemohon')
                    ->label('Pemohon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('statusAjuan.nama_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => match ($record->status_ajuan_id) {
                        1 => 'info',
                        2 => 'danger',
                        3 => 'success',
                        4 => 'success',
                        5 => 'gray',
                        6 => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('selesai_at')
                    ->label('Waktu Selesai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_penyelesaian_formatted')
                    ->label('Durasi Penyelesaian')
                    ->badge()
                    ->color(fn($state) => $state === '-' ? 'gray' : 'success'),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.service-requests.view', ['record' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }
}
