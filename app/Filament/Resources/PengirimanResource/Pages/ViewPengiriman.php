<?php

namespace App\Filament\Resources\PengirimanResource\Pages;

use App\Filament\Resources\PengirimanResource;
use App\Models\StatusAjuan;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;

class ViewPengiriman extends ViewRecord
{
    protected static string $resource = PengirimanResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Eager load all relationships
        $this->record->load([
            'kategoriLayanan',
            'jenisLayanan',
            'jenisProduk',
            'statusPelapor',
            'statusAjuan',
            'fo',
            'operator',
            'cs',
            'loket',
            'aktaKelahiran.jenisLayanan',
            'aktaKelahiran.statusPelapor',
            'aktaKelahiran.jenisProduk',
            'aktaKelahiran.statusAjuan',
            'aktaKematian.jenisLayanan',
            'aktaKematian.statusPelapor',
            'aktaKematian.jenisProduk',
            'aktaKematian.statusAjuan',
            'aktaPerkawinan.jenisLayanan',
            'aktaPerkawinan.statusPelapor',
            'aktaPerkawinan.jenisProduk',
            'aktaPerkawinan.statusAjuan',
            'aktaPerceraian.jenisLayanan',
            'aktaPerceraian.statusPelapor',
            'aktaPerceraian.jenisProduk',
            'aktaPerceraian.statusAjuan',
            'kutipanDuaAktaKelahiran.jenisLayanan',
            'kutipanDuaAktaKelahiran.statusPelapor',
            'kutipanDuaAktaKelahiran.jenisProduk',
            'kutipanDuaAktaKelahiran.statusAjuan',
            'kutipanDuaAktaKematian.jenisLayanan',
            'kutipanDuaAktaKematian.statusPelapor',
            'kutipanDuaAktaKematian.jenisProduk',
            'kutipanDuaAktaKematian.statusAjuan',
            'kutipanDuaAktaPerkawinan.jenisLayanan',
            'kutipanDuaAktaPerkawinan.statusPelapor',
            'kutipanDuaAktaPerkawinan.jenisProduk',
            'kutipanDuaAktaPerkawinan.statusAjuan',
            'kutipanDuaAktaPerceraian.jenisLayanan',
            'kutipanDuaAktaPerceraian.statusPelapor',
            'kutipanDuaAktaPerceraian.jenisProduk',
            'kutipanDuaAktaPerceraian.statusAjuan',
            'catatanPinggir.jenisLayanan',
            'catatanPinggir.statusPelapor',
            'catatanPinggir.jenisProduk',
            'catatanPinggir.statusAjuan',
        ]);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Kelola')
                ->visible(fn () => 
                    $this->record->status_ajuan_id == StatusAjuan::SIAP_KIRIM || 
                    $this->record->status_ajuan_id == StatusAjuan::DITOLAK
                ),
            Actions\Action::make('mark_selesai')
                ->label(fn () => $this->record->status_ajuan_id == StatusAjuan::SELESAI 
                    ? 'Selesai Kirim' 
                    : 'Tandai Selesai'
                )
                ->icon('heroicon-o-check-circle')
                ->color(fn () => $this->record->status_ajuan_id == StatusAjuan::SELESAI 
                    ? 'primary' 
                    : 'success'
                )
                ->disabled(fn () => $this->record->status_ajuan_id == StatusAjuan::SELESAI)
                ->requiresConfirmation(fn () => $this->record->status_ajuan_id != StatusAjuan::SELESAI)
                ->modalHeading('Tandai Sebagai Selesai')
                ->modalDescription('Apakah Anda yakin sudah mengirim file/alasan ke warga?')
                ->action(function () {
                    if ($this->record->status_ajuan_id != StatusAjuan::SELESAI) {
                        $this->record->update([
                            'status_ajuan_id' => StatusAjuan::SELESAI,
                            'cs_id' => auth()->id(),
                            'selesai_at' => now(),
                        ]);

                        // Sync with akta kelahiran if exists
                        if ($this->record->aktaKelahiran) {
                            $this->record->aktaKelahiran->update([
                                'status_ajuan_id' => StatusAjuan::SELESAI,
                            ]);
                        }

                        // Sync with akta kematian if exists
                        if ($this->record->aktaKematian) {
                            $this->record->aktaKematian->update([
                                'status_ajuan_id' => StatusAjuan::SELESAI,
                            ]);
                        }

                        // Sync with akta perkawinan if exists
                        if ($this->record->aktaPerkawinan) {
                            $this->record->aktaPerkawinan->update([
                                'status_ajuan_id' => StatusAjuan::SELESAI,
                            ]);
                        }

                        // Sync with akta perceraian if exists
                        if ($this->record->aktaPerceraian) {
                            $this->record->aktaPerceraian->update([
                                'status_ajuan_id' => StatusAjuan::SELESAI,
                            ]);
                        }

                        // Sync with kutipan dua akta kelahiran if exists
                        if ($this->record->kutipanDuaAktaKelahiran) {
                            $this->record->kutipanDuaAktaKelahiran->update([
                                'status_ajuan_id' => StatusAjuan::SELESAI,
                            ]);
                        }

                        // Sync with kutipan dua akta kematian if exists
                        if ($this->record->kutipanDuaAktaKematian) {
                            $this->record->kutipanDuaAktaKematian->update([
                                'status_ajuan_id' => StatusAjuan::SELESAI,
                            ]);
                        }

                        // Sync with kutipan dua akta perkawinan if exists
                        if ($this->record->kutipanDuaAktaPerkawinan) {
                            $this->record->kutipanDuaAktaPerkawinan->update([
                                'status_ajuan_id' => StatusAjuan::SELESAI,
                            ]);
                        }

                        // Sync with kutipan dua akta perceraian if exists
                        if ($this->record->kutipanDuaAktaPerceraian) {
                            $this->record->kutipanDuaAktaPerceraian->update([
                                'status_ajuan_id' => StatusAjuan::SELESAI,
                            ]);
                        }

                        // Sync with catatan pinggir if exists
                        if ($this->record->catatanPinggir) {
                            $this->record->catatanPinggir->update([
                                'status_ajuan_id' => StatusAjuan::SELESAI,
                            ]);
                        }

                        // Log the status change
                        \App\Models\ServiceRequestLog::create([
                            'service_request_id' => $this->record->id,
                            'status_ajuan_id' => StatusAjuan::SELESAI,
                            'user_id' => auth()->id(),
                            'catatan' => 'Selesai dikirim oleh CS',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil')
                            ->success()
                            ->body('Status ajuan telah ditandai sebagai SELESAI')
                            ->send();
                    }
                })
                ->visible(fn () => 
                    $this->record->status_ajuan_id == StatusAjuan::SIAP_KIRIM || 
                    $this->record->status_ajuan_id == StatusAjuan::DITOLAK ||
                    $this->record->status_ajuan_id == StatusAjuan::SELESAI
                ),
        ];
    }
}

