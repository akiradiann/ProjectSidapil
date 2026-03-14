<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Section 0: Laporan Dispensasi --}}
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn rounded-xl">
                <div class="fi-section-content p-6">
                    {{-- Header --}}
                    <div class="flex items-center gap-3 mb-6">
                        <x-filament::icon icon="heroicon-o-clipboard-document-list"
                            class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                Laporan Dispensasi
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Laporan harian khusus Akta Kelahiran (TP, TP/LN, TP/SPTJM)
                            </p>
                        </div>
                    </div>

                    {{-- Form Filter --}}
                    <form wire:submit.prevent="tampilkanDispensasi" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <x-filament::input.wrapper>
                                <x-filament::input type="date" wire:model="dispensasi_tanggal" label="Tanggal" />
                            </x-filament::input.wrapper>
                            <div class="flex items-end gap-2">
                                <x-filament::button type="submit" color="primary" icon="heroicon-o-magnifying-glass">
                                    Tampilkan
                                </x-filament::button>
                                <x-filament::button type="button" color="success" icon="heroicon-o-arrow-down-tray"
                                    wire:click="downloadDispensasiExcel" :disabled="!$showDispensasiTable">
                                    Download Excel
                                </x-filament::button>
                            </div>
                        </div>
                    </form>

                    {{-- Results Area --}}
                    @if (!$showDispensasiTable)
                        {{-- Placeholder --}}
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <x-filament::icon icon="heroicon-o-clipboard-document-list"
                                class="h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" />
                            <p class="text-gray-600 dark:text-gray-400">
                                Pilih tanggal, lalu klik 'Tampilkan' untuk melihat data dispensasi
                            </p>
                        </div>
                    @else
                        {{-- Info Box --}}
                        <div
                            class="mb-4 mt-6 p-4 {{ $dispensasiTotal > 0 ? 'bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800' : 'bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700' }} rounded-lg">
                            <p
                                class="text-sm {{ $dispensasiTotal > 0 ? 'text-success-800 dark:text-success-200' : 'text-gray-800 dark:text-gray-200' }}">
                                <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($dispensasi_tanggal)->format('d F Y') }}
                                |
                                <strong>Total:</strong> {{ $this->formatAngka($dispensasiTotal) }} data
                            </p>
                        </div>

                        {{-- Simple Table --}}
                        @if ($dispensasiTotal > 0)
                            <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                    <thead
                                        class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">No</th>
                                            <th scope="col" class="px-6 py-3">Nama</th>
                                            <th scope="col" class="px-6 py-3">Tempat Lahir</th>
                                            <th scope="col" class="px-6 py-3">Tanggal Lahir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($this->getDispensasiQuery()->get() as $index => $item)
                                            <tr
                                                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                <th scope="row"
                                                    class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                    {{ $index + 1 }}
                                                </th>
                                                <td class="px-6 py-4">
                                                    {{ $item->nama }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    {{ $item->tempat_lahir }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    {{ $item->tanggal_lahir ? $item->tanggal_lahir->format('d/m/Y') : '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Section 1: Laporan Layanan --}}
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn rounded-xl">
                <div class="fi-section-content p-6">
                    {{-- Header --}}
                    <div class="flex items-center gap-3 mb-6">
                        <x-filament::icon icon="heroicon-o-document-text"
                            class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                Laporan Layanan
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Laporan bulanan berdasarkan kategori layanan
                            </p>
                        </div>
                    </div>

                    {{-- Form Filter --}}
                    <form wire:submit.prevent="tampilkanLayanan" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <x-filament::input.wrapper>
                                <x-filament::input.select wire:model="kategori_layanan_id" label="Kategori Layanan"
                                    placeholder="Pilih kategori layanan">
                                    <option value="">Pilih kategori layanan</option>
                                    @foreach(\App\Models\KategoriLayanan::all() as $kategori)
                                        <option value="{{ $kategori->id }}">{{ $kategori->nama_kategori }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                            <x-filament::input.wrapper>
                                <x-filament::input.select wire:model="bulan" label="Periode (Bulan)"
                                    placeholder="Pilih bulan">
                                    <option value="">Pilih bulan</option>
                                    <option value="1">Januari</option>
                                    <option value="2">Februari</option>
                                    <option value="3">Maret</option>
                                    <option value="4">April</option>
                                    <option value="5">Mei</option>
                                    <option value="6">Juni</option>
                                    <option value="7">Juli</option>
                                    <option value="8">Agustus</option>
                                    <option value="9">September</option>
                                    <option value="10">Oktober</option>
                                    <option value="11">November</option>
                                    <option value="12">Desember</option>
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model="tahun" label="Tahun" disabled />
                                <x-slot name="hint">
                                    Tahun otomatis menggunakan tahun berjalan
                                </x-slot>
                            </x-filament::input.wrapper>
                            <div class="flex items-end gap-2">
                                <x-filament::button type="submit" color="primary" icon="heroicon-o-magnifying-glass">
                                    Tampilkan
                                </x-filament::button>
                                <x-filament::button type="button" color="success" icon="heroicon-o-arrow-down-tray"
                                    wire:click="downloadExcel" :disabled="!$showLayananTable">
                                    Download Excel
                                </x-filament::button>
                            </div>
                        </div>
                    </form>

                    {{-- Results Area --}}
                    @if (!$showLayananTable)
                        {{-- Placeholder --}}
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <x-filament::icon icon="heroicon-o-document-text"
                                class="h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" />
                            <p class="text-gray-600 dark:text-gray-400">
                                Pilih kategori layanan dan bulan, lalu klik 'Tampilkan' untuk melihat data
                            </p>
                        </div>
                    @else
                        {{-- Info Box - Always show when showLayananTable is true --}}
                        <div
                            class="mb-4 p-4 {{ $layananTotal > 0 ? 'bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800' : 'bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700' }} rounded-lg">
                            <p
                                class="text-sm {{ $layananTotal > 0 ? 'text-success-800 dark:text-success-200' : 'text-gray-800 dark:text-gray-200' }}">
                                <strong>Kategori:</strong> {{ $this->getKategoriNama() }} |
                                <strong>Periode:</strong> {{ $this->getBulanNama() }} {{ $tahun }} |
                                <strong>Total:</strong> {{ $this->formatAngka($layananTotal) }} data
                            </p>
                        </div>

                        {{-- Table --}}
                        {{ $this->table }}
                    @endif
                </div>
            </div>
        </div>

        {{-- Section 2: Statistik Layanan --}}
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn rounded-xl">
                <div class="fi-section-content p-6">
                    {{-- Header --}}
                    <div class="flex items-center gap-3 mb-6">
                        <x-filament::icon icon="heroicon-o-chart-bar"
                            class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                Statistik Layanan
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Quick view statistik total angka per kategori, loket, dan status pelapor
                            </p>
                        </div>
                    </div>

                    {{-- Form Filter --}}
                    <form wire:submit.prevent="tampilkanStatistik" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <x-filament::input.wrapper>
                                <x-filament::input type="date" wire:model="tanggal_mulai" label="Tanggal Mulai" />
                            </x-filament::input.wrapper>
                            <x-filament::input.wrapper>
                                <x-filament::input type="date" wire:model="tanggal_selesai" label="Tanggal Selesai" />
                            </x-filament::input.wrapper>
                            <div class="flex items-end">
                                <x-filament::button type="submit" color="primary" icon="heroicon-o-chart-bar">
                                    Tampilkan Statistik
                                </x-filament::button>
                            </div>
                        </div>
                    </form>

                    {{-- Statistics Display --}}
                    @if (!$showStatistik)
                        {{-- Placeholder --}}
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <x-filament::icon icon="heroicon-o-chart-bar"
                                class="h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" />
                            <p class="text-gray-600 dark:text-gray-400">
                                Pilih periode, lalu klik 'Tampilkan Statistik' untuk melihat data
                            </p>
                        </div>
                    @else
                        <div class="space-y-6 mt-6">
                            {{-- Kategori Layanan --}}
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    Kategori Layanan
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach ($statistikData['kategori_layanan'] ?? [] as $item)
                                        <div
                                            class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $item['nama'] }}</p>
                                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                                {{ $this->formatAngka($item['count']) }} Ajuan
                                            </p>

                                            @if (!empty($item['sub_totals']))
                                                <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 space-y-1">
                                                    @foreach ($item['sub_totals'] as $sub)
                                                        <div class="flex justify-between text-xs">
                                                            <span class="text-gray-500 dark:text-gray-400">{{ $sub['label'] }}</span>
                                                            <span
                                                                class="font-semibold text-gray-700 dark:text-gray-300">{{ $this->formatAngka($sub['count']) }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Jenis Loket --}}
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    Jenis Loket
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach ($statistikData['jenis_loket'] ?? [] as $item)
                                        <div
                                            class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $item['nama'] }}</p>
                                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                                {{ $this->formatAngka($item['count']) }} Ajuan
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Status Pelapor --}}
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    Status Pelapor
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach ($statistikData['status_pelapor'] ?? [] as $item)
                                        <div
                                            class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $item['nama'] }}</p>
                                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                                {{ $this->formatAngka($item['count']) }} Ajuan
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Total Keseluruhan --}}
                            <div
                                class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-6 border-2 border-primary-200 dark:border-primary-800">
                                <div class="text-center">
                                    <p class="text-sm text-primary-700 dark:text-primary-300 mb-2">TOTAL KESELURUHAN</p>
                                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                                        {{ $this->formatAngka($statistikData['total_keseluruhan'] ?? 0) }} Ajuan
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>