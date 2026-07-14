<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KtpElResource\Pages;
use App\Models\KtpEl;
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

class KtpElResource extends Resource
{
    protected static ?string $model = KtpEl::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'KTP EL';

    protected static ?string $modelLabel = 'KTP EL';

    protected static ?string $pluralModelLabel = 'KTP EL';

    protected static ?string $navigationGroup = 'Layanan Pendaftaran Penduduk';

    protected static ?int $navigationSort = 3;

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
                Forms\Components\Placeholder::make('revisi_banner')
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
                    '))
                    ->visible(fn ($record) => $record !== null && $record->status_ajuan_id == \App\Models\StatusAjuan::REVISI),

                Forms\Components\Section::make('Informasi KTP EL')
                    ->description('Data KTP EL')
                    ->schema([
                        Forms\Components\TextInput::make('nomor')
                            ->label('Nomor / Tahun')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn() => 'Otomatis')
                            ->visible(fn($record) => $record !== null),
                        Forms\Components\Select::make('nik')
                            ->label('NIK')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $results = \App\Models\KtpEl::where('nik', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nik => $item->nik . ' - ' . $item->nama])
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
                                    $ref = \App\Models\KtpEl::where('nik', $state)->first();
                                    if ($ref) {
                                        $set('nama', $ref->nama);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik NIK baru jika belum ada.'),
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                        Forms\Components\TextInput::make('no_hp')
                            ->label('No. HP / WhatsApp')
                            ->tel()
                            ->maxLength(20)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi Layanan')
                    ->description('Data layanan')
                    ->schema([
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
                        Forms\Components\CheckboxList::make('serviceRequest.checklist_persyaratan')
                            ->label('Persyaratan')
                            ->options([
                                    'Kartu Keluarga' => 'Kartu Keluarga',
                                    'KTP-el Lama, jika mengajukan perubahan data atau penggantian' => 'KTP-el Lama, jika mengajukan perubahan data atau penggantian',
                                    'Dokumen Pendukung Perubahan Data, jika terdapat perubahan data' => 'Dokumen Pendukung Perubahan Data, jika terdapat perubahan data',
                                    'Surat Kehilangan dari Kepolisian, jika KTP-el hilang' => 'Surat Kehilangan dari Kepolisian, jika KTP-el hilang',
                                    'KTP-el yang Rusak, jika mengajukan penggantian karena rusak' => 'KTP-el yang Rusak, jika mengajukan penggantian karena rusak',
                                    'Perekaman Biometrik, untuk pemohon yang belum pernah melakukan perekaman' => 'Perekaman Biometrik, untuk pemohon yang belum pernah melakukan perekaman',
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
                            ->label('Jenis Produk')
                            ->options(JenisProduk::where('id', 1)->pluck('nama_produk', 'id'))
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isOperator && !$isAdmin && !$isLoket)
                            ->default(1)
                            ->live(),
                        Forms\Components\Select::make('status_ajuan_id')
                            ->label('Status Ajuan')
                            ->options(function () {
                                $options = StatusAjuan::all()->pluck('nama_status', 'id');
                                $options->forget(StatusAjuan::SIAP_KIRIM);
                                return $options;
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn($record) => $record && !$isOperator && !$isAdmin && !$isLoket)
                            ->default(StatusAjuan::DIPROSES),
                        Forms\Components\FileUpload::make('file_produk')
                            ->label('File Produk (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('ktp-el/files')
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
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->html()
                    ->formatStateUsing(fn ($record, $state) => $record->status_ajuan_id == \App\Models\StatusAjuan::REVISI ? new \Illuminate\Support\HtmlString('<span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-500 shrink-0" style="background-color: #f59e0b; display: inline-block;"></span>' . e($state) . '</span>') : e($state)),
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('jenisProduk.nama_produk')
                    ->label('Produk')
                    ->badge()
                    ->color(fn($record) => match ($record->produk_id) {
                        2 => 'success', // FILE
                        1 => 'warning', // DIAMBIL
                        3 => 'info', // POS
                        5 => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('statusAjuan.nama_status')
                    ->label('Status Ajuan')
                    ->badge()
                    ->color(fn($record) => match ($record->status_ajuan_id) {
                        1 => 'info', // DIPROSES
                        2 => 'danger', // DITOLAK
                        3 => 'success', // SIAP KIRIM
                        4 => 'success', // SIAP DIAMBIL
                        5 => 'gray', // SELESAI
                        6 => 'warning', // REVISI
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('selesai_at')
                    ->label('Selesai')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) {
                            return '-';
                        }
                        try {
                            if ($state instanceof \Carbon\Carbon || $state instanceof \DateTime) {
                                return $state->format('d M Y H:i');
                            }
                            return \Carbon\Carbon::parse($state)->format('d M Y H:i');
                        } catch (\Exception $e) {
                            return '-';
                        }
                    })
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
            ->recordClasses(fn ($record) => $record->status_ajuan_id == \App\Models\StatusAjuan::REVISI ? 'border-s-[6px] border-amber-500 bg-amber-50/20 dark:bg-amber-950/5' : null)
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada KTP EL')
            ->emptyStateDescription('Mulai dengan membuat KTP EL baru.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah KTP EL')
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
            'index' => Pages\ListKtpEls::route('/'),
            'create' => Pages\CreateKtpEl::route('/create'),
            'view' => Pages\ViewKtpEl::route('/{record}'),
            'edit' => Pages\EditKtpEl::route('/{record}/edit'),
        ];
    }
}
