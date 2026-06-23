<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\KategoriLayanan;
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
use Illuminate\Support\Facades\Storage;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Layanan Umum';

    protected static ?string $modelLabel = 'Ajuan Layanan';

    protected static ?string $pluralModelLabel = 'Ajuan Layanan';

    protected static ?string $navigationGroup = 'Layanan Pencatatan Sipil';

    protected static ?int $navigationSort = 1;

    /**
     * Role-based access control
     * Hidden from sidebar - ServiceRequest is used internally for workflow tracking
     */
    public static function canViewAny(): bool
    {
        // Hide from sidebar - model is still used internally for tracking
        return false;
    }

    public static function canCreate(): bool
    {
        // Admin dan FO memiliki akses penuh untuk input layanan
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isFrontOffice());
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user)
            return false;

        // Admin dan FO memiliki akses penuh untuk edit layanan
        if ($user->isAdmin() || $user->isFrontOffice())
            return true;

        // Operator hanya dapat melakukan edit layanan
        if ($user->isOperator()) {
            return in_array($record->status_ajuan_id, [
                StatusAjuan::DIPROSES,
                StatusAjuan::SIAP_DIAMBIL,
            ]);
        }

        // Loket can edit if status is SIAP DIAMBIL
        if ($user->isLoket()) {
            return $record->status_ajuan_id == StatusAjuan::SIAP_DIAMBIL;
        }

        return false;
    }

    public static function canDelete($record): bool
    {
        // Admin, FO, dan Operator dapat hapus layanan
        $user = auth()->user();
        if (!$user)
            return false;

        if ($user->isAdmin() || $user->isFrontOffice())
            return true;

        // Operator hanya dapat hapus layanan
        if ($user->isOperator())
            return true;

        return false;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isOperator = $user && $user->isOperator();
        $isFrontOffice = $user && $user->isFrontOffice();
        $isAdmin = $user && $user->isAdmin();
        $isLoket = $user && $user->isLoket();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Ajuan')
                    ->description('Data ajuan layanan')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_layanan')
                            ->label('Nomor Layanan')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn() => 'Otomatis')
                            ->visible(fn($record) => $record !== null),
                        Forms\Components\Select::make('kategori_layanan_id')
                            ->label('Kategori Layanan')
                            ->options(KategoriLayanan::all()->pluck('nama_kategori', 'id'))
                            ->required()
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->reactive()
                            ->afterStateUpdated(fn($state, Forms\Set $set) => $set('jenis_layanan_id', null)),
                        Forms\Components\Select::make('jenis_layanan_id')
                            ->label('Jenis Layanan')
                            ->options(JenisLayanan::all()->pluck('nama_layanan', 'id'))
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn($get) => $get('kategori_layanan_id')),
                        Forms\Components\Select::make('status_pelapor_id')
                            ->label('Status Pelapor')
                            ->options(StatusPelapor::all()->pluck('nama_status', 'id'))
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn($get) => $get('kategori_layanan_id')),
                        Forms\Components\TextInput::make('nama_pemohon')
                            ->label('Nama Pemohon')
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\TextInput::make('no_hp')
                            ->label('No. HP / WhatsApp')
                            ->tel()
                            ->maxLength(20)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Produk')
                    ->description('Kelola status dan produk ajuan')
                    ->schema([
                        Forms\Components\Select::make('jenis_produk_id')
                            ->label('Jenis Produk')
                            ->options(JenisProduk::all()->pluck('nama_produk', 'id'))
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isOperator && !$isAdmin)
                            ->reactive(),
                        Forms\Components\Select::make('status_ajuan_id')
                            ->label('Status Ajuan')
                            ->options(function ($record) use ($isLoket) {
                                $options = StatusAjuan::all()->pluck('nama_status', 'id');
                                // If Loket, only show SIAP DIAMBIL and SELESAI
                                if ($isLoket && $record) {
                                    return $options->only([
                                        StatusAjuan::SIAP_DIAMBIL,
                                        StatusAjuan::SELESAI,
                                    ]);
                                }
                                return $options;
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isOperator && !$isAdmin && !$isLoket)
                            ->default(StatusAjuan::DIPROSES)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // If status is DITOLAK, require catatan
                                if ($state == StatusAjuan::DITOLAK) {
                                    $set('catatan_required', true);
                                }
                            }),
                        Forms\Components\FileUpload::make('file_produk')
                            ->label('File Produk (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('service-requests/files')
                            ->preserveFilenames()
                            ->disk('local')
                            ->visibility('private')
                            ->downloadable()
                            ->previewable()
                            ->multiple()
                            ->maxFiles(4)
                            ->disabled(fn($record) => $record && !$isOperator && !$isAdmin)
                            ->visible(
                                fn(Forms\Get $get, $record) =>
                                $get('jenis_produk_id') == 2 || // FILE
                                ($record && $record->jenis_produk_id == 2)
                            )
                            ->required(
                                fn(Forms\Get $get, $record) =>
                                $get('status_ajuan_id') == StatusAjuan::SIAP_KIRIM &&
                                $get('jenis_produk_id') == 2 &&
                                !$get('file_produk') && // Only required if no file exists
                                (!$record || !$record->file_produk)
                            )
                            ->helperText('Wajib diupload jika status SIAP KIRIM dan produk FILE. Maksimal 4 file'),
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

                Forms\Components\Section::make('Informasi Petugas')
                    ->description('Petugas yang menangani ajuan')
                    ->schema([
                        Forms\Components\Select::make('fo_id')
                            ->label('Front Office')
                            ->relationship('fo', 'name')
                            ->disabled()
                            ->visible(fn($record) => $record && $record->fo_id),
                        Forms\Components\Select::make('operator_id')
                            ->label('Operator')
                            ->relationship('operator', 'name')
                            ->disabled()
                            ->visible(fn($record) => $record && $record->operator_id),
                        Forms\Components\Select::make('cs_id')
                            ->label('Customer Service')
                            ->relationship('cs', 'name')
                            ->disabled()
                            ->visible(fn($record) => $record && $record->cs_id),
                        Forms\Components\Select::make('loket_id')
                            ->label('Petugas Loket')
                            ->relationship('loket', 'name')
                            ->disabled()
                            ->visible(fn($record) => $record && $record->loket_id),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn($record) => $record),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_layanan')
                    ->label('Nomor Layanan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('kategoriLayanan.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('nama_pemohon')
                    ->label('Nama Pemohon')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('jenisLayanan.nama_layanan')
                    ->label('Jenis Layanan')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('jenisProduk.nama_produk')
                    ->label('Produk')
                    ->badge()
                    ->color(fn($record) => match ($record->jenis_produk_id) {
                        2 => 'success', // FILE
                        1 => 'warning', // DIAMBIL
                        default => 'gray',
                    })
                    ->toggleable(),
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
                Tables\Columns\IconColumn::make('file_produk')
                    ->label('File')
                    ->boolean()
                    ->trueIcon('heroicon-o-document')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fo.name')
                    ->label('FO')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('operator.name')
                    ->label('Operator')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_layanan_id')
                    ->label('Kategori Layanan')
                    ->relationship('kategoriLayanan', 'nama_kategori')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status_ajuan_id')
                    ->label('Status Ajuan')
                    ->options(StatusAjuan::all()->pluck('nama_status', 'id'))
                    ->multiple(),
                Tables\Filters\SelectFilter::make('jenis_produk_id')
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
                    ->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->visible(fn($record) => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn($record) => static::canDelete($record)),
                Tables\Actions\Action::make('download_file')
                    ->label('Download File')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(
                        fn($record) => $record->file_produk
                        ? static::getUrl('download-file', ['record' => $record])
                        : null
                    )
                    ->openUrlInNewTab()
                    ->visible(fn($record) => $record->file_produk && (
                        auth()->user()?->isAdmin() ||
                        auth()->user()?->isOperator() ||
                        auth()->user()?->isCustomerService()
                    )),
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
            ->modifyQueryUsing(function (Builder $query) use ($user) {
                // Filter based on role
                if ($user && $user->isFrontOffice()) {
                    // FO can see all requests
                    return $query;
                } elseif ($user && $user->isOperator()) {
                    // Operator can see DIPROSES and SIAP DIAMBIL
                    return $query->whereIn('status_ajuan_id', [
                        StatusAjuan::DIPROSES,
                        StatusAjuan::SIAP_DIAMBIL,
                    ]);
                } elseif ($user && $user->isLoket()) {
                    // Loket can see SIAP DIAMBIL
                    return $query->where('status_ajuan_id', StatusAjuan::SIAP_DIAMBIL);
                }
                // Admin can see all
                return $query;
            })
            ->emptyStateHeading('Belum ada ajuan')
            ->emptyStateDescription('Mulai dengan membuat ajuan layanan baru.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Ajuan')
                    ->visible(fn() => static::canCreate()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ServiceRequestResource\RelationManagers\LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRequests::route('/'),
            'create' => Pages\CreateServiceRequest::route('/create'),
            'view' => Pages\ViewServiceRequest::route('/{record}'),
            'edit' => Pages\EditServiceRequest::route('/{record}/edit'),
            'download-file' => Pages\DownloadFile::route('/{record}/download-file'),
        ];
    }
}

