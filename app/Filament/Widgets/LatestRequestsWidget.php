<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use App\Models\StatusAjuan;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestRequestsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, [
            User::ROLE_OPERATOR,
            User::ROLE_CUSTOMER_SERVICE,
            User::ROLE_LOKET,
        ]);
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $query = ServiceRequest::query()->latest('created_at');

        if ($user) {
            if ($user->isOperator()) {
                $query->where('status_ajuan_id', StatusAjuan::DIPROSES);
            } elseif ($user->isCustomerService()) {
                $query->whereIn('status_ajuan_id', [StatusAjuan::DITOLAK, StatusAjuan::SIAP_KIRIM]);
            } elseif ($user->isLoket()) {
                $query->where('status_ajuan_id', StatusAjuan::SIAP_DIAMBIL);
            }
        }

        return $table
            ->query($query)
            ->heading(function() use ($user) {
                if ($user) {
                    if ($user->isOperator()) {
                        return 'Daftar Ajuan Perlu Diproses';
                    } elseif ($user->isCustomerService()) {
                        return 'Daftar Ajuan Perlu Tindakan (Ditolak / Siap Kirim)';
                    } elseif ($user->isLoket()) {
                        return 'Daftar Ajuan Siap Diambil';
                    }
                }
                return 'Daftar Ajuan Terbaru';
            })
            ->description('Menampilkan 5-10 daftar ajuan layanan terbaru yang memerlukan perhatian Anda.')
            ->columns([
                Tables\Columns\TextColumn::make('nomor_layanan')
                    ->label('Nomor Layanan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('kategoriLayanan.nama_kategori')
                    ->label('Kategori Layanan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_pemohon')
                    ->label('Nama Pemohon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('statusAjuan.nama_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => match ($record->status_ajuan_id) {
                        StatusAjuan::DIPROSES => 'info',
                        StatusAjuan::DITOLAK => 'danger',
                        StatusAjuan::SIAP_KIRIM => 'warning',
                        StatusAjuan::SIAP_DIAMBIL => 'success',
                        StatusAjuan::SELESAI => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Masuk')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->actions([
                Tables\Actions\Action::make('proses')
                    ->label('Proses')
                    ->icon('heroicon-o-pencil-square')
                    ->url(function (ServiceRequest $record) {
                        $relations = [
                            'aktaKelahiran' => 'akta-kelahirans',
                            'kutipanDuaAktaKelahiran' => 'kutipan-dua-akta-kelahirans',
                            'aktaKematian' => 'akta-kematians',
                            'kartuKeluarga' => 'kartu-keluargas',
                            'aktaPerkawinan' => 'akta-perkawinans',
                            'aktaPerceraian' => 'akta-perceraians',
                            'pindahDatang' => 'pindah-datangs',
                            'ktpEl' => 'ktp-els',
                            'kia' => 'kias',
                            'kutipanDuaAktaKematian' => 'kutipan-dua-akta-kematians',
                            'kutipanDuaAktaPerkawinan' => 'kutipan-dua-akta-perkawinans',
                            'kutipanDuaAktaPerceraian' => 'kutipan-dua-akta-perceraians',
                            'catatanPinggir' => 'catatan-pinggirs',
                            'surat' => 'surats',
                        ];

                        foreach ($relations as $relation => $slug) {
                            if ($record->$relation()->exists()) {
                                $relatedRecord = $record->$relation;
                                if ($relatedRecord) {
                                    return "/{$slug}/{$relatedRecord->id}/edit";
                                }
                            }
                        }

                        return "/service-requests/{$record->id}/edit";
                    })
                    ->visible(fn () => auth()->user() && !auth()->user()->isCustomerService()),

                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ServiceRequest $record) => \App\Filament\Resources\PengirimanResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn () => auth()->user() && auth()->user()->isCustomerService()),
            ]);
    }
}
