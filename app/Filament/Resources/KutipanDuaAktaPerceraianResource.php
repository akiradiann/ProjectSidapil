<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KutipanDuaAktaPerceraianResource\Pages;
use App\Models\KutipanDuaAktaPerceraian;
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

class KutipanDuaAktaPerceraianResource extends Resource
{
    protected static ?string $model = KutipanDuaAktaPerceraian::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Kutipan Dua Akta Perceraian';

    protected static ?string $modelLabel = 'Kutipan Dua Akta Perceraian';

    protected static ?string $pluralModelLabel = 'Kutipan Dua Akta Perceraian';

    protected static ?string $navigationGroup = 'Layanan Pencatatan Sipil';

    protected static ?int $navigationSort = 8;

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
                Forms\Components\Section::make('Informasi Kutipan Dua Akta Perceraian')
                    ->description('Data kutipan dua akta perceraian')
                    ->schema([
                        Forms\Components\TextInput::make('nomor')
                            ->label('Nomor')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn() => 'Otomatis')
                            ->visible(fn($record) => $record !== null),
                        Forms\Components\Select::make('kode')
                            ->label('Kode')
                            ->options(KutipanDuaAktaPerceraian::getKodeOptions())
                            ->required()
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->native(true)
                            ->helperText('Tahun akan otomatis ditambahkan sesuai tahun berjalan'),
                        Forms\Components\TextInput::make('nomor_akta')
                            ->label('Nomor Akta')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Nomor akta sebelumnya'),
                        Forms\Components\TextInput::make('nama_suami')
                            ->label('Nama Suami')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\TextInput::make('nama_istri')
                            ->label('Nama Istri')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\Textarea::make('alasan')
                            ->label('Alasan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->required()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->helperText('Alasan permintaan kutipan dua (akta hilang, akta rusak, dll)'),
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
                            ->label('Ajuan')
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
                            ->directory('kutipan-dua-akta-perceraian/files')
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
                    ->copyable(),
                Tables\Columns\TextColumn::make('nomor_akta')
                    ->label('No Akta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('nama_suami')
                    ->label('Nama Suami')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('nama_istri')
                    ->label('Nama Istri')
                    ->searchable()
                    ->sortable()
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
            ->emptyStateHeading('Belum ada kutipan dua akta perceraian')
            ->emptyStateDescription('Mulai dengan membuat kutipan dua akta perceraian baru.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Kutipan Dua Akta Perceraian')
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
            'index' => Pages\ListKutipanDuaAktaPerceraians::route('/'),
            'create' => Pages\CreateKutipanDuaAktaPerceraian::route('/create'),
            'view' => Pages\ViewKutipanDuaAktaPerceraian::route('/{record}'),
            'edit' => Pages\EditKutipanDuaAktaPerceraian::route('/{record}/edit'),
        ];
    }
}

