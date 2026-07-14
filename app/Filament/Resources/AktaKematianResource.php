<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AktaKematianResource\Pages;
use App\Models\AktaKematian;
use App\Models\JenisLayanan;
use App\Models\JenisProduk;
use App\Models\StatusAjuan;
use App\Models\StatusPelapor;
use App\Models\User;
use App\Services\WilayahService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AktaKematianResource extends Resource
{
    protected static ?string $model = AktaKematian::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Akta Kematian';

    protected static ?string $modelLabel = 'Akta Kematian';

    protected static ?string $pluralModelLabel = 'Akta Kematian';

    protected static ?string $navigationGroup = 'Layanan Pencatatan Sipil';

    protected static ?int $navigationSort = 2;

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
                Forms\Components\Placeholder::make('revisi_banner')
                    ->hidden(fn ($record) => !$record || $record->status_ajuan_id != StatusAjuan::REVISI)
                    ->columnSpanFull()
                    ->label('') // Menghilangkan label revisi banner
                    ->content(fn ($record) => new \Illuminate\Support\HtmlString('
                        <div style="background-color: #fffbeb; border-left: 6px solid #d97706; padding: 1.25rem; border-radius: 0.375rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); margin-bottom: 1rem;">
                            <div style="display: flex; align-items: flex-start;">
                                <div style="flex-shrink: 0; padding-top: 0.125rem;">
                                    <svg style="height: 1.5rem; width: 1.5rem; color: #d97706;" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div style="margin-left: 1rem;">
                                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #78350f; text-transform: uppercase; letter-spacing: 0.025em; margin: 0;">
                                        AJUAN REVISI
                                    </h3>
                                    <p style="font-size: 0.875rem; font-weight: 500; color: #92400e; margin-top: 0.25rem; margin-bottom: 0;">
                                        Ajuan ini adalah perbaikan dokumen dari ajuan yang sebelumnya DITOLAK.
                                    </p>
                                    ' . ($record->catatan ? '<div style="margin-top: 0.75rem; padding: 0.75rem; background-color: #fff9e6; border: 1px solid #fef3c7; border-radius: 0.375rem;"><p style="font-size: 0.875rem; color: #92400e; margin: 0;"><strong>Catatan Penolakan Sebelumnya:</strong> ' . e($record->catatan) . '</p></div>' : '') . '
                                </div>
                            </div>
                        </div>
                    ')),
                Forms\Components\Section::make('Informasi Akta Kematian')
                    ->description('Data akta kematian')
                    ->schema([
                        Forms\Components\TextInput::make('nomor')
                            ->label('Nomor Akta')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn() => 'Otomatis')
                            ->visible(fn($record) => $record !== null),
                        Forms\Components\TextInput::make('kode')
                            ->label('Kode')
                            ->disabled()
                            ->default(fn() => 'KM/' . date('Y'))
                            ->helperText('Kode otomatis KM/(tahun berjalan)'),
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Jenazah')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->options([
                                'L' => 'Laki-Laki',
                                'P' => 'Perempuan',
                            ])
                            ->native(false)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\DatePicker::make('tanggal_kematian')
                            ->label('Tanggal Kematian')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(true)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\Select::make('kecamatan_id')
                            ->label('Kecamatan')
                            ->options(WilayahService::getDistrictOptions('3310'))
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
                            ->live() // Added live for reactivity
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
                            ->label('Layanan')
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
                            ->options([
                                    'Surat Keterangan Kematian' => 'Surat Keterangan Kematian',
                                    'Kartu Keluarga Almarhum/Almarhumah' => 'Kartu Keluarga Almarhum/Almarhumah',
                                    'KTP-el Almarhum/Almarhumah' => 'KTP-el Almarhum/Almarhumah',
                                    'KTP-el Pelapor' => 'KTP-el Pelapor',
                            ])
                            ->bulkToggleable()
                            ->columns(1)
                    ])
                    ->visible(fn ($record) => ($isOperator || $isAdmin) && $record !== null)
                    ->collapsible(),

                Forms\Components\Section::make('Status & Produk')
                    ->description('Kelola status dan produk ajuan')
                    ->schema([
                        Forms\Components\Select::make('produk_id')
                            ->label('Produk')
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
                            ->label('Upload File (Opsional)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('akta-kematian/files')
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
                                $get('produk_id') == 2 ||
                                ($record && $record->produk_id == 2)
                            )
                            ->helperText('Maksimal 4 file'),
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
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_kematian')
                    ->label('Tanggal Kematian')
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
                Tables\Filters\SelectFilter::make('status_ajuan_id')
                    ->label('Status Ajuan')
                    ->options(StatusAjuan::all()->pluck('nama_status', 'id'))
                    ->multiple(),
                Tables\Filters\SelectFilter::make('produk_id')
                    ->label('Produk')
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
            ->recordClasses(fn ($record) => $record->status_ajuan_id == \App\Models\StatusAjuan::REVISI ? 'border-s-[6px] border-amber-500 bg-amber-50/20 dark:bg-amber-950/5' : null)
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada akta kematian')
            ->emptyStateDescription('Mulai dengan membuat akta kematian baru.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Akta Kematian')
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
            'index' => Pages\ListAktaKematian::route('/'),
            'create' => Pages\CreateAktaKematian::route('/create'),
            'view' => Pages\ViewAktaKematian::route('/{record}'),
            'edit' => Pages\EditAktaKematian::route('/{record}/edit'),
        ];
    }
}




