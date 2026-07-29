<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\StatusAjuan;
use App\Models\User;
use App\Filament\Resources\AktaKelahiranResource;
use App\Filament\Resources\AktaKematianResource;
use App\Filament\Resources\AktaPerkawinanResource;
use App\Filament\Resources\AktaPerceraianResource;
use App\Filament\Resources\KartuKeluargaResource;
use App\Filament\Resources\KtpElResource;
use App\Filament\Resources\KiaResource;
use App\Filament\Resources\PindahDatangResource;
use App\Filament\Resources\KutipanDuaAktaKelahiranResource;
use App\Filament\Resources\KutipanDuaAktaKematianResource;
use App\Filament\Resources\KutipanDuaAktaPerkawinanResource;
use App\Filament\Resources\KutipanDuaAktaPerceraianResource;
use App\Filament\Resources\CatatanPinggirResource;
use App\Filament\Resources\SuratResource;

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
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(function ($record) {
                        $kategori = strtoupper($record->kategoriLayanan?->nama_kategori ?? '');

                        if (str_contains($kategori, 'KELAHIRAN') && !str_contains($kategori, 'KUTIPAN')) {
                            if ($record->aktaKelahiran) {
                                return AktaKelahiranResource::getUrl('edit', ['record' => $record->aktaKelahiran->id]);
                            }
                        } elseif (str_contains($kategori, 'KEMATIAN') && !str_contains($kategori, 'KUTIPAN')) {
                            if ($record->aktaKematian) {
                                return AktaKematianResource::getUrl('edit', ['record' => $record->aktaKematian->id]);
                            }
                        } elseif (str_contains($kategori, 'PERKAWINAN') && !str_contains($kategori, 'KUTIPAN')) {
                            if ($record->aktaPerkawinan) {
                                return AktaPerkawinanResource::getUrl('edit', ['record' => $record->aktaPerkawinan->id]);
                            }
                        } elseif (str_contains($kategori, 'PERCERAIAN') && !str_contains($kategori, 'KUTIPAN')) {
                            if ($record->aktaPerceraian) {
                                return AktaPerceraianResource::getUrl('edit', ['record' => $record->aktaPerceraian->id]);
                            }
                        } elseif (str_contains($kategori, 'KARTU KELUARGA')) {
                            if ($record->kartuKeluarga) {
                                return KartuKeluargaResource::getUrl('edit', ['record' => $record->kartuKeluarga->id]);
                            }
                        } elseif (str_contains($kategori, 'KTP')) {
                            if ($record->ktpEl) {
                                return KtpElResource::getUrl('edit', ['record' => $record->ktpEl->id]);
                            }
                        } elseif (str_contains($kategori, 'KIA')) {
                            if ($record->kia) {
                                return KiaResource::getUrl('edit', ['record' => $record->kia->id]);
                            }
                        } elseif (str_contains($kategori, 'PINDAH DATANG')) {
                            if ($record->pindahDatang) {
                                return PindahDatangResource::getUrl('edit', ['record' => $record->pindahDatang->id]);
                            }
                        } elseif (str_contains($kategori, 'KUTIPAN DUA KELAHIRAN')) {
                            if ($record->kutipanDuaAktaKelahiran) {
                                return KutipanDuaAktaKelahiranResource::getUrl('edit', ['record' => $record->kutipanDuaAktaKelahiran->id]);
                            }
                        } elseif (str_contains($kategori, 'KUTIPAN DUA KEMATIAN')) {
                            if ($record->kutipanDuaAktaKematian) {
                                return KutipanDuaAktaKematianResource::getUrl('edit', ['record' => $record->kutipanDuaAktaKematian->id]);
                            }
                        } elseif (str_contains($kategori, 'KUTIPAN DUA PERKAWINAN')) {
                            if ($record->kutipanDuaAktaPerkawinan) {
                                return KutipanDuaAktaPerkawinanResource::getUrl('edit', ['record' => $record->kutipanDuaAktaPerkawinan->id]);
                            }
                        } elseif (str_contains($kategori, 'KUTIPAN DUA PERCERAIAN')) {
                            if ($record->kutipanDuaAktaPerceraian) {
                                return KutipanDuaAktaPerceraianResource::getUrl('edit', ['record' => $record->kutipanDuaAktaPerceraian->id]);
                            }
                        } elseif (str_contains($kategori, 'CATATAN PINGGIR')) {
                            if ($record->catatanPinggir) {
                                return CatatanPinggirResource::getUrl('edit', ['record' => $record->catatanPinggir->id]);
                            }
                        } elseif (str_contains($kategori, 'SURAT')) {
                            if ($record->surat) {
                                return SuratResource::getUrl('edit', ['record' => $record->surat->id]);
                            }
                        }

                        return '#';
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }
}
