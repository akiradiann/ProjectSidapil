<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatatanPinggirResource\Pages;
use App\Models\CatatanPinggir;
use App\Models\User;
use App\Models\JenisLayanan;
use App\Models\JenisProduk;
use App\Models\StatusAjuan;
use App\Models\StatusPelapor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CatatanPinggirResource extends Resource
{
    protected static ?string $model = CatatanPinggir::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Catatan Pinggir';

    protected static ?string $modelLabel = 'Catatan Pinggir';

    protected static ?string $pluralModelLabel = 'Catatan Pinggir';

    protected static ?string $navigationGroup = 'Layanan Pencatatan Sipil';

    protected static ?int $navigationSort = 9;

    /**
     * Role-based access control
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_FRONT_OFFICE,
            User::ROLE_OPERATOR,
            User::ROLE_LOKET,
        ]);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isFrontOffice());
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user)
            return false;

        if ($user->isCustomerService())
            return false;

        if ($user->isAdmin() || $user->isFrontOffice())
            return true;

        if ($user->isOperator() || $user->isLoket())
            return true;

        return false;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (!$user)
            return false;

        if ($user->isCustomerService())
            return false;

        if ($user->isAdmin() || $user->isFrontOffice())
            return true;

        if ($user->isOperator())
            return true;

        return false;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isFrontOffice = $user && $user->isFrontOffice();
        $isAdmin = $user && $user->isAdmin();
        $isOperator = $user && $user->isOperator();
        $isLoket = $user && $user->isLoket();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Catatan Pinggir')
                    ->description('Data catatan pinggir')
                    ->schema([
                        Forms\Components\TextInput::make('nomor')
                            ->label('Nomor')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn() => 'Otomatis')
                            ->visible(fn($record) => $record !== null),
                        Forms\Components\Select::make('kode')
                            ->label('Kode')
                            ->options(CatatanPinggir::getKodeOptions())
                            ->required()
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('kode_changed', true))
                            ->helperText(new HtmlString(
                                '* PRB (PERUBAHAN NAMA)<br>' .
                                '* PGSH (PENGESAHAN ANAK)<br>' .
                                '* PGN (PENGANGKATAN ANAK)<br>' .
                                '* PGK (PENGAKUAN ANAK)<br>' .
                                '* PKOI (PERUBAHAN KEWARGANEGARAAN)'
                            ))


                    ])
                    ->columns(2),

                // PRB - Perubahan Nama
                Forms\Components\Section::make('Perubahan Nama')
                    ->schema([
                        Forms\Components\Select::make('nomor_akta_prb')
                            ->label('Nomor Akta')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $results = \App\Models\AktaKelahiran::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama])
                                    ->toArray();
                                if ($search && !isset($results[$search])) {
                                    $results = [$search => $search] + $results;
                                }
                                return $results;
                            })
                            ->getOptionLabelUsing(fn ($value): string => $value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $ref = \App\Models\AktaKelahiran::where('nomor', $state)->first();
                                    if ($ref) {
                                        $set('nama_sebelum', $ref->nama);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PRB)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
                        Forms\Components\TextInput::make('nama_sebelum')
                            ->label('Nama Sebelum')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PRB),
                        Forms\Components\TextInput::make('nama_sesudah')
                            ->label('Nama Sesudah')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PRB),
                        Forms\Components\TextInput::make('no_penetapan_pengadilan_prb')
                            ->label('No Penetapan Pengadilan')
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PRB),
                        Forms\Components\DatePicker::make('tanggal_penetapan_prb')
                            ->label('Tanggal Penetapan')
                            ->displayFormat('d/m/Y')
                            ->native(true)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PRB),
                    ])
                    ->columns(2)
                    ->visible(
                        fn(Forms\Get $get, $record) =>
                        $get('kode') == CatatanPinggir::KODE_PRB ||
                        ($record && $record->kode == CatatanPinggir::KODE_PRB)
                    ),

                // PGSH - Pengesahan
                Forms\Components\Section::make('Pengesahan')
                    ->schema([
                        Forms\Components\Select::make('nomor_akta_pgsh')
                            ->label('Nomor Akta')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $results = \App\Models\AktaKelahiran::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama])
                                    ->toArray();
                                if ($search && !isset($results[$search])) {
                                    $results = [$search => $search] + $results;
                                }
                                return $results;
                            })
                            ->getOptionLabelUsing(fn ($value): string => $value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $ref = \App\Models\AktaKelahiran::where('nomor', $state)->first();
                                    if ($ref) {
                                        $set('nama_anak_pgsh', $ref->nama);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGSH)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
                        Forms\Components\TextInput::make('nama_anak_pgsh')
                            ->label('Nama Anak')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGSH),
                        Forms\Components\TextInput::make('nama_ibu_pgsh')
                            ->label('Nama Ibu')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGSH),
                        Forms\Components\TextInput::make('nama_ayah_pgsh')
                            ->label('Nama Ayah')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGSH),
                        Forms\Components\Textarea::make('dasar_pengesahan')
                            ->label('Dasar Pengesahan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->required()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGSH),
                    ])
                    ->columns(2)
                    ->visible(
                        fn(Forms\Get $get, $record) =>
                        $get('kode') == CatatanPinggir::KODE_PGSH ||
                        ($record && $record->kode == CatatanPinggir::KODE_PGSH)
                    ),

                // PGN - Pengangkatan Anak
                Forms\Components\Section::make('Pengangkatan Anak')
                    ->schema([
                        Forms\Components\Select::make('nomor_akta_pgn')
                            ->label('Nomor Akta')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $results = \App\Models\AktaKelahiran::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama])
                                    ->toArray();
                                if ($search && !isset($results[$search])) {
                                    $results = [$search => $search] + $results;
                                }
                                return $results;
                            })
                            ->getOptionLabelUsing(fn ($value): string => $value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $ref = \App\Models\AktaKelahiran::where('nomor', $state)->first();
                                    if ($ref) {
                                        $set('nama_anak_pgn', $ref->nama);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGN)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
                        Forms\Components\TextInput::make('nama_anak_pgn')
                            ->label('Nama Anak')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGN),
                        Forms\Components\TextInput::make('nama_ayah_kandung')
                            ->label('Nama Ayah Kandung')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGN),
                        Forms\Components\TextInput::make('nama_ibu_kandung')
                            ->label('Nama Ibu Kandung')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGN),
                        Forms\Components\TextInput::make('no_penetapan_pengadilan_pgn')
                            ->label('No. Penetapan Pengadilan')
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGN),
                        Forms\Components\TextInput::make('nama_ayah_angkat')
                            ->label('Nama Ayah Angkat')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGN),
                        Forms\Components\TextInput::make('nama_ibu_angkat')
                            ->label('Nama Ibu Angkat')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGN),
                    ])
                    ->columns(2)
                    ->visible(
                        fn(Forms\Get $get, $record) =>
                        $get('kode') == CatatanPinggir::KODE_PGN ||
                        ($record && $record->kode == CatatanPinggir::KODE_PGN)
                    ),

                // PGK - Pengakuan Anak
                Forms\Components\Section::make('Pengakuan Anak')
                    ->schema([
                        Forms\Components\Select::make('nomor_akta_pgk')
                            ->label('Nomor Akta')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $results = \App\Models\AktaKelahiran::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama])
                                    ->toArray();
                                if ($search && !isset($results[$search])) {
                                    $results = [$search => $search] + $results;
                                }
                                return $results;
                            })
                            ->getOptionLabelUsing(fn ($value): string => $value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $ref = \App\Models\AktaKelahiran::where('nomor', $state)->first();
                                    if ($ref) {
                                        $set('nama_anak_pgk', $ref->nama);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGK)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
                        Forms\Components\TextInput::make('nama_anak_pgk')
                            ->label('Nama Anak')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGK),
                        Forms\Components\TextInput::make('nama_ibu_pgk')
                            ->label('Nama Ibu')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGK),
                        Forms\Components\TextInput::make('nama_ayah_pgk')
                            ->label('Nama Ayah')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGK),
                        Forms\Components\Textarea::make('dasar_pengakuan')
                            ->label('Dasar Pengakuan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->required()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGK),
                    ])
                    ->columns(2)
                    ->visible(
                        fn(Forms\Get $get, $record) =>
                        $get('kode') == CatatanPinggir::KODE_PGK ||
                        ($record && $record->kode == CatatanPinggir::KODE_PGK)
                    ),

                // PKOI - Perubahan Kewarganegaraan
                Forms\Components\Section::make('Perubahan Kewarganegaraan')
                    ->schema([
                        Forms\Components\Select::make('perubahan_kewarganegaraan')
                            ->label('Perubahan')
                            ->options([
                                'WNI-WNA' => 'WNI-WNA',
                                'WNA-WNI' => 'WNA-WNI',
                            ])
                            ->required()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PKOI),
                        Forms\Components\TextInput::make('nama_pkoi')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PKOI),
                        Forms\Components\DatePicker::make('tanggal_lahir_pkoi')
                            ->label('Tanggal Lahir')
                            ->displayFormat('d/m/Y')
                            ->native(true)
                            ->required()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PKOI),
                        Forms\Components\Select::make('jenis_kelamin_pkoi')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-Laki',
                                'P' => 'Perempuan',
                            ])
                            ->required()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PKOI),
                        Forms\Components\Textarea::make('alamat_pkoi')
                            ->label('Alamat')
                            ->rows(3)
                            ->required()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PKOI),
                        Forms\Components\TextInput::make('negara_asal')
                            ->label('Negara Asal')
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PKOI),
                        Forms\Components\TextInput::make('negara_tujuan')
                            ->label('Negara Tujuan')
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PKOI),
                        Forms\Components\TextInput::make('surat_dasar_keputusan')
                            ->label('Surat/Dasar Keputusan')
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PKOI),
                        Forms\Components\DatePicker::make('tanggal_surat_keputusan')
                            ->label('Tanggal Surat/Keputusan')
                            ->displayFormat('d/m/Y')
                            ->native(true)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PKOI),
                        Forms\Components\Select::make('alasan_perubahan')
                            ->label('Alasan Perubahan')
                            ->options([
                                'PERMOHONAN KEWARGANEGARAAN' => 'PERMOHONAN KEWARGANEGARAAN',
                                'PERKAWINAN (UU NO.12/2006 Ps.19)' => 'PERKAWINAN (UU NO.12/2006 Ps.19)',
                            ])
                            ->required()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PKOI),
                    ])
                    ->columns(2)
                    ->visible(
                        fn(Forms\Get $get, $record) =>
                        $get('kode') == CatatanPinggir::KODE_PKOI ||
                        ($record && $record->kode == CatatanPinggir::KODE_PKOI)
                    ),

                // Field default
                Forms\Components\Section::make('Informasi Pelapor')
                    ->description('Data pelapor')
                    ->schema([
                        Forms\Components\TextInput::make('nama_pelapor')
                            ->label('Nama Pelapor')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\TextInput::make('no_hp')
                            ->label('No. HP')
                            ->tel()
                            ->maxLength(20)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\Select::make('layanan_id')
                            ->label('Jenis Layanan')
                            ->options(JenisLayanan::all()->pluck('nama_layanan', 'id'))
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\Select::make('status_pelapor_id')
                            ->label('Status Pelapor')
                            ->options(StatusPelapor::all()->pluck('nama_status', 'id'))
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Checklist Persyaratan')
                    ->description('Centang dokumen persyaratan yang sudah lengkap')
                    ->schema([
                        Forms\Components\CheckboxList::make('checklist_persyaratan')
                            ->label('Persyaratan')
                            ->options(function (Forms\Get $get) {
                                $kode = $get('kode');
                                if ($kode == CatatanPinggir::KODE_PRB) {
                                    return [
                                        'Penetapan Pengadilan Negeri tentang Perubahan Nama' => 'Penetapan Pengadilan Negeri tentang Perubahan Nama',
                                        'Akta Kelahiran' => 'Akta Kelahiran',
                                        'Kartu Keluarga' => 'Kartu Keluarga',
                                        'KTP-el Pemohon' => 'KTP-el Pemohon'
                                    ];
                                } elseif ($kode == CatatanPinggir::KODE_PGK) {
                                    return [
                                        'Surat Pernyataan Pengakuan Anak dari Ayah Biologis' => 'Surat Pernyataan Pengakuan Anak dari Ayah Biologis',
                                        'Surat Persetujuan Ibu Kandung' => 'Surat Persetujuan Ibu Kandung',
                                        'Akta Kelahiran Anak' => 'Akta Kelahiran Anak',
                                        'Kartu Keluarga' => 'Kartu Keluarga',
                                        'KTP-el Ayah dan Ibu' => 'KTP-el Ayah dan Ibu',
                                        'Penetapan Pengadilan, jika pengakuan berdasarkan putusan pengadilan' => 'Penetapan Pengadilan, jika pengakuan berdasarkan putusan pengadilan'
                                    ];
                                } elseif ($kode == CatatanPinggir::KODE_PGSH) {
                                    return [
                                        'Akta Kelahiran Anak' => 'Akta Kelahiran Anak',
                                        'Buku Nikah/Akta Perkawinan Orang Tua' => 'Buku Nikah/Akta Perkawinan Orang Tua',
                                        'Kartu Keluarga' => 'Kartu Keluarga',
                                        'KTP-el Kedua Orang Tua' => 'KTP-el Kedua Orang Tua',
                                        'Penetapan Pengadilan, jika diperlukan' => 'Penetapan Pengadilan, jika diperlukan'
                                    ];
                                } elseif ($kode == CatatanPinggir::KODE_PGN) {
                                    return [
                                        'Penetapan Pengadilan tentang Pengangkatan Anak' => 'Penetapan Pengadilan tentang Pengangkatan Anak',
                                        'Kutipan Akta Kelahiran Anak' => 'Kutipan Akta Kelahiran Anak',
                                        'Kartu Keluarga Orang Tua Angkat' => 'Kartu Keluarga Orang Tua Angkat',
                                        'KTP-el Orang Tua Angkat' => 'KTP-el Orang Tua Angkat'
                                    ];
                                } elseif ($kode == CatatanPinggir::KODE_PKOI) {
                                    return [
                                        'Keputusan atau Bukti Resmi Perubahan Kewarganegaraan' => 'Keputusan atau Bukti Resmi Perubahan Kewarganegaraan',
                                        'Berita Acara Pengucapan Sumpah/Janji Setia' => 'Berita Acara Pengucapan Sumpah/Janji Setia',
                                        'Akta Kelahiran' => 'Akta Kelahiran',
                                        'Kartu Keluarga' => 'Kartu Keluarga',
                                        'KTP-el' => 'KTP-el',
                                        'Paspor atau Dokumen Perjalanan' => 'Paspor atau Dokumen Perjalanan',
                                        'Akta Perkawinan, jika sudah menikah' => 'Akta Perkawinan, jika sudah menikah',
                                        'Keputusan Presiden/Menteri, sesuai jenis perubahan kewarganegaraan' => 'Keputusan Presiden/Menteri, sesuai jenis perubahan kewarganegaraan'
                                    ];
                                }
                                return [];
                            })
                            ->bulkToggleable()
                            ->columns(1)
                    ])
                    ->visible(fn ($record) => ($isOperator || $isAdmin) && $record !== null)
                    ->collapsible(),

                Forms\Components\Section::make('Status & Produk')
                    ->description('Kelola status dan produk ajuan')
                    ->schema([
                        Forms\Components\Select::make('produk_id')
                            ->label('Jenis Produk')
                            ->options(JenisProduk::all()->pluck('nama_produk', 'id'))
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isOperator && !$isAdmin && !$isLoket)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $statusId = $get('status_ajuan_id');
                                if ($state == 1 && $statusId == StatusAjuan::SIAP_KIRIM) {
                                    $set('status_ajuan_id', null);
                                } elseif ($state == 2 && $statusId == StatusAjuan::SIAP_DIAMBIL) {
                                    $set('status_ajuan_id', null);
                                }
                            }),
                        Forms\Components\Select::make('status_ajuan_id')
                            ->label('Status Ajuan')
                            ->options(function (Forms\Get $get) {
                                $options = StatusAjuan::all()->pluck('nama_status', 'id');
                                $produkId = $get('produk_id');
                                if ($produkId == 1) { // DIAMBIL
                                    $options->forget(StatusAjuan::SIAP_KIRIM);
                                } elseif ($produkId == 2) { // FILE
                                    $options->forget(StatusAjuan::SIAP_DIAMBIL);
                                }
                                return $options;
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isOperator && !$isAdmin && !$isLoket)
                            ->default(StatusAjuan::DIPROSES),
                        Forms\Components\FileUpload::make('file_produk')
                            ->label('File Produk (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('catatan-pinggir/files')
                            ->preserveFilenames()
                            ->disk('local')
                            ->visibility('private')
                            ->downloadable()
                            ->previewable()
                            ->multiple()
                            ->maxFiles(4)
                            ->disabled(fn($record) => $record && !$isOperator && !$isAdmin && !$isLoket)
                            ->visible(
                                fn(Forms\Get $get, $record) =>
                                $get('produk_id') == 2 || // FILE
                                ($record && $record->produk_id == 2)
                            )
                            ->required(
                                fn(Forms\Get $get, $record) =>
                                ($get('status_ajuan_id') == StatusAjuan::SIAP_KIRIM && $get('produk_id') == 2) ||
                                ($record && $record->status_ajuan_id == StatusAjuan::SIAP_KIRIM && $record->produk_id == 2)
                            )
                            ->helperText('Wajib diupload jika produk FILE dan status SIAP KIRIM. Maksimal 4 file'),
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->required(fn(Forms\Get $get) => $get('status_ajuan_id') == StatusAjuan::DITOLAK)
                            ->helperText(
                                fn(Forms\Get $get) =>
                                $get('status_ajuan_id') == StatusAjuan::DITOLAK
                                ? 'Wajib diisi ketika status DITOLAK'
                                : 'Opsional'
                            ),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->label('No')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->html()
                    ->formatStateUsing(fn ($record, $state) => $record->status_ajuan_id == \App\Models\StatusAjuan::REVISI ? new \Illuminate\Support\HtmlString('<span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-500 shrink-0" style="background-color: #f59e0b; display: inline-block;"></span>' . e($state) . '</span>') : e($state)),
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->getStateUsing(function ($record) {
                        return $record->nama;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search) {
                            $query->where('nama_sesudah', 'like', "%{$search}%")
                                ->orWhere('nama_anak_pgsh', 'like', "%{$search}%")
                                ->orWhere('nama_anak_pgn', 'like', "%{$search}%")
                                ->orWhere('nama_anak_pgk', 'like', "%{$search}%")
                                ->orWhere('nama_pkoi', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(false)
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('nama_pelapor')
                    ->label('Nama Pelapor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('jenisProduk.nama_produk')
                    ->label('Produk')
                    ->badge()
                    ->color(fn($record) => match ($record->produk_id) {
                        2 => 'success', // FILE
                        1 => 'warning', // DIAMBIL
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('statusAjuan.nama_status')
                    ->label('Status Ajuan')
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kode')
                    ->label('Kode')
                    ->options(CatatanPinggir::getKodeOptions())
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status_ajuan_id')
                    ->label('Status Ajuan')
                    ->options(StatusAjuan::all()->pluck('nama_status', 'id'))
                    ->multiple(),
                Tables\Filters\SelectFilter::make('produk_id')
                    ->label('Jenis Produk')
                    ->options(JenisProduk::all()->pluck('nama_produk', 'id'))
                    ->multiple(),
                Tables\Filters\SelectFilter::make('layanan_id')
                    ->label('Jenis Layanan')
                    ->options(JenisLayanan::all()->pluck('nama_layanan', 'id'))
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus yang dipilih')
                        ->visible(fn() => auth()->user()?->isAdmin() ?? false),
                ]),
            ])
            ->recordUrl(null)
            ->recordClasses(fn ($record) => $record->status_ajuan_id == \App\Models\StatusAjuan::REVISI ? 'border-s-[6px] border-amber-500 bg-amber-50/20 dark:bg-amber-950/5' : null)
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada catatan pinggir')
            ->emptyStateDescription('Mulai dengan membuat catatan pinggir baru.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Catatan Pinggir')
                    ->visible(fn() => static::canCreate()),
            ]);
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
            'index' => Pages\ListCatatanPinggirs::route('/'),
            'create' => Pages\CreateCatatanPinggir::route('/create'),
            'view' => Pages\ViewCatatanPinggir::route('/{record}'),
            'edit' => Pages\EditCatatanPinggir::route('/{record}/edit'),
        ];
    }
}

