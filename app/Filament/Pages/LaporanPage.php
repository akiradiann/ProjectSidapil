<?php

namespace App\Filament\Pages;

use App\Exports\LayananExport;
use App\Models\KategoriLayanan;
use App\Models\JenisLayanan;
use App\Models\StatusPelapor;
use App\Models\ServiceRequest;
use App\Models\CatatanPinggir;
use App\Models\User;
use App\Services\WilayahService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class LaporanPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.laporan-page';

    protected static ?string $title = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2; // Below Pengiriman (sort 1)

    // Section 0: Laporan Dispensasi
    public ?string $dispensasi_tanggal = null;
    public $showDispensasiTable = false;
    public $dispensasiTotal = 0;

    // Section 1: Laporan Bulanan
    public ?int $kategori_layanan_id = null;
    public ?int $bulan = null;
    public ?int $tahun = null;
    public $layananData = [];
    public $showLayananTable = false;
    public $layananTotal = 0;

    // Section 1b: Laporan Tahunan
    public ?int $tahunan_kategori_layanan_id = null;
    public ?int $tahunan_tahun = null;
    public ?string $tipeLaporan = null; // 'bulanan' or 'tahunan'

    // Section 2: Statistik Layanan
    public ?string $tanggal_mulai = null;
    public ?string $tanggal_selesai = null;
    public $statistikData = null;
    public $showStatistik = false;

    public function mount(): void
    {
        $this->tahun = (int) date('Y');
        $this->tahunan_tahun = (int) date('Y');
    }

    // DISPENSASI METHODS
    public function tampilkanDispensasi(): void
    {
        $this->validate([
            'dispensasi_tanggal' => 'required|date',
        ]);

        $this->showDispensasiTable = true;

        $this->dispensasiTotal = $this->getDispensasiQuery()->count();

        // Show notification based on result
        if ($this->dispensasiTotal > 0) {
            Notification::make()
                ->title('Data berhasil dimuat')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Data tidak ditemukan')
                ->warning()
                ->body("Tidak ada data dispensasi untuk tanggal yang dipilih.")
                ->send();
        }
    }

    public function downloadDispensasiExcel()
    {
        $this->validate([
            'dispensasi_tanggal' => 'required|date',
        ]);

        if (!$this->showDispensasiTable) {
            Notification::make()
                ->title('Silakan klik "Tampilkan" terlebih dahulu')
                ->warning()
                ->send();
            return;
        }

        $tanggalStr = Carbon::parse($this->dispensasi_tanggal)->format('d-m-Y');
        $fileName = "Laporan Dispensasi_{$tanggalStr}.xlsx";

        $data = $this->getDispensasiQuery()->get();

        try {
            return Excel::download(
                new \App\Exports\LaporanDispensasiExport($data, $this->dispensasi_tanggal),
                $fileName
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saat export Excel')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getDispensasiQuery(): Builder
    {
        if (!$this->dispensasi_tanggal || !$this->showDispensasiTable) {
            return \App\Models\AktaKelahiran::query()->whereRaw('1 = 0');
        }

        // Logic: Laporan dispensasi hanya berfokus pada akta kelahiran 
        // yang memiliki kode TP, TP/LN, dan TP/SPTJM
        $targetCodes = [
            \App\Models\AktaKelahiran::KODE_TP,
            \App\Models\AktaKelahiran::KODE_TP_LN,
            \App\Models\AktaKelahiran::KODE_TP_SPTJM,
        ];

        return \App\Models\AktaKelahiran::query()
            ->whereIn('kode', $targetCodes)
            ->whereDate('created_at', $this->dispensasi_tanggal)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Reset data when kategori or bulan changes
     */
    public function updatedKategoriLayananId($value): void
    {
        $this->resetLayananData();
        if ($this->showLayananTable) {
            $this->resetTable();
        }
    }

    public function updatedBulan($value): void
    {
        $this->resetLayananData();
        if ($this->showLayananTable) {
            $this->resetTable();
        }
    }

    public function updatedTahunanKategoriLayananId($value): void
    {
        $this->resetLayananData();
        if ($this->showLayananTable) {
            $this->resetTable();
        }
    }

    /**
     * All roles can access
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_FRONT_OFFICE,
            User::ROLE_OPERATOR,
            User::ROLE_LOKET,
            User::ROLE_CUSTOMER_SERVICE,
        ]);
    }

    // Form method removed - using direct Livewire properties instead

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getLayananQuery())
            ->columns($this->getTableColumns())
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status_ajuan_id')
                    ->label('Status Ajuan')
                    ->options(\App\Models\StatusAjuan::all()->pluck('nama_status', 'id'))
                    ->multiple(),
                \Filament\Tables\Filters\SelectFilter::make('jenis_produk_id')
                    ->label('Jenis Produk')
                    ->options(\App\Models\JenisProduk::all()->pluck('nama_produk', 'id'))
                    ->multiple(),
                \Filament\Tables\Filters\SelectFilter::make('jenis_layanan_id')
                    ->label('Jenis Layanan')
                    ->options(\App\Models\JenisLayanan::all()->pluck('nama_layanan', 'id'))
                    ->multiple(),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Data tidak ditemukan')
            ->emptyStateDescription('Tidak ada data untuk kategori dan periode yang dipilih.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    protected function getTableColumns(): array
    {
        $kategoriId = null;
        if ($this->tipeLaporan === 'bulanan') {
            $kategoriId = $this->kategori_layanan_id;
        } elseif ($this->tipeLaporan === 'tahunan') {
            $kategoriId = $this->tahunan_kategori_layanan_id;
        } else {
            $kategoriId = $this->kategori_layanan_id ?: $this->tahunan_kategori_layanan_id;
        }

        if (!$kategoriId) {
            return [];
        }

        $kategori = KategoriLayanan::find($kategoriId);
        if (!$kategori) {
            return [];
        }

        $kategoriNama = strtoupper($kategori->nama_kategori);

        // Special handling for Catatan Pinggir
        if ($kategoriNama === 'CATATAN PINGGIR') {
            return [
                TextColumn::make('catatanPinggir.kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('catatanPinggir.nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('catatanPinggir.nama')
                    ->label('Nama')
                    ->getStateUsing(fn($record) => $record->catatanPinggir->nama ?? '')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('catatanPinggir', function (Builder $query) use ($search) {
                            $query->where('nama_sesudah', 'like', "%{$search}%")
                                ->orWhere('nama_anak_pgsh', 'like', "%{$search}%")
                                ->orWhere('nama_anak_pgn', 'like', "%{$search}%")
                                ->orWhere('nama_anak_pgk', 'like', "%{$search}%")
                                ->orWhere('nama_pkoi', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('catatanPinggir.no_hp')
                    ->label('No HP')
                    ->searchable(),
                TextColumn::make('loket_layanan')
                    ->label('Loket Layanan')
                    ->getStateUsing(fn($record) => $record->jenisLayanan->nama_layanan ?? '')
                    ->badge()
                    ->color('info'),
                TextColumn::make('status_pelapor')
                    ->label('Status Pelapor')
                    ->getStateUsing(fn($record) => $record->statusPelapor->nama_status ?? '')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('produk')
                    ->label('Produk')
                    ->getStateUsing(fn($record) => $record->jenisProduk->nama_produk ?? '')
                    ->badge()
                    ->color('success'),
                TextColumn::make('status_ajuan')
                    ->label('Status Ajuan')
                    ->getStateUsing(fn($record) => $record->statusAjuan->nama_status ?? '')
                    ->badge()
                    ->color(fn($record) => match ($record->status_ajuan_id) {
                        1 => 'info',
                        2 => 'danger',
                        3 => 'warning',
                        4 => 'success',
                        5 => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ];
        }

        // Generic columns for other categories
        $columns = [];

        // Add category-specific columns
        $columns = array_merge($columns, $this->getCategorySpecificColumns($kategoriNama));

        // Common columns for all categories
        $columns = array_merge($columns, [
            TextColumn::make('jenisLayanan.nama_layanan')
                ->label('Loket Layanan')
                ->badge()
                ->color('info'),
            TextColumn::make('statusPelapor.nama_status')
                ->label('Status Pelapor')
                ->badge()
                ->color('warning'),
            TextColumn::make('jenisProduk.nama_produk')
                ->label('Produk')
                ->badge()
                ->color('success'),
            TextColumn::make('statusAjuan.nama_status')
                ->label('Status Ajuan')
                ->badge()
                ->color(fn($record) => match ($record->status_ajuan_id) {
                    1 => 'info',
                    2 => 'danger',
                    3 => 'warning',
                    4 => 'success',
                    5 => 'gray',
                    default => 'gray',
                })
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ]);

        return $columns;
    }

    protected function getCategorySpecificColumns(string $kategoriNama): array
    {
        return match ($kategoriNama) {
            'AKTA KELAHIRAN' => [
                TextColumn::make('aktaKelahiran.nomor')
                    ->label('Nomor Akta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('aktaKelahiran.nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('aktaKelahiran.tempat_lahir')
                    ->label('Tempat Lahir')
                    ->searchable(),
                TextColumn::make('tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->getStateUsing(fn($record) => $record->aktaKelahiran->tanggal_lahir ? $record->aktaKelahiran->tanggal_lahir->format('d/m/Y') : '')
                    ->sortable(),
                TextColumn::make('aktaKelahiran.kecamatan_id')
                    ->label('Kecamatan')
                    ->getStateUsing(fn($record) => $record->aktaKelahiran->kecamatan_name ?? '')
                    ->searchable(),
                TextColumn::make('aktaKelahiran.desa_id')
                    ->label('Desa')
                    ->getStateUsing(fn($record) => $record->aktaKelahiran->desa_name ?? '')
                    ->searchable(),
                TextColumn::make('aktaKelahiran.nama_pelapor')
                    ->label('Nama Pelapor')
                    ->searchable(),
                TextColumn::make('aktaKelahiran.no_hp')
                    ->label('No HP')
                    ->searchable(),
            ],
            'AKTA KEMATIAN' => [
                TextColumn::make('aktaKematian.nomor')
                    ->label('Nomor Akta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('aktaKematian.nama')
                    ->label('Nama Jenazah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('aktaKematian.jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->searchable(),
                TextColumn::make('tanggal_kematian')
                    ->label('Tanggal Kematian')
                    ->getStateUsing(fn($record) => $record->aktaKematian->tanggal_kematian ? $record->aktaKematian->tanggal_kematian->format('d/m/Y') : '')
                    ->sortable(),
                TextColumn::make('aktaKematian.kecamatan_id')
                    ->label('Kecamatan')
                    ->getStateUsing(fn($record) => $record->aktaKematian->kecamatan_name ?? '')
                    ->searchable(),
                TextColumn::make('aktaKematian.desa_id')
                    ->label('Desa')
                    ->getStateUsing(fn($record) => $record->aktaKematian->desa_name ?? '')
                    ->searchable(),
                TextColumn::make('aktaKematian.nama_pelapor')
                    ->label('Nama Pelapor')
                    ->searchable(),
                TextColumn::make('aktaKematian.no_hp')
                    ->label('No HP')
                    ->searchable(),
            ],
            'AKTA PERKAWINAN' => [
                TextColumn::make('aktaPerkawinan.nomor')
                    ->label('Nomor Akta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('aktaPerkawinan.nama_mempelai_laki')
                    ->label('Nama Mempelai Laki-Laki')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('aktaPerkawinan.nama_mempelai_perempuan')
                    ->label('Nama Mempelai Perempuan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('aktaPerkawinan.tempat_perkawinan_agama')
                    ->label('Tempat Perkawinan Agama')
                    ->searchable(),
                TextColumn::make('tanggal_perkawinan')
                    ->label('Tanggal Perkawinan')
                    ->getStateUsing(fn($record) => $record->aktaPerkawinan->tanggal_perkawinan ? $record->aktaPerkawinan->tanggal_perkawinan->format('d/m/Y') : '')
                    ->sortable(),
                TextColumn::make('tanggal_pencatatan')
                    ->label('Tanggal Pencatatan')
                    ->getStateUsing(fn($record) => $record->aktaPerkawinan->tanggal_pencatatan ? $record->aktaPerkawinan->tanggal_pencatatan->format('d/m/Y') : '')
                    ->sortable(),
                TextColumn::make('aktaPerkawinan.nama_pelapor')
                    ->label('Nama Pelapor')
                    ->searchable(),
                TextColumn::make('aktaPerkawinan.no_hp')
                    ->label('No HP')
                    ->searchable(),
            ],
            'AKTA PERCERAIAN' => [
                TextColumn::make('aktaPerceraian.nomor')
                    ->label('Nomor Akta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('aktaPerceraian.nomor_akta_perkawinan')
                    ->label('No Akta Perkawinan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_perkawinan')
                    ->label('Tanggal Perkawinan')
                    ->getStateUsing(fn($record) => $record->aktaPerceraian->tanggal_perkawinan ? $record->aktaPerceraian->tanggal_perkawinan->format('d/m/Y') : '')
                    ->sortable(),
                TextColumn::make('aktaPerceraian.nama_suami')
                    ->label('Nama Suami')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('aktaPerceraian.nama_istri')
                    ->label('Nama Istri')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('aktaPerceraian.nomor_penetapan_pengadilan')
                    ->label('No Penetapan Pengadilan')
                    ->searchable(),
                TextColumn::make('aktaPerceraian.nama_pelapor')
                    ->label('Nama Pelapor')
                    ->searchable(),
                TextColumn::make('aktaPerceraian.no_hp')
                    ->label('No HP')
                    ->searchable(),
            ],
            'KUTIPAN DUA KELAHIRAN' => [
                TextColumn::make('kutipanDuaAktaKelahiran.nomor')
                    ->label('Nomor Kutipan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('kutipanDuaAktaKelahiran.no_akta')
                    ->label('No Akta')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaKelahiran.nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kutipanDuaAktaKelahiran.kecamatan_id')
                    ->label('Kecamatan')
                    ->getStateUsing(fn($record) => $record->kutipanDuaAktaKelahiran->kecamatan_id ? \App\Services\WilayahService::getDistrict($record->kutipanDuaAktaKelahiran->kecamatan_id)['name'] ?? $record->kutipanDuaAktaKelahiran->kecamatan_id : '')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaKelahiran.desa_id')
                    ->label('Desa')
                    ->getStateUsing(fn($record) => ($record->kutipanDuaAktaKelahiran->kecamatan_id && $record->kutipanDuaAktaKelahiran->desa_id) ? (\App\Services\WilayahService::getVillage($record->kutipanDuaAktaKelahiran->kecamatan_id, $record->kutipanDuaAktaKelahiran->desa_id)['name'] ?? $record->kutipanDuaAktaKelahiran->desa_id) : '')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaKelahiran.nama_pelapor')
                    ->label('Nama Pelapor')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaKelahiran.no_hp')
                    ->label('No HP')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaKelahiran.alasan')
                    ->label('Alasan')
                    ->searchable(),
            ],
            'KUTIPAN DUA KEMATIAN' => [
                TextColumn::make('kutipanDuaAktaKematian.nomor')
                    ->label('Nomor Kutipan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('kutipanDuaAktaKematian.no_akta')
                    ->label('No Akta')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaKematian.nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kutipanDuaAktaKematian.kecamatan_id')
                    ->label('Kecamatan')
                    ->getStateUsing(fn($record) => $record->kutipanDuaAktaKematian->kecamatan_id ? \App\Services\WilayahService::getDistrict($record->kutipanDuaAktaKematian->kecamatan_id)['name'] ?? $record->kutipanDuaAktaKematian->kecamatan_id : '')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaKematian.desa_id')
                    ->label('Desa')
                    ->getStateUsing(fn($record) => ($record->kutipanDuaAktaKematian->kecamatan_id && $record->kutipanDuaAktaKematian->desa_id) ? (\App\Services\WilayahService::getVillage($record->kutipanDuaAktaKematian->kecamatan_id, $record->kutipanDuaAktaKematian->desa_id)['name'] ?? $record->kutipanDuaAktaKematian->desa_id) : '')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaKematian.nama_pelapor')
                    ->label('Nama Pelapor')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaKematian.no_hp')
                    ->label('No HP')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaKematian.alasan')
                    ->label('Alasan')
                    ->searchable(),
            ],
            'KUTIPAN DUA PERKAWINAN' => [
                TextColumn::make('kutipanDuaAktaPerkawinan.nomor')
                    ->label('Nomor Kutipan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('kutipanDuaAktaPerkawinan.no_akta')
                    ->label('No Akta')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaPerkawinan.nama_suami')
                    ->label('Nama Suami')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kutipanDuaAktaPerkawinan.nama_istri')
                    ->label('Nama Istri')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kutipanDuaAktaPerkawinan.nama_pelapor')
                    ->label('Nama Pelapor')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaPerkawinan.no_hp')
                    ->label('No HP')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaPerkawinan.alasan')
                    ->label('Alasan')
                    ->searchable(),
            ],
            'KUTIPAN DUA PERCERAIAN' => [
                TextColumn::make('kutipanDuaAktaPerceraian.nomor')
                    ->label('Nomor Kutipan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('kutipanDuaAktaPerceraian.nomor_akta')
                    ->label('No Akta')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaPerceraian.nama_suami')
                    ->label('Nama Suami')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kutipanDuaAktaPerceraian.nama_istri')
                    ->label('Nama Istri')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kutipanDuaAktaPerceraian.nama_pelapor')
                    ->label('Nama Pelapor')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaPerceraian.no_hp')
                    ->label('No HP')
                    ->searchable(),
                TextColumn::make('kutipanDuaAktaPerceraian.alasan')
                    ->label('Alasan')
                    ->searchable(),
            ],
            'KARTU KELUARGA' => [
                TextColumn::make('kartuKeluarga.no_kk')
                    ->label('No KK')
                    ->searchable(),
                TextColumn::make('kartuKeluarga.nama_kepala_keluarga')
                    ->label('Nama Kepala Keluarga')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kartuKeluarga.nama_pemohon')
                    ->label('Nama Pemohon')
                    ->searchable(),
            ],
            'PINDAH DATANG' => [
                TextColumn::make('pindahDatang.ajuan')
                    ->label('Ajuan')
                    ->searchable(),
                TextColumn::make('pindahDatang.no_kk')
                    ->label('No KK')
                    ->searchable(),
                TextColumn::make('pindahDatang.nik')
                    ->label('NIK')
                    ->searchable(),
                TextColumn::make('pindahDatang.nama_kepala_keluarga')
                    ->label('Nama Kepala Keluarga')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pindahDatang.nama_pemohon')
                    ->label('Nama Pemohon')
                    ->searchable(),
            ],
            'KTP-EL' => [
                TextColumn::make('ktpEl.nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('ktpEl.nik')
                    ->label('NIK')
                    ->searchable(),
                TextColumn::make('ktpEl.nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
            ],
            'KIA' => [
                TextColumn::make('kia.nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('kia.nik')
                    ->label('NIK')
                    ->searchable(),
                TextColumn::make('kia.nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
            ],
            'SURAT' => [
                TextColumn::make('surat.nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('surat.jenis')
                    ->label('Jenis')
                    ->searchable(),
                TextColumn::make('surat.nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('surat.no_akta')
                    ->label('No Akta')
                    ->searchable(),
                TextColumn::make('surat.tujuan')
                    ->label('Tujuan')
                    ->searchable(),
                TextColumn::make('surat.nama_pemohon')
                    ->label('Nama Pemohon')
                    ->searchable(),
                TextColumn::make('surat.no_hp')
                    ->label('No. HP')
                    ->searchable(),
            ],
            default => [],
        };
    }

    protected function getLayananQuery(): Builder
    {
        if (!$this->showLayananTable || !$this->tipeLaporan) {
            return ServiceRequest::query()->whereRaw('1 = 0'); // Empty query
        }

        if ($this->tipeLaporan === 'bulanan') {
            if (!$this->kategori_layanan_id || !$this->bulan || !$this->tahun) {
                return ServiceRequest::query()->whereRaw('1 = 0');
            }
            $kategoriId = $this->kategori_layanan_id;
            $tahun = $this->tahun;
        } else {
            if (!$this->tahunan_kategori_layanan_id || !$this->tahunan_tahun) {
                return ServiceRequest::query()->whereRaw('1 = 0');
            }
            $kategoriId = $this->tahunan_kategori_layanan_id;
            $tahun = $this->tahunan_tahun;
        }

        $query = ServiceRequest::query()
            ->where('kategori_layanan_id', $kategoriId)
            ->whereYear('created_at', $tahun);

        if ($this->tipeLaporan === 'bulanan') {
            $query->whereMonth('created_at', $this->bulan);
        }

        $query->with([
            'kategoriLayanan',
            'jenisLayanan',
            'statusPelapor',
            'statusAjuan',
            'jenisProduk',
            'aktaKelahiran',
            'aktaKematian',
            'aktaPerkawinan',
            'aktaPerceraian',
            'kutipanDuaAktaKelahiran',
            'kutipanDuaAktaKematian',
            'kutipanDuaAktaPerkawinan',
            'kutipanDuaAktaPerceraian',
            'kartuKeluarga',
            'pindahDatang',
            'ktpEl',
            'kia',
            'catatanPinggir',
            'surat',
        ]);

        return $query;
    }

    public function tampilkanLayanan(): void
    {
        $this->validate([
            'kategori_layanan_id' => 'required|integer|exists:kategori_layanan,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
        ]);

        $this->tipeLaporan = 'bulanan';
        $this->showLayananTable = true;

        $this->layananTotal = ServiceRequest::query()
            ->where('kategori_layanan_id', $this->kategori_layanan_id)
            ->whereYear('created_at', $this->tahun)
            ->whereMonth('created_at', $this->bulan)
            ->count();

        $this->resetTable();

        $kategori = KategoriLayanan::find($this->kategori_layanan_id);
        $kategoriNama = $kategori ? $kategori->nama_kategori : '';

        if ($this->layananTotal > 0) {
            Notification::make()
                ->title('Data berhasil dimuat')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Data tidak ditemukan')
                ->warning()
                ->body("Tidak ada data {$kategoriNama} untuk periode yang dipilih.")
                ->send();
        }
    }

    public function tampilkanLayananTahunan(): void
    {
        $this->validate([
            'tahunan_kategori_layanan_id' => 'required|integer|exists:kategori_layanan,id',
            'tahunan_tahun' => 'required|integer',
        ]);

        $this->tipeLaporan = 'tahunan';
        $this->showLayananTable = true;

        $this->layananTotal = ServiceRequest::query()
            ->where('kategori_layanan_id', $this->tahunan_kategori_layanan_id)
            ->whereYear('created_at', $this->tahunan_tahun)
            ->count();

        $this->resetTable();

        $kategori = KategoriLayanan::find($this->tahunan_kategori_layanan_id);
        $kategoriNama = $kategori ? $kategori->nama_kategori : '';

        if ($this->layananTotal > 0) {
            Notification::make()
                ->title('Data berhasil dimuat')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Data tidak ditemukan')
                ->warning()
                ->body("Tidak ada data {$kategoriNama} untuk tahun yang dipilih.")
                ->send();
        }
    }

    public function downloadExcel()
    {
        if (!$this->showLayananTable || !$this->tipeLaporan) {
            Notification::make()
                ->title('Silakan klik "Tampilkan" terlebih dahulu')
                ->warning()
                ->send();
            return;
        }

        if ($this->tipeLaporan === 'bulanan') {
            $this->validate([
                'kategori_layanan_id' => 'required|integer|exists:kategori_layanan,id',
                'bulan' => 'required|integer|min:1|max:12',
                'tahun' => 'required|integer',
            ]);

            $bulanNama = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];
            $namaBulan = $bulanNama[$this->bulan];
            $kategori = KategoriLayanan::find($this->kategori_layanan_id);
            $kategoriNama = $kategori ? $kategori->nama_kategori : 'Layanan';
            $tahunExport = $this->tahun;

            $fileName = "Laporan_{$kategoriNama}_{$namaBulan}_{$this->tahun}.xlsx";
        } else {
            $this->validate([
                'tahunan_kategori_layanan_id' => 'required|integer|exists:kategori_layanan,id',
                'tahunan_tahun' => 'required|integer',
            ]);

            $namaBulan = 'TAHUNAN';
            $kategori = KategoriLayanan::find($this->tahunan_kategori_layanan_id);
            $kategoriNama = $kategori ? $kategori->nama_kategori : 'Layanan';
            $tahunExport = $this->tahunan_tahun;

            $fileName = "Laporan_{$kategoriNama}_Tahunan_{$this->tahunan_tahun}.xlsx";
        }

        $fileName = str_replace([' ', '/'], '_', $fileName);

        $data = $this->getLayananQuery()->get();

        try {
            return Excel::download(
                new LayananExport($data, $kategoriNama, $namaBulan, $tahunExport),
                $fileName
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saat export Excel')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function downloadExcelBulanan()
    {
        $this->tipeLaporan = 'bulanan';
        return $this->downloadExcel();
    }

    public function downloadExcelTahunan()
    {
        $this->tipeLaporan = 'tahunan';
        return $this->downloadExcel();
    }

    public function tampilkanStatistik(): void
    {
        $this->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $tanggalMulai = Carbon::parse($this->tanggal_mulai)->startOfDay();
        $tanggalSelesai = Carbon::parse($this->tanggal_selesai)->endOfDay();

        // Get statistics
        $statistik = [
            'kategori_layanan' => [],
            'jenis_loket' => [],
            'status_pelapor' => [],
            'total_keseluruhan' => 0,
        ];

        // Total per Kategori Layanan
        $kategoriLayanan = KategoriLayanan::all();
        foreach ($kategoriLayanan as $kategori) {
            $count = ServiceRequest::where('kategori_layanan_id', $kategori->id)
                ->whereBetween('created_at', [$tanggalMulai, $tanggalSelesai])
                ->count();

            $subTotals = [];
            if (strtoupper($kategori->nama_kategori) === 'CATATAN PINGGIR') {
                $codes = [
                    CatatanPinggir::KODE_PRB => 'PERUBAHAN NAMA',
                    CatatanPinggir::KODE_PGSH => 'PENGESAHAN ANAK',
                    CatatanPinggir::KODE_PGN => 'PENGANGKATAN ANAK',
                    CatatanPinggir::KODE_PGK => 'PENGAKUAN ANAK',
                    CatatanPinggir::KODE_PKOI => 'PERUBAHAN KEWARGANEGARAAN',
                ];

                foreach ($codes as $code => $label) {
                    $subCount = ServiceRequest::where('kategori_layanan_id', $kategori->id)
                        ->whereHas('catatanPinggir', function ($query) use ($code) {
                            $query->where('kode', $code);
                        })
                        ->whereBetween('created_at', [$tanggalMulai, $tanggalSelesai])
                        ->count();

                    if ($subCount > 0) {
                        $subTotals[] = [
                            'label' => $label,
                            'count' => $subCount,
                        ];
                    }
                }
            }

            $statistik['kategori_layanan'][] = [
                'nama' => $kategori->nama_kategori,
                'count' => $count,
                'sub_totals' => $subTotals,
            ];
        }

        // Total per Jenis Loket (jenis_layanan_id)
        $jenisLayanan = JenisLayanan::all();
        foreach ($jenisLayanan as $jenis) {
            $count = ServiceRequest::where('jenis_layanan_id', $jenis->id)
                ->whereBetween('created_at', [$tanggalMulai, $tanggalSelesai])
                ->count();

            $statistik['jenis_loket'][] = [
                'nama' => $jenis->nama_layanan,
                'count' => $count,
            ];
        }

        // Total per Status Pelapor
        $statusPelapor = StatusPelapor::all();
        foreach ($statusPelapor as $status) {
            $count = ServiceRequest::where('status_pelapor_id', $status->id)
                ->whereBetween('created_at', [$tanggalMulai, $tanggalSelesai])
                ->count();

            $statistik['status_pelapor'][] = [
                'nama' => $status->nama_status,
                'count' => $count,
            ];
        }

        // Total Keseluruhan
        $statistik['total_keseluruhan'] = ServiceRequest::whereBetween('created_at', [$tanggalMulai, $tanggalSelesai])
            ->count();

        $this->statistikData = $statistik;
        $this->showStatistik = true;

        Notification::make()
            ->title('Statistik berhasil dimuat')
            ->success()
            ->send();
    }

    protected function resetLayananData(): void
    {
        $this->showLayananTable = false;
        $this->layananData = [];
        $this->layananTotal = 0;
    }

    protected function resetStatistik(): void
    {
        $this->showStatistik = false;
        $this->statistikData = null;
    }

    public function getBulanNama(): ?string
    {
        if ($this->tipeLaporan === 'tahunan') {
            return 'Tahunan';
        }

        if (!$this->bulan) {
            return null;
        }

        $bulanNama = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $bulanNama[$this->bulan] ?? null;
    }

    public function getKategoriNama(): ?string
    {
        $kategoriId = $this->tipeLaporan === 'bulanan' ? $this->kategori_layanan_id : $this->tahunan_kategori_layanan_id;
        if (!$kategoriId) {
            return null;
        }

        $kategori = KategoriLayanan::find($kategoriId);
        return $kategori ? $kategori->nama_kategori : null;
    }

    public function formatAngka(int $angka): string
    {
        return number_format($angka, 0, ',', '.');
    }
}
