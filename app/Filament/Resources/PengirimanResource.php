<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengirimanResource\Pages;
use App\Models\ServiceRequest;
use App\Models\StatusAjuan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class PengirimanResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'Pengiriman';

    protected static ?string $modelLabel = 'Pengiriman';

    protected static ?string $pluralModelLabel = 'Pengiriman';

    protected static ?string $navigationGroup = 'Pengiriman';

    protected static ?int $navigationSort = 1;

    /**
     * All roles can view Pengiriman, but only Admin and CS can edit
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_FRONT_OFFICE,
            User::ROLE_OPERATOR,
            User::ROLE_CUSTOMER_SERVICE,
            User::ROLE_LOKET,
        ]);
    }

    public static function canCreate(): bool
    {
        return false; // CS cannot create, only manage existing
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user)
            return false;

        // Only CS and Admin can edit (mark as selesai)
        return $user->isAdmin() || $user->isCustomerService();
    }

    public static function canDelete($record): bool
    {
        return false; // No deletion from pengiriman
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isCS = $user && $user->isCustomerService();
        $isAdmin = $user && $user->isAdmin();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kontak Pemohon')
                    ->schema([
                        Forms\Components\Placeholder::make('nama_pemohon')
                            ->label('Nama Pemohon')
                            ->content(fn($record) => $record->nama_pemohon ?? '-'),
                        Forms\Components\Placeholder::make('no_hp')
                            ->label('No. HP / WhatsApp')
                            ->content(fn($record) => $record->no_hp ?? '-'),
                    ])
                    ->columns(2),

                // Detail Akta Kelahiran (jika ada)
                Forms\Components\Section::make('Detail Layanan - Akta Kelahiran')
                    ->schema([
                        Forms\Components\Placeholder::make('akta_nomor')
                            ->label('Nomor Akta')
                            ->content(fn($record) => $record->aktaKelahiran?->nomor ?? '-'),
                        Forms\Components\Placeholder::make('akta_kode')
                            ->label('Kode')
                            ->content(fn($record) => $record->aktaKelahiran?->kode ?? '-'),
                        Forms\Components\Placeholder::make('akta_nama')
                            ->label('Nama')
                            ->content(fn($record) => $record->aktaKelahiran?->nama ?? '-'),
                        Forms\Components\Placeholder::make('akta_tempat_lahir')
                            ->label('Tempat Lahir')
                            ->content(fn($record) => $record->aktaKelahiran?->tempat_lahir ?? '-'),
                        Forms\Components\Placeholder::make('akta_tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->content(
                                fn($record) => $record->aktaKelahiran?->tanggal_lahir
                                ? \Carbon\Carbon::parse($record->aktaKelahiran->tanggal_lahir)->format('d M Y')
                                : '-'
                            ),
                        Forms\Components\Placeholder::make('kecamatan_name')
                            ->label('Kecamatan')
                            ->content(fn($record) => $record->aktaKelahiran?->kecamatan_name ?? '-'),
                        Forms\Components\Placeholder::make('desa_name')
                            ->label('Desa')
                            ->content(fn($record) => $record->aktaKelahiran?->desa_name ?? '-'),
                        Forms\Components\Placeholder::make('akta_nama_pelapor')
                            ->label('Nama Pelapor')
                            ->content(fn($record) => $record->aktaKelahiran?->nama_pelapor ?? '-'),
                        Forms\Components\Placeholder::make('akta_no_hp')
                            ->label('No. HP')
                            ->content(fn($record) => $record->aktaKelahiran?->no_hp ?? '-'),
                        Forms\Components\Placeholder::make('akta_jenis_layanan')
                            ->label('Jenis Layanan')
                            ->content(fn($record) => $record->aktaKelahiran?->jenisLayanan?->nama_layanan ?? '-'),
                        Forms\Components\Placeholder::make('akta_status_pelapor')
                            ->label('Status Pelapor')
                            ->content(fn($record) => $record->aktaKelahiran?->statusPelapor?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('akta_produk')
                            ->label('Produk')
                            ->content(fn($record) => $record->aktaKelahiran?->jenisProduk?->nama_produk ?? '-'),
                        Forms\Components\Placeholder::make('akta_status_ajuan')
                            ->label('Status Ajuan')
                            ->content(fn($record) => $record->aktaKelahiran?->statusAjuan?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('akta_catatan')
                            ->label('Catatan Akta Kelahiran')
                            ->content(fn($record) => $record->aktaKelahiran?->catatan ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->aktaKelahiran),

                // Detail Akta Kematian (jika ada)
                Forms\Components\Section::make('Detail Layanan - Akta Kematian')
                    ->schema([
                        Forms\Components\Placeholder::make('akta_kematian_nomor')
                            ->label('Nomor Akta')
                            ->content(fn($record) => $record->aktaKematian?->nomor ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_kode')
                            ->label('Kode')
                            ->content(fn($record) => $record->aktaKematian?->kode ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_nama')
                            ->label('Nama Jenazah')
                            ->content(fn($record) => $record->aktaKematian?->nama ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->content(fn($record) => match ($record->aktaKematian?->jenis_kelamin) {
                                'L' => 'Laki-Laki',
                                'P' => 'Perempuan',
                                default => '-',
                            }),
                        Forms\Components\Placeholder::make('akta_kematian_tanggal')
                            ->label('Tanggal Kematian')
                            ->content(
                                fn($record) => $record->aktaKematian?->tanggal_kematian
                                ? \Carbon\Carbon::parse($record->aktaKematian->tanggal_kematian)->format('d M Y')
                                : '-'
                            ),
                        Forms\Components\Placeholder::make('akta_kematian_kecamatan')
                            ->label('Kecamatan')
                            ->content(fn($record) => $record->aktaKematian?->kecamatan_name ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_desa')
                            ->label('Desa')
                            ->content(fn($record) => $record->aktaKematian?->desa_name ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_nama_pelapor')
                            ->label('Nama Pelapor')
                            ->content(fn($record) => $record->aktaKematian?->nama_pelapor ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_no_hp')
                            ->label('No. HP')
                            ->content(fn($record) => $record->aktaKematian?->no_hp ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_jenis_layanan')
                            ->label('Layanan')
                            ->content(fn($record) => $record->aktaKematian?->jenisLayanan?->nama_layanan ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_status_pelapor')
                            ->label('Status Pelapor')
                            ->content(fn($record) => $record->aktaKematian?->statusPelapor?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_produk')
                            ->label('Produk')
                            ->content(fn($record) => $record->aktaKematian?->jenisProduk?->nama_produk ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_status_ajuan')
                            ->label('Status Ajuan')
                            ->content(fn($record) => $record->aktaKematian?->statusAjuan?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('akta_kematian_catatan')
                            ->label('Catatan Akta Kematian')
                            ->content(fn($record) => $record->aktaKematian?->catatan ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->aktaKematian),

                // Detail Akta Perkawinan (jika ada)
                Forms\Components\Section::make('Detail Layanan - Akta Perkawinan')
                    ->schema([
                        Forms\Components\Placeholder::make('akta_perkawinan_nomor')
                            ->label('Nomor')
                            ->content(fn($record) => $record->aktaPerkawinan?->nomor ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_kode')
                            ->label('Kode')
                            ->content(fn($record) => $record->aktaPerkawinan?->kode ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_nama_laki')
                            ->label('Nama Mempelai Laki-Laki')
                            ->content(fn($record) => $record->aktaPerkawinan?->nama_mempelai_laki ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_nama_perempuan')
                            ->label('Nama Mempelai Perempuan')
                            ->content(fn($record) => $record->aktaPerkawinan?->nama_mempelai_perempuan ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_tempat')
                            ->label('Tempat Perkawinan Agama')
                            ->content(fn($record) => $record->aktaPerkawinan?->tempat_perkawinan_agama ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_tanggal')
                            ->label('Tanggal Perkawinan')
                            ->content(
                                fn($record) => $record->aktaPerkawinan?->tanggal_perkawinan
                                ? \Carbon\Carbon::parse($record->aktaPerkawinan->tanggal_perkawinan)->format('d M Y')
                                : '-'
                            ),
                        Forms\Components\Placeholder::make('akta_perkawinan_tanggal_pencatatan')
                            ->label('Tanggal Pencatatan')
                            ->content(
                                fn($record) => $record->aktaPerkawinan?->tanggal_pencatatan
                                ? \Carbon\Carbon::parse($record->aktaPerkawinan->tanggal_pencatatan)->format('d M Y')
                                : '-'
                            ),
                        Forms\Components\Placeholder::make('akta_perkawinan_nama_pelapor')
                            ->label('Nama Pelapor')
                            ->content(fn($record) => $record->aktaPerkawinan?->nama_pelapor ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_no_hp')
                            ->label('No. HP')
                            ->content(fn($record) => $record->aktaPerkawinan?->no_hp ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_jenis_layanan')
                            ->label('Layanan')
                            ->content(fn($record) => $record->aktaPerkawinan?->jenisLayanan?->nama_layanan ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_status_pelapor')
                            ->label('Ajuan')
                            ->content(fn($record) => $record->aktaPerkawinan?->statusPelapor?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_produk')
                            ->label('Produk')
                            ->content(fn($record) => $record->aktaPerkawinan?->jenisProduk?->nama_produk ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_status_ajuan')
                            ->label('Status Ajuan')
                            ->content(fn($record) => $record->aktaPerkawinan?->statusAjuan?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('akta_perkawinan_catatan')
                            ->label('Catatan Akta Perkawinan')
                            ->content(fn($record) => $record->aktaPerkawinan?->catatan ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->aktaPerkawinan),

                // Detail Akta Perceraian (jika ada)
                Forms\Components\Section::make('Detail Layanan - Akta Perceraian')
                    ->schema([
                        Forms\Components\Placeholder::make('akta_perceraian_nomor')
                            ->label('Nomor')
                            ->content(fn($record) => $record->aktaPerceraian?->nomor ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_kode')
                            ->label('Kode')
                            ->content(fn($record) => $record->aktaPerceraian?->kode ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_nomor_akta_perkawinan')
                            ->label('Nomor Akta Perkawinan')
                            ->content(fn($record) => $record->aktaPerceraian?->nomor_akta_perkawinan ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_tanggal_perkawinan')
                            ->label('Tanggal Perkawinan')
                            ->content(
                                fn($record) => $record->aktaPerceraian?->tanggal_perkawinan
                                ? \Carbon\Carbon::parse($record->aktaPerceraian->tanggal_perkawinan)->format('d M Y')
                                : '-'
                            ),
                        Forms\Components\Placeholder::make('akta_perceraian_nama_suami')
                            ->label('Nama Suami')
                            ->content(fn($record) => $record->aktaPerceraian?->nama_suami ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_nama_istri')
                            ->label('Nama Istri')
                            ->content(fn($record) => $record->aktaPerceraian?->nama_istri ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_nomor_penetapan')
                            ->label('Nomor Penetapan Pengadilan')
                            ->content(fn($record) => $record->aktaPerceraian?->nomor_penetapan_pengadilan ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_nama_pelapor')
                            ->label('Nama Pelapor')
                            ->content(fn($record) => $record->aktaPerceraian?->nama_pelapor ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_no_hp')
                            ->label('No. HP')
                            ->content(fn($record) => $record->aktaPerceraian?->no_hp ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_jenis_layanan')
                            ->label('Layanan')
                            ->content(fn($record) => $record->aktaPerceraian?->jenisLayanan?->nama_layanan ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_status_pelapor')
                            ->label('Ajuan')
                            ->content(fn($record) => $record->aktaPerceraian?->statusPelapor?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_produk')
                            ->label('Produk')
                            ->content(fn($record) => $record->aktaPerceraian?->jenisProduk?->nama_produk ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_status_ajuan')
                            ->label('Status Ajuan')
                            ->content(fn($record) => $record->aktaPerceraian?->statusAjuan?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('akta_perceraian_catatan')
                            ->label('Catatan Akta Perceraian')
                            ->content(fn($record) => $record->aktaPerceraian?->catatan ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->aktaPerceraian),

                // Detail Kutipan Dua Akta Kelahiran (jika ada)
                Forms\Components\Section::make('Detail Layanan - Kutipan Dua Akta Kelahiran')
                    ->schema([
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_nomor')
                            ->label('Nomor')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->nomor ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_kode')
                            ->label('Kode')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->kode ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_no_akta')
                            ->label('No Akta')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->no_akta ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_nama')
                            ->label('Nama')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->nama ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_kecamatan')
                            ->label('Kecamatan')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->kecamatan_name ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_desa')
                            ->label('Desa')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->desa_name ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_nama_pelapor')
                            ->label('Nama Pelapor')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->nama_pelapor ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_no_hp')
                            ->label('No. HP')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->no_hp ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_alasan')
                            ->label('Alasan')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->alasan ?? '-')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_jenis_layanan')
                            ->label('Layanan')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->jenisLayanan?->nama_layanan ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_status_pelapor')
                            ->label('Status Pelapor')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->statusPelapor?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_produk')
                            ->label('Produk')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->jenisProduk?->nama_produk ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_status_ajuan')
                            ->label('Status Ajuan')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->statusAjuan?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kelahiran_catatan')
                            ->label('Catatan')
                            ->content(fn($record) => $record->kutipanDuaAktaKelahiran?->catatan ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->kutipanDuaAktaKelahiran),

                // Detail Kutipan Dua Akta Kematian (jika ada)
                Forms\Components\Section::make('Detail Layanan - Kutipan Dua Akta Kematian')
                    ->schema([
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_nomor')
                            ->label('Nomor')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->nomor ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_kode')
                            ->label('Kode')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->kode ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_no_akta')
                            ->label('No Akta')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->no_akta ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_nama')
                            ->label('Nama')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->nama ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_kecamatan')
                            ->label('Kecamatan')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->kecamatan_name ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_desa')
                            ->label('Desa')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->desa_name ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_nama_pelapor')
                            ->label('Nama Pelapor')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->nama_pelapor ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_no_hp')
                            ->label('No. HP')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->no_hp ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_alasan')
                            ->label('Alasan')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->alasan ?? '-')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_jenis_layanan')
                            ->label('Layanan')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->jenisLayanan?->nama_layanan ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_status_pelapor')
                            ->label('Status Pelapor')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->statusPelapor?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_produk')
                            ->label('Produk')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->jenisProduk?->nama_produk ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_status_ajuan')
                            ->label('Status Ajuan')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->statusAjuan?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_kematian_catatan')
                            ->label('Catatan')
                            ->content(fn($record) => $record->kutipanDuaAktaKematian?->catatan ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->kutipanDuaAktaKematian),

                // Detail Kutipan Dua Akta Perkawinan (jika ada)
                Forms\Components\Section::make('Detail Layanan - Kutipan Dua Akta Perkawinan')
                    ->schema([
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_nomor')
                            ->label('Nomor')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->nomor ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_kode')
                            ->label('Kode')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->kode ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_no_akta')
                            ->label('No Akta')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->no_akta ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_nama')
                            ->label('Nama')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->nama ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_nama_pelapor')
                            ->label('Nama Pelapor')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->nama_pelapor ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_no_hp')
                            ->label('No. HP')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->no_hp ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_alasan')
                            ->label('Alasan')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->alasan ?? '-')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_jenis_layanan')
                            ->label('Layanan')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->jenisLayanan?->nama_layanan ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_status_pelapor')
                            ->label('Status Pelapor')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->statusPelapor?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_produk')
                            ->label('Produk')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->jenisProduk?->nama_produk ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_status_ajuan')
                            ->label('Status Ajuan')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->statusAjuan?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perkawinan_catatan')
                            ->label('Catatan')
                            ->content(fn($record) => $record->kutipanDuaAktaPerkawinan?->catatan ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->kutipanDuaAktaPerkawinan),

                // Detail Kutipan Dua Akta Perceraian (jika ada)
                Forms\Components\Section::make('Detail Layanan - Kutipan Dua Akta Perceraian')
                    ->schema([
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_nomor')
                            ->label('Nomor')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->nomor ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_kode')
                            ->label('Kode')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->kode ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_nomor_akta')
                            ->label('Nomor Akta')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->nomor_akta ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_nama_suami')
                            ->label('Nama Suami')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->nama_suami ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_nama_istri')
                            ->label('Nama Istri')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->nama_istri ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_alasan')
                            ->label('Alasan')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->alasan ?? '-')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_nama_pelapor')
                            ->label('Nama Pelapor')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->nama_pelapor ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_no_hp')
                            ->label('No. HP')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->no_hp ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_jenis_layanan')
                            ->label('Layanan')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->jenisLayanan?->nama_layanan ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_status_pelapor')
                            ->label('Status Pelapor')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->statusPelapor?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_produk')
                            ->label('Produk')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->jenisProduk?->nama_produk ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_status_ajuan')
                            ->label('Status Ajuan')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->statusAjuan?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('kutipan_dua_akta_perceraian_catatan')
                            ->label('Catatan')
                            ->content(fn($record) => $record->kutipanDuaAktaPerceraian?->catatan ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->kutipanDuaAktaPerceraian),

                // Detail Catatan Pinggir (jika ada)
                Forms\Components\Section::make('Detail Layanan - Catatan Pinggir')
                    ->schema([
                        Forms\Components\Placeholder::make('catatan_pinggir_nomor')
                            ->label('Nomor')
                            ->content(fn($record) => $record->catatanPinggir?->nomor ?? '-'),
                        Forms\Components\Placeholder::make('catatan_pinggir_kode')
                            ->label('Kode')
                            ->content(fn($record) => $record->catatanPinggir?->kode ?? '-'),
                        Forms\Components\Placeholder::make('catatan_pinggir_nama')
                            ->label('Nama')
                            ->content(fn($record) => $record->catatanPinggir?->nama ?? '-'),
                        Forms\Components\Placeholder::make('catatan_pinggir_nama_pelapor')
                            ->label('Nama Pelapor')
                            ->content(fn($record) => $record->catatanPinggir?->nama_pelapor ?? '-'),
                        Forms\Components\Placeholder::make('catatan_pinggir_no_hp')
                            ->label('No. HP')
                            ->content(fn($record) => $record->catatanPinggir?->no_hp ?? '-'),
                        Forms\Components\Placeholder::make('catatan_pinggir_jenis_layanan')
                            ->label('Layanan')
                            ->content(fn($record) => $record->catatanPinggir?->jenisLayanan?->nama_layanan ?? '-'),
                        Forms\Components\Placeholder::make('catatan_pinggir_status_pelapor')
                            ->label('Status Pelapor')
                            ->content(fn($record) => $record->catatanPinggir?->statusPelapor?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('catatan_pinggir_produk')
                            ->label('Produk')
                            ->content(fn($record) => $record->catatanPinggir?->jenisProduk?->nama_produk ?? '-'),
                        Forms\Components\Placeholder::make('catatan_pinggir_status_ajuan')
                            ->label('Status Ajuan')
                            ->content(fn($record) => $record->catatanPinggir?->statusAjuan?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('catatan_pinggir_catatan')
                            ->label('Catatan')
                            ->content(fn($record) => $record->catatanPinggir?->catatan ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->catatanPinggir),

                // Detail Surat (jika ada)
                Forms\Components\Section::make('Detail Layanan - Surat')
                    ->schema([
                        Forms\Components\Placeholder::make('surat_nomor')
                            ->label('Nomor')
                            ->content(fn($record) => $record->surat?->nomor ?? '-'),
                        Forms\Components\Placeholder::make('surat_jenis')
                            ->label('Jenis Surat')
                            ->content(fn($record) => $record->surat?->jenis ?? '-'),
                        Forms\Components\Placeholder::make('surat_nama')
                            ->label('Nama')
                            ->content(fn($record) => $record->surat?->nama ?? '-'),
                        Forms\Components\Placeholder::make('surat_no_akta')
                            ->label('No Akta')
                            ->content(fn($record) => $record->surat?->no_akta ?? '-'),
                        Forms\Components\Placeholder::make('surat_tujuan')
                            ->label('Tujuan')
                            ->content(fn($record) => $record->surat?->tujuan ?? '-'),
                        Forms\Components\Placeholder::make('surat_nama_pemohon')
                            ->label('Nama Pemohon')
                            ->content(fn($record) => $record->surat?->nama_pemohon ?? '-'),
                        Forms\Components\Placeholder::make('surat_no_hp')
                            ->label('No. HP')
                            ->content(fn($record) => $record->surat?->no_hp ?? '-'),
                        Forms\Components\Placeholder::make('surat_jenis_layanan')
                            ->label('Layanan')
                            ->content(fn($record) => $record->surat?->jenisLayanan?->nama_layanan ?? '-'),
                        Forms\Components\Placeholder::make('surat_status_pelapor')
                            ->label('Status Pelapor')
                            ->content(fn($record) => $record->surat?->statusPelapor?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('surat_produk')
                            ->label('Produk')
                            ->content(fn($record) => $record->surat?->jenisProduk?->nama_produk ?? '-'),
                        Forms\Components\Placeholder::make('surat_status_ajuan')
                            ->label('Status Ajuan')
                            ->content(fn($record) => $record->surat?->statusAjuan?->nama_status ?? '-'),
                        Forms\Components\Placeholder::make('surat_catatan')
                            ->label('Catatan')
                            ->content(fn($record) => $record->surat?->catatan ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->surat),

                Forms\Components\Section::make('File Produk')
                    ->schema([
                        Forms\Components\Placeholder::make('file_info')
                            ->label('File Produk')
                            ->content(function ($record) {
                                if (!$record->file_produk) {
                                    return 'Tidak ada file';
                                }

                                // Handle both array (new format) and string (old format)
                                $fileProduk = $record->file_produk;
                                if (is_array($fileProduk)) {
                                    $count = count($fileProduk);
                                    if ($count === 0) {
                                        return 'Tidak ada file';
                                    }
                                    $fileNames = array_map('basename', $fileProduk);
                                    return 'File tersedia: ' . $count . ' file (' . implode(', ', $fileNames) . ')';
                                } elseif (is_string($fileProduk)) {
                                    return 'File tersedia: ' . basename($fileProduk);
                                }

                                return 'Tidak ada file';
                            }),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('preview')
                                ->label('Lihat Semua File')
                                ->icon('heroicon-o-eye')
                                ->color('success')
                                ->url(
                                    fn($record) => $record->file_produk
                                    ? static::getUrl('preview-files', ['record' => $record])
                                    : null
                                )
                                ->visible(fn($record) => !empty($record->file_produk)),
                        ]),
                    ])
                    ->visible(fn($record) => $record && !empty($record->file_produk)),

                Forms\Components\Section::make('Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan/Alasan')
                            ->disabled()
                            ->rows(4),
                    ])
                    ->visible(fn($record) => $record && !empty($record->catatan)),


                Forms\Components\Section::make('Catatan Tambahan')
                    ->description('Catatan tambahan tentang pengiriman')
                    ->schema([
                        Forms\Components\Textarea::make('catatan_tambahan')
                            ->label('Catatan Tambahan (Opsional)')
                            ->rows(3)
                            ->helperText('Catatan tambahan tentang pengiriman')
                            ->disabled(fn($record) => !$isCS && !$isAdmin),
                    ])
                    ->visible(fn($record) => $record && ($isCS || $isAdmin)),

                Forms\Components\Section::make('Tandai Selesai')
                    ->description('Setelah mengirim file/alasan ke warga, tandai sebagai selesai')
                    ->schema([
                        Forms\Components\Select::make('status_ajuan_id')
                            ->label('Status')
                            ->options([
                                StatusAjuan::SIAP_KIRIM => 'SIAP KIRIM',
                                StatusAjuan::DITOLAK => 'DITOLAK',
                                StatusAjuan::SELESAI => 'SELESAI',
                            ])
                            ->default(fn($record) => $record->status_ajuan_id ?? StatusAjuan::SIAP_KIRIM)
                            ->required()
                            ->disabled(fn($record) => 
                                !$record || 
                                $record->status_ajuan_id == StatusAjuan::SELESAI ||
                                ($record->file_produk && !$record->is_downloaded)
                            )
                            ->helperText(fn($record) => 
                                !$record ? 'Pilih SELESAI setelah mengirim file/alasan ke warga' : (
                                ($record->file_produk && !$record->is_downloaded)
                                ? 'Unduh file terlebih dahulu sebelum menandai selesai!'
                                : 'Pilih SELESAI setelah mengirim file/alasan ke warga'
                                )
                            ),
                    ])
                    ->visible(fn($record) => 
                        $record &&
                        ($isCS || $isAdmin) &&
                        ($record->status_ajuan_id == StatusAjuan::SIAP_KIRIM ||
                            $record->status_ajuan_id == StatusAjuan::DITOLAK)
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kategoriLayanan.nama_kategori')
                    ->label('Kategori Layanan')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_pemohon')
                    ->label('Nama Pemohon')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->html()
                    ->formatStateUsing(fn ($record, $state) => $record->status_ajuan_id == \App\Models\StatusAjuan::REVISI ? new \Illuminate\Support\HtmlString('<span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0" style="background-color: #f59e0b; display: inline-block;"></span>' . e($state) . '</span>') : e($state)),
                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('statusAjuan.nama_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => match ($record->status_ajuan_id) {
                        1 => 'info', // DIPROSES
                        2 => 'danger', // DITOLAK
                        3 => 'success', // SIAP KIRIM (hijau)
                        4 => 'success', // SIAP DIAMBIL
                        5 => 'gray', // SELESAI
                        6 => 'warning', // REVISI (oren)
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenisProduk.nama_produk')
                    ->label('Produk')
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('file_produk')
                    ->label('File')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-arrow-down')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->url(
                        fn($record) => $record->file_produk
                        ? static::getUrl('preview-files', ['record' => $record])
                        : null
                    )
                    ->tooltip(fn($record) => $record->file_produk ? 'Klik untuk preview file' : 'Tidak ada file'),
                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->catatan)
                    ->wrap(),
                Tables\Columns\TextColumn::make('catatan_tambahan')
                    ->label('Catatan Tambahan')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->catatan_tambahan)
                    ->wrap(),
                Tables\Columns\TextColumn::make('selesai_at')
                    ->label('Selesai')
                    ->formatStateUsing(function ($state, $record) {
                        // Get the actual value from the record
                        $value = $record->selesai_at ?? null;

                        if (!$value || $value === null || $value === '') {
                            return '-';
                        }

                        try {
                            if ($value instanceof \Carbon\Carbon || $value instanceof \DateTime) {
                                return $value->format('d M Y H:i');
                            }
                            return \Carbon\Carbon::parse($value)->format('d M Y H:i');
                        } catch (\Exception $e) {
                            return '-';
                        }
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_ajuan_id')
                    ->label('Status')
                    ->options([
                        StatusAjuan::DITOLAK => 'DITOLAK',
                        StatusAjuan::SIAP_KIRIM => 'SIAP KIRIM',
                        StatusAjuan::SELESAI => 'SELESAI',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('jenis_produk_id')
                    ->label('Jenis Produk')
                    ->options([
                        2 => 'FILE',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->label('Lihat')
                    ->extraAttributes(['style' => 'margin-right: 0.375rem !important;']),
                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(function ($record) {
                        $phone = $record->no_hp;
                        if (!$phone) {
                            return null;
                        }
                        if (str_starts_with($phone, '0')) {
                            $phone = '62' . substr($phone, 1);
                        }
                        $phone = preg_replace('/[^0-9]/', '', $phone);
                        
                        $kategori = $record->kategoriLayanan?->nama_kategori ?? '-';
                        
                        if ($record->status_ajuan_id == StatusAjuan::DITOLAK) {
                            $message = "Halo " . $record->nama_pemohon . ",\n\n"
                                . "Kami informasikan bahwa permohonan layanan " . $kategori . " dengan nomor layanan " . $record->nomor_layanan . " DITOLAK.\n\n"
                                . "Alasan Penolakan:\n"
                                . "\"" . ($record->catatan ?? 'Terdapat berkas yang tidak sesuai/belum lengkap') . "\"\n\n"
                                . "Mohon untuk memeriksa kembali alasan penolakan di atas dan melakukan perbaikan dokumen sesuai petunjuk. Apabila terdapat kendala, silakan menghubungi petugas Dinas Kependudukan dan Pencatatan Sipil Kab Klaten.\n\n"
                                . "Terima kasih.\n\n"
                                . "Hormat kami,\n"
                                . "Dinas Kependudukan dan Pencatatan Sipil\n"
                                . "Kabupaten Klaten";
                        } else {
                            $message = "Halo " . $record->nama_pemohon . ",\n\n"
                                . "Kami informasikan bahwa permohonan layanan " . $kategori . " dengan nomor layanan " . $record->nomor_layanan . " telah selesai diproses.\n\n"
                                . "Dokumen hasil layanan telah kami lampirkan pada pesan ini. Mohon untuk memeriksa kembali dokumen yang diterima. Apabila terdapat kendala, silakan menghubungi petugas Dinas Kependudukan dan Pencatatan Sipil Kab Klaten.\n\n"
                                . "Terima kasih telah menggunakan layanan kami. \n\n"
                                . "Hormat kami,\n"
                                . "Dinas Kependudukan dan Pencatatan Sipil\n"
                                . "Kabupaten Klaten";
                        }
                            
                        return "https://wa.me/" . $phone . "?text=" . urlencode($message);
                    })
                    ->openUrlInNewTab()
                    ->extraAttributes(['style' => 'margin-right: 0.375rem !important;']),
                Tables\Actions\Action::make('mark_selesai')
                    ->label(
                        fn($record) => $record->status_ajuan_id == StatusAjuan::SELESAI
                        ? 'Selesai Kirim'
                        : 'Tandai Selesai'
                    )
                    ->icon('heroicon-o-check-circle')
                    ->color(
                        fn($record) => $record->status_ajuan_id == StatusAjuan::SELESAI
                        ? 'primary'
                        : 'success'
                    )
                    ->disabled(fn($record) => 
                        $record->status_ajuan_id == StatusAjuan::SELESAI ||
                        ($record->file_produk && !$record->is_downloaded)
                    )
                    ->tooltip(fn($record) => 
                        ($record->file_produk && !$record->is_downloaded) 
                        ? 'File produk harus diunduh terlebih dahulu' 
                        : null
                    )
                    ->requiresConfirmation(fn($record) => $record->status_ajuan_id != StatusAjuan::SELESAI)
                    ->modalHeading('Tandai Sebagai Selesai')
                    ->modalDescription('Apakah Anda yakin sudah mengirim file/alasan ke warga?')
                    ->action(function ($record) {
                        if ($record->status_ajuan_id != StatusAjuan::SELESAI) {
                            $record->update([
                                'status_ajuan_id' => StatusAjuan::SELESAI,
                                'cs_id' => auth()->id(),
                                'selesai_at' => now(),
                            ]);

                            // Sync with akta kelahiran if exists
                            if ($record->aktaKelahiran) {
                                $record->aktaKelahiran->update([
                                    'status_ajuan_id' => StatusAjuan::SELESAI,
                                ]);
                            }

                            // Sync with akta kematian if exists
                            if ($record->aktaKematian) {
                                $record->aktaKematian->update([
                                    'status_ajuan_id' => StatusAjuan::SELESAI,
                                ]);
                            }

                            // Sync with akta perkawinan if exists
                            if ($record->aktaPerkawinan) {
                                $record->aktaPerkawinan->update([
                                    'status_ajuan_id' => StatusAjuan::SELESAI,
                                ]);
                            }

                            // Sync with akta perceraian if exists
                            if ($record->aktaPerceraian) {
                                $record->aktaPerceraian->update([
                                    'status_ajuan_id' => StatusAjuan::SELESAI,
                                ]);
                            }

                            // Sync with kutipan dua akta kelahiran if exists
                            if ($record->kutipanDuaAktaKelahiran) {
                                $record->kutipanDuaAktaKelahiran->update([
                                    'status_ajuan_id' => StatusAjuan::SELESAI,
                                ]);
                            }

                            // Sync with kutipan dua akta kematian if exists
                            if ($record->kutipanDuaAktaKematian) {
                                $record->kutipanDuaAktaKematian->update([
                                    'status_ajuan_id' => StatusAjuan::SELESAI,
                                ]);
                            }

                            // Sync with kutipan dua akta perkawinan if exists
                            if ($record->kutipanDuaAktaPerkawinan) {
                                $record->kutipanDuaAktaPerkawinan->update([
                                    'status_ajuan_id' => StatusAjuan::SELESAI,
                                ]);
                            }

                            // Sync with kutipan dua akta perceraian if exists
                            if ($record->kutipanDuaAktaPerceraian) {
                                $record->kutipanDuaAktaPerceraian->update([
                                    'status_ajuan_id' => StatusAjuan::SELESAI,
                                ]);
                            }

                            // Sync with catatan pinggir if exists
                            if ($record->catatanPinggir) {
                                $record->catatanPinggir->update([
                                    'status_ajuan_id' => StatusAjuan::SELESAI,
                                ]);
                            }

                            // Sync with surat if exists
                            if ($record->surat) {
                                $record->surat->update([
                                    'status_ajuan_id' => StatusAjuan::SELESAI,
                                ]);
                            }

                            // Log the status change
                            \App\Models\ServiceRequestLog::create([
                                'service_request_id' => $record->id,
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
                    ->visible(
                        fn($record) =>
                        (auth()->user()?->isCustomerService() || auth()->user()?->isAdmin()) &&
                        ($record->status_ajuan_id == StatusAjuan::SIAP_KIRIM ||
                         $record->status_ajuan_id == StatusAjuan::DITOLAK ||
                         $record->status_ajuan_id == StatusAjuan::SELESAI)
                    ),
            ])
            ->actionsAlignment('left')
            ->recordClasses(fn ($record) => $record->status_ajuan_id == \App\Models\StatusAjuan::REVISI ? 'border-s-[6px] border-amber-500 bg-amber-50/20 dark:bg-amber-950/5' : null)
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                // Show requests that need to be sent OR already completed:
                // 1. DITOLAK (need to send rejection reason)
                // 2. SIAP KIRIM with FILE product (need to send file)
                // 3. SELESAI (already completed, but still show in table)
                // Only for Pencatatan Sipil categories (ID 1-9) + Surat (ID 14)
                return $query->whereIn('kategori_layanan_id', [1, 2, 3, 4, 5, 6, 7, 8, 9, 14])
                    ->where(function ($q) {
                    $q->where('status_ajuan_id', StatusAjuan::DITOLAK)
                        ->orWhere('status_ajuan_id', StatusAjuan::SELESAI)
                        ->orWhere(function ($q2) {
                            $q2->where('status_ajuan_id', StatusAjuan::SIAP_KIRIM)
                                ->where('jenis_produk_id', 2); // FILE
                        });
                });
            })
            ->emptyStateHeading('Tidak ada pengiriman')
            ->emptyStateDescription('Tidak ada file atau alasan penolakan yang perlu dikirim saat ini.');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ServiceRequestResource\RelationManagers\LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengiriman::route('/'),
            'view' => Pages\ViewPengiriman::route('/{record}'),
            'edit' => Pages\EditPengiriman::route('/{record}/edit'),
            'download-file' => Pages\DownloadFile::route('/{record}/download-file'),
            'preview-files' => Pages\PreviewFiles::route('/{record}/preview-files'),
        ];
    }
}
