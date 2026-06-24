<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AktaKelahiranResource\Pages;
use App\Models\AktaKelahiran;
use App\Models\User;
use App\Models\JenisLayanan;
use App\Models\JenisProduk;
use App\Models\StatusAjuan;
use App\Models\StatusPelapor;
use App\Services\WilayahService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AktaKelahiranResource extends Resource
{
    protected static ?string $model = AktaKelahiran::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Akta Kelahiran';

    protected static ?string $modelLabel = 'Akta Kelahiran';

    protected static ?string $pluralModelLabel = 'Akta Kelahiran';

    protected static ?string $navigationGroup = 'Layanan Pencatatan Sipil';

    protected static ?int $navigationSort = 1;

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
        // Admin, FO, Operator, dan Loket memiliki akses untuk input layanan
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isFrontOffice() || $user->isOperator());
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user)
            return false;

        // Customer Service hanya bisa read, tidak bisa edit
        if ($user->isCustomerService())
            return false;

        // Admin dan FO memiliki akses penuh untuk edit layanan
        if ($user->isAdmin() || $user->isFrontOffice())
            return true;

        // Operator dan Loket dapat melakukan edit layanan
        if ($user->isOperator() || $user->isLoket())
            return true;

        return false;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (!$user)
            return false;

        // Customer Service hanya bisa read, tidak bisa hapus
        if ($user->isCustomerService())
            return false;

        // Admin, FO, Operator, dan Loket dapat hapus layanan
        if ($user->isAdmin() || $user->isFrontOffice())
            return true;

        // Operator dan Loket dapat hapus layanan
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
                Forms\Components\Section::make('Informasi Akta Kelahiran')
                    ->description('Data akta kelahiran')
                    ->schema([
                        Forms\Components\TextInput::make('nomor')
                            ->label('Nomor Akta')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn() => 'Otomatis')
                            ->visible(fn($record) => $record !== null),
                        Forms\Components\Select::make('kode')
                            ->label('Kode')
                            ->options(AktaKelahiran::getKodeOptions())
                            ->required()
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->native(false)
                            ->helperText('Tahun akan otomatis ditambahkan sesuai tahun berjalan'),
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(true)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\Select::make('kecamatan_id')
                            ->label('Kecamatan')
                            ->options(function (Forms\Get $get, $record) {
                                $options = WilayahService::getDistrictOptions('3310');

                                // Fix UX: Inject current form value into options so it displays immediately after creation
                                $currentVal = $get('kecamatan_id');
                                if ($currentVal && !array_key_exists($currentVal, $options)) {
                                    $options[$currentVal] = $currentVal;
                                }

                                // Ensure saved record value is displayed
                                if ($record && $record->kecamatan_id && !array_key_exists($record->kecamatan_id, $options)) {
                                    $options[$record->kecamatan_id] = $record->kecamatan_id;
                                }
                                return $options;
                            })
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('desa_id', null))
                            ->createOptionUsing(function (array $data, Forms\Set $set) {
                                $newValue = $data['manual_district_name'];
                                $set('kecamatan_id', $newValue);
                                return $newValue;
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('manual_district_name')
                                    ->label('Nama Kecamatan Manual')
                                    ->required(),
                            ])
                            ->helperText('Pilih dari list atau buat baru jika tidak ada'),
                        Forms\Components\Select::make('desa_id')
                            ->label('Desa')
                            ->options(function (Forms\Get $get, $record) {
                                $kecamatanId = $get('kecamatan_id');
                                if (!$kecamatanId) {
                                    return [];
                                }
                                $options = is_numeric($kecamatanId)
                                    ? WilayahService::getVillageOptions($kecamatanId)
                                    : [];

                                // Fix UX: Inject current form value into options
                                $currentVal = $get('desa_id');
                                if ($currentVal && !array_key_exists($currentVal, $options)) {
                                    $options[$currentVal] = $currentVal;
                                }

                                if ($record && $record->desa_id && !array_key_exists($record->desa_id, $options)) {
                                    $options[$record->desa_id] = $record->desa_id;
                                }
                                return $options;
                            })
                            ->required()
                            ->searchable()
                            ->live() // Added live() for reactivity
                            ->disabled(
                                fn(Forms\Get $get, $record) =>
                                !$get('kecamatan_id') ||
                                ($record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            )
                            ->createOptionUsing(function (array $data, Forms\Set $set) {
                                $newValue = $data['manual_village_name'];
                                $set('desa_id', $newValue);
                                return $newValue;
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('manual_village_name')
                                    ->label('Nama Desa Manual')
                                    ->required(),
                            ])
                            ->helperText('Pilih dari list atau buat baru jika tidak ada'),
                    ])
                    ->columns(2),

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

                Forms\Components\Section::make('Status & Produk')
                    ->description('Kelola status dan produk ajuan')
                    ->schema([
                        Forms\Components\Select::make('produk_id')
                            ->label('Jenis Produk')
                            ->options(JenisProduk::all()->pluck('nama_produk', 'id'))
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isOperator && !$isAdmin && !$isLoket),
                        Forms\Components\Select::make('status_ajuan_id')
                            ->label('Status Ajuan')
                            ->options(StatusAjuan::all()->pluck('nama_status', 'id'))
                            ->required()
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isOperator && !$isAdmin && !$isLoket)
                            ->default(StatusAjuan::DIPROSES)
                            ->reactive(),
                        Forms\Components\FileUpload::make('file_produk')
                            ->label('File Produk (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('akta-kelahiran/files')
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
                            ->helperText('Wajib diupload jika produk FILE. Maksimal 4 file'),
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
                    ->label('No Akta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('tempat_lahir')
                    ->label('Tempat Lahir')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->date('d/m/Y')
                    ->sortable(),
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
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => match ($record->status_ajuan_id) {
                        1 => 'info', // DIPROSES
                        2 => 'danger', // DITOLAK
                        3 => 'warning', // SIAP KIRIM
                        4 => 'success', // SIAP DIAMBIL
                        5 => 'gray', // SELESAI
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_ajuan_id')
                    ->label('Status Ajuan')
                    ->options(StatusAjuan::all()->pluck('nama_status', 'id'))
                    ->multiple(),
                Tables\Filters\SelectFilter::make('produk_id')
                    ->label('Jenis Produk')
                    ->options(JenisProduk::all()->pluck('nama_produk', 'id'))
                    ->multiple(),
                Tables\Filters\SelectFilter::make('kecamatan_id')
                    ->label('Kecamatan')
                    ->options(function () {
                        return WilayahService::getDistrictOptions('3310');
                    })
                    ->searchable(),
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
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada akta kelahiran')
            ->emptyStateDescription('Mulai dengan membuat akta kelahiran baru.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Akta Kelahiran')
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
            'index' => Pages\ListAktaKelahiran::route('/'),
            'create' => Pages\CreateAktaKelahiran::route('/create'),
            'view' => Pages\ViewAktaKelahiran::route('/{record}'),
            'edit' => Pages\EditAktaKelahiran::route('/{record}/edit'),
        ];
    }
}

