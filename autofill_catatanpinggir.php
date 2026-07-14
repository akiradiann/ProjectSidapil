<?php
function replaceField($file, $search, $replace) {
    if (!file_exists($file)) return false;
    $content = file_get_contents($file);
    if (strpos($content, $search) !== false) {
        $content = str_replace($search, $replace, $content);
        file_put_contents($file, $content);
        echo "Updated $file\n";
        return true;
    }
    echo "Pattern not found in $file\n";
    return false;
}

$file = __DIR__ . '/app/Filament/Resources/CatatanPinggirResource.php';

// 1. PRB
$search1 = <<<'EOD'
                        Forms\Components\TextInput::make('nomor_akta_prb')
                            ->label('Nomor Akta')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PRB),
EOD;
$replace1 = <<<'EOD'
                        Forms\Components\Select::make('nomor_akta_prb')
                            ->label('Nomor Akta')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => 
                                \App\Models\AktaKelahiran::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => 
                                \App\Models\AktaKelahiran::where('nomor', $value)->first()
                                    ? \App\Models\AktaKelahiran::where('nomor', $value)->first()->nomor
                                    : $value
                            )
                            ->createOptionForm([
                                Forms\Components\TextInput::make('no_akta_manual')
                                    ->label('Input No Akta Manual')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data, Forms\Set $set) {
                                return $data['no_akta_manual'];
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $record = \App\Models\AktaKelahiran::where('nomor', $state)->first();
                                    if ($record) {
                                        $set('nama_sebelum', $record->nama);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PRB)
                            ->helperText('Cari berdasar Nomor/Nama, atau klik tombol + untuk ketik manual.'),
EOD;
replaceField($file, trim($search1), trim($replace1));

// 2. PGSH
$search2 = <<<'EOD'
                        Forms\Components\TextInput::make('nomor_akta_pgsh')
                            ->label('Nomor Akta Kelahiran')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGSH),
EOD;
$replace2 = <<<'EOD'
                        Forms\Components\Select::make('nomor_akta_pgsh')
                            ->label('Nomor Akta Kelahiran')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => 
                                \App\Models\AktaKelahiran::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => 
                                \App\Models\AktaKelahiran::where('nomor', $value)->first()
                                    ? \App\Models\AktaKelahiran::where('nomor', $value)->first()->nomor
                                    : $value
                            )
                            ->createOptionForm([
                                Forms\Components\TextInput::make('no_akta_manual')
                                    ->label('Input No Akta Manual')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data, Forms\Set $set) {
                                return $data['no_akta_manual'];
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $record = \App\Models\AktaKelahiran::where('nomor', $state)->first();
                                    if ($record) {
                                        $set('nama_anak', $record->nama);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGSH)
                            ->helperText('Cari berdasar Nomor/Nama, atau klik tombol + untuk ketik manual.'),
EOD;
replaceField($file, trim($search2), trim($replace2));

// 3. PGN
$search3 = <<<'EOD'
                        Forms\Components\TextInput::make('nomor_akta_pgn')
                            ->label('Nomor Akta Kelahiran Anak')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGN),
EOD;
$replace3 = <<<'EOD'
                        Forms\Components\Select::make('nomor_akta_pgn')
                            ->label('Nomor Akta Kelahiran Anak')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => 
                                \App\Models\AktaKelahiran::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => 
                                \App\Models\AktaKelahiran::where('nomor', $value)->first()
                                    ? \App\Models\AktaKelahiran::where('nomor', $value)->first()->nomor
                                    : $value
                            )
                            ->createOptionForm([
                                Forms\Components\TextInput::make('no_akta_manual')
                                    ->label('Input No Akta Manual')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data, Forms\Set $set) {
                                return $data['no_akta_manual'];
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $record = \App\Models\AktaKelahiran::where('nomor', $state)->first();
                                    if ($record) {
                                        $set('nama_anak', $record->nama);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGN)
                            ->helperText('Cari berdasar Nomor/Nama, atau klik tombol + untuk ketik manual.'),
EOD;
replaceField($file, trim($search3), trim($replace3));

// 4. PGK
$search4 = <<<'EOD'
                        Forms\Components\TextInput::make('nomor_akta_pgk')
                            ->label('Nomor Akta Kelahiran Anak')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGK),
EOD;
$replace4 = <<<'EOD'
                        Forms\Components\Select::make('nomor_akta_pgk')
                            ->label('Nomor Akta Kelahiran Anak')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => 
                                \App\Models\AktaKelahiran::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => 
                                \App\Models\AktaKelahiran::where('nomor', $value)->first()
                                    ? \App\Models\AktaKelahiran::where('nomor', $value)->first()->nomor
                                    : $value
                            )
                            ->createOptionForm([
                                Forms\Components\TextInput::make('no_akta_manual')
                                    ->label('Input No Akta Manual')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data, Forms\Set $set) {
                                return $data['no_akta_manual'];
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $record = \App\Models\AktaKelahiran::where('nomor', $state)->first();
                                    if ($record) {
                                        $set('nama_anak', $record->nama);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->visible(fn(Forms\Get $get) => $get('kode') == CatatanPinggir::KODE_PGK)
                            ->helperText('Cari berdasar Nomor/Nama, atau klik tombol + untuk ketik manual.'),
EOD;
replaceField($file, trim($search4), trim($replace4));
