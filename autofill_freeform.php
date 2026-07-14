<?php
/**
 * Converts all autofill Select fields from "createOptionForm" style
 * to "free-form autocomplete" style (typed value appears as first option).
 */

function safeReplace(string $file, string $searchPattern, string $replacement): bool {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        return false;
    }
    $content = file_get_contents($file);
    $normalized = str_replace("\r\n", "\n", $content);
    $normalizedSearch = str_replace("\r\n", "\n", $searchPattern);

    if (strpos($normalized, $normalizedSearch) === false) {
        echo "Pattern not found in: $file\n";
        return false;
    }

    $result = str_replace($normalizedSearch, $replacement, $normalized);
    if (strpos($content, "\r\n") !== false) {
        $result = str_replace("\n", "\r\n", $result);
    }
    file_put_contents($file, $result);
    echo "Updated: $file\n";
    return true;
}

// Template: Kutipan Dua / Catatan Pinggir using AktaKelahiran (search by nomor & nama, fill nama field)
function buildAktaSearchSelect(string $fieldName, string $label, string $model, string $displayField1, string $displayField2, string $fillField1, string $fillField2, string $setField1, string $setField2, string $disabled, string $visible = ''): string {
    $visibleLine = $visible ? "\n                            ->visible($visible)" : '';
    return <<<EOD
                        Forms\Components\Select::make('$fieldName')
                            ->label('$label')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string \$search) {
                                \$results = $model::where('$displayField1', 'like', "%{\$search}%")
                                    ->orWhere('$displayField2', 'like', "%{\$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn (\$item) => [\$item->$displayField1 => \$item->$displayField1 . ' - ' . \$item->$displayField2])
                                    ->toArray();
                                if (\$search && !isset(\$results[\$search])) {
                                    \$results = [\$search => '✎ Pakai: ' . \$search] + \$results;
                                }
                                return \$results;
                            })
                            ->getOptionLabelUsing(fn (\$value): string => \$value)
                            ->live()
                            ->afterStateUpdated(function (\$state, Forms\Set \$set) {
                                if (\$state) {
                                    \$ref = $model::where('$displayField1', \$state)->first();
                                    if (\$ref) {
                                        \$set('$setField1', \$ref->$fillField1);
                                        \$set('$setField2', \$ref->$fillField2);
                                    }
                                }
                            })
                            ->disabled($disabled)$visibleLine
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
EOD;
}

// Template: single fill field (fill only nama)
function buildSingleFillSelect(string $fieldName, string $label, string $model, string $searchField1, string $searchField2, string $fillField, string $setField, string $disabled, string $extraDisplay = ''): string {
    $mapValue = $extraDisplay
        ? "\$item->$searchField1 . ' - ' . \$item->$extraDisplay"
        : "\$item->$searchField1 . ' - ' . \$item->$searchField2";
    return <<<EOD
                        Forms\Components\Select::make('$fieldName')
                            ->label('$label')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string \$search) {
                                \$results = $model::where('$searchField1', 'like', "%{\$search}%")
                                    ->orWhere('$searchField2', 'like', "%{\$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn (\$item) => [\$item->$searchField1 => $mapValue])
                                    ->toArray();
                                if (\$search && !isset(\$results[\$search])) {
                                    \$results = [\$search => '✎ Pakai: ' . \$search] + \$results;
                                }
                                return \$results;
                            })
                            ->getOptionLabelUsing(fn (\$value): string => \$value)
                            ->live()
                            ->afterStateUpdated(function (\$state, Forms\Set \$set) {
                                if (\$state) {
                                    \$ref = $model::where('$searchField1', \$state)->first();
                                    if (\$ref) {
                                        \$set('$setField', \$ref->$fillField);
                                    }
                                }
                            })
                            ->disabled($disabled)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
EOD;
}

// Template: Catatan Pinggir (single fill, visible condition)
function buildCatatanPinggirSelect(string $fieldName, string $label, string $setField, string $disabled, string $kodeConst): string {
    return <<<EOD
                        Forms\Components\Select::make('$fieldName')
                            ->label('$label')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string \$search) {
                                \$results = \App\Models\AktaKelahiran::where('nomor', 'like', "%{\$search}%")
                                    ->orWhere('nama', 'like', "%{\$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn (\$item) => [\$item->nomor => \$item->nomor . ' - ' . \$item->nama])
                                    ->toArray();
                                if (\$search && !isset(\$results[\$search])) {
                                    \$results = [\$search => '✎ Pakai: ' . \$search] + \$results;
                                }
                                return \$results;
                            })
                            ->getOptionLabelUsing(fn (\$value): string => \$value)
                            ->live()
                            ->afterStateUpdated(function (\$state, Forms\Set \$set) {
                                if (\$state) {
                                    \$ref = \App\Models\AktaKelahiran::where('nomor', \$state)->first();
                                    if (\$ref) {
                                        \$set('$setField', \$ref->nama);
                                    }
                                }
                            })
                            ->disabled($disabled)
                            ->visible(fn(Forms\Get \$get) => \$get('kode') == CatatanPinggir::$kodeConst)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
EOD;
}

// ============================================================
// OLD patterns to search for (the createOptionForm style)
// ============================================================

$oldPatternAktaKelahiran = <<<'EOD'
                        Forms\Components\Select::make('no_akta')
                            ->label('No Akta')
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
                                        $set('nama', $record->nama);
                                        $set('kecamatan_id', $record->kecamatan_id);
                                        $set('desa_id', $record->desa_id);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Cari berdasar Nomor/Nama, atau klik tombol + untuk ketik manual jika data lama tidak ada.'),
EOD;

$newPatternAktaKelahiran = <<<'EOD'
                        Forms\Components\Select::make('no_akta')
                            ->label('No Akta')
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
                                    $results = [$search => '✎ Pakai: ' . $search] + $results;
                                }
                                return $results;
                            })
                            ->getOptionLabelUsing(fn ($value): string => $value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $ref = \App\Models\AktaKelahiran::where('nomor', $state)->first();
                                    if ($ref) {
                                        $set('nama', $ref->nama);
                                        $set('kecamatan_id', $ref->kecamatan_id);
                                        $set('desa_id', $ref->desa_id);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
EOD;

// KutipanDuaAktaKelahiran & KutipanDuaAktaKematian share same pattern
safeReplace(__DIR__ . '/app/Filament/Resources/KutipanDuaAktaKelahiranResource.php', trim($oldPatternAktaKelahiran), trim($newPatternAktaKelahiran));

$oldPatternAktaKematian = str_replace('AktaKelahiran', 'AktaKematian', $oldPatternAktaKelahiran);
$newPatternAktaKematian = str_replace('AktaKelahiran', 'AktaKematian', $newPatternAktaKelahiran);
safeReplace(__DIR__ . '/app/Filament/Resources/KutipanDuaAktaKematianResource.php', trim($oldPatternAktaKematian), trim($newPatternAktaKematian));

// KutipanDuaAktaPerkawinan
$oldPerkawinan = <<<'EOD'
                        Forms\Components\Select::make('no_akta')
                            ->label('No Akta')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => 
                                \App\Models\AktaPerkawinan::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama_mempelai_laki', 'like', "%{$search}%")
                                    ->orWhere('nama_mempelai_perempuan', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama_mempelai_laki . ' & ' . $item->nama_mempelai_perempuan])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => 
                                \App\Models\AktaPerkawinan::where('nomor', $value)->first()
                                    ? \App\Models\AktaPerkawinan::where('nomor', $value)->first()->nomor
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
                                    $record = \App\Models\AktaPerkawinan::where('nomor', $state)->first();
                                    if ($record) {
                                        $set('nama_suami', $record->nama_mempelai_laki);
                                        $set('nama_istri', $record->nama_mempelai_perempuan);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Cari berdasar Nomor/Nama, atau klik tombol + untuk ketik manual jika data lama tidak ada.'),
EOD;

$newPerkawinan = <<<'EOD'
                        Forms\Components\Select::make('no_akta')
                            ->label('No Akta')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $results = \App\Models\AktaPerkawinan::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama_mempelai_laki', 'like', "%{$search}%")
                                    ->orWhere('nama_mempelai_perempuan', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama_mempelai_laki . ' & ' . $item->nama_mempelai_perempuan])
                                    ->toArray();
                                if ($search && !isset($results[$search])) {
                                    $results = [$search => '✎ Pakai: ' . $search] + $results;
                                }
                                return $results;
                            })
                            ->getOptionLabelUsing(fn ($value): string => $value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $ref = \App\Models\AktaPerkawinan::where('nomor', $state)->first();
                                    if ($ref) {
                                        $set('nama_suami', $ref->nama_mempelai_laki);
                                        $set('nama_istri', $ref->nama_mempelai_perempuan);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
EOD;
safeReplace(__DIR__ . '/app/Filament/Resources/KutipanDuaAktaPerkawinanResource.php', trim($oldPerkawinan), trim($newPerkawinan));

// KutipanDuaAktaPerceraian
$oldPerceraian = <<<'EOD'
                        Forms\Components\Select::make('nomor_akta')
                            ->label('Nomor Akta')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => 
                                \App\Models\AktaPerceraian::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama_suami', 'like', "%{$search}%")
                                    ->orWhere('nama_istri', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama_suami . ' & ' . $item->nama_istri])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => 
                                \App\Models\AktaPerceraian::where('nomor', $value)->first()
                                    ? \App\Models\AktaPerceraian::where('nomor', $value)->first()->nomor
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
                                    $record = \App\Models\AktaPerceraian::where('nomor', $state)->first();
                                    if ($record) {
                                        $set('nama_suami', $record->nama_suami);
                                        $set('nama_istri', $record->nama_istri);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Cari berdasar Nomor/Nama, atau klik tombol + untuk ketik manual jika data lama tidak ada.'),
EOD;

$newPerceraian = <<<'EOD'
                        Forms\Components\Select::make('nomor_akta')
                            ->label('Nomor Akta')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $results = \App\Models\AktaPerceraian::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama_suami', 'like', "%{$search}%")
                                    ->orWhere('nama_istri', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama_suami . ' & ' . $item->nama_istri])
                                    ->toArray();
                                if ($search && !isset($results[$search])) {
                                    $results = [$search => '✎ Pakai: ' . $search] + $results;
                                }
                                return $results;
                            })
                            ->getOptionLabelUsing(fn ($value): string => $value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $ref = \App\Models\AktaPerceraian::where('nomor', $state)->first();
                                    if ($ref) {
                                        $set('nama_suami', $ref->nama_suami);
                                        $set('nama_istri', $ref->nama_istri);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
EOD;
safeReplace(__DIR__ . '/app/Filament/Resources/KutipanDuaAktaPerceraianResource.php', trim($oldPerceraian), trim($newPerceraian));

// ============================================================
// Catatan Pinggir - semua 4 field (PRB, PGSH, PGN, PGK)
// ============================================================
$cpFile = __DIR__ . '/app/Filament/Resources/CatatanPinggirResource.php';
$cpFields = [
    ['nomor_akta_prb', 'Nomor Akta', 'nama_sebelum', 'KODE_PRB'],
    ['nomor_akta_pgsh', 'Nomor Akta', 'nama_anak_pgsh', 'KODE_PGSH'],
    ['nomor_akta_pgn', 'Nomor Akta', 'nama_anak_pgn', 'KODE_PGN'],
    ['nomor_akta_pgk', 'Nomor Akta', 'nama_anak_pgk', 'KODE_PGK'],
];

foreach ($cpFields as [$fieldName, $label, $setField, $kodeConst]) {
    $old = <<<EOD
                        Forms\Components\Select::make('$fieldName')
                            ->label('$label')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string \$search): array => 
                                \App\Models\AktaKelahiran::where('nomor', 'like', "%{\$search}%")
                                    ->orWhere('nama', 'like', "%{\$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn (\$item) => [\$item->nomor => \$item->nomor . ' - ' . \$item->nama])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn (\$value): ?string => 
                                \App\Models\AktaKelahiran::where('nomor', \$value)->first()
                                    ? \App\Models\AktaKelahiran::where('nomor', \$value)->first()->nomor
                                    : \$value
                            )
                            ->createOptionForm([
                                Forms\Components\TextInput::make('no_akta_manual')
                                    ->label('Input No Akta Manual')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array \$data, Forms\Set \$set) {
                                return \$data['no_akta_manual'];
                            })
                            ->live()
                            ->afterStateUpdated(function (\$state, Forms\Set \$set) {
                                if (\$state) {
                                    \$record = \App\Models\AktaKelahiran::where('nomor', \$state)->first();
                                    if (\$record) {
                                        \$set('$setField', \$record->nama);
                                    }
                                }
                            })
                            ->disabled(fn(\$record) => \$record && !\$isFrontOffice && !\$isAdmin && !\$isOperator)
                            ->visible(fn(Forms\Get \$get) => \$get('kode') == CatatanPinggir::$kodeConst)
                            ->helperText('Cari berdasar Nomor/Nama, atau klik tombol + untuk ketik manual.'),
EOD;

    $new = <<<EOD
                        Forms\Components\Select::make('$fieldName')
                            ->label('$label')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string \$search) {
                                \$results = \App\Models\AktaKelahiran::where('nomor', 'like', "%{\$search}%")
                                    ->orWhere('nama', 'like', "%{\$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn (\$item) => [\$item->nomor => \$item->nomor . ' - ' . \$item->nama])
                                    ->toArray();
                                if (\$search && !isset(\$results[\$search])) {
                                    \$results = [\$search => '✎ Pakai: ' . \$search] + \$results;
                                }
                                return \$results;
                            })
                            ->getOptionLabelUsing(fn (\$value): string => \$value)
                            ->live()
                            ->afterStateUpdated(function (\$state, Forms\Set \$set) {
                                if (\$state) {
                                    \$ref = \App\Models\AktaKelahiran::where('nomor', \$state)->first();
                                    if (\$ref) {
                                        \$set('$setField', \$ref->nama);
                                    }
                                }
                            })
                            ->disabled(fn(\$record) => \$record && !\$isFrontOffice && !\$isAdmin && !\$isOperator)
                            ->visible(fn(Forms\Get \$get) => \$get('kode') == CatatanPinggir::$kodeConst)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
EOD;
    safeReplace($cpFile, trim($old), trim($new));
}

// ============================================================
// KartuKeluargaResource & PindahDatangResource - no_kk -> KartuKeluarga
// ============================================================
$oldKK = <<<'EOD'
                        Forms\Components\Select::make('no_kk')
                            ->label('No KK')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => 
                                \App\Models\KartuKeluarga::where('no_kk', 'like', "%{$search}%")
                                    ->orWhere('nama_kepala_keluarga', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->no_kk => $item->no_kk . ' - ' . $item->nama_kepala_keluarga])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => 
                                \App\Models\KartuKeluarga::where('no_kk', $value)->first()
                                    ? \App\Models\KartuKeluarga::where('no_kk', $value)->first()->no_kk
                                    : $value
                            )
                            ->createOptionForm([
                                Forms\Components\TextInput::make('no_kk_manual')
                                    ->label('Input No KK Manual')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data, Forms\Set $set) {
                                return $data['no_kk_manual'];
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $record = \App\Models\KartuKeluarga::where('no_kk', $state)->first();
                                    if ($record) {
                                        $set('nama_kepala_keluarga', $record->nama_kepala_keluarga);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->helperText('Cari berdasar No KK/Nama KK, atau klik tombol + untuk ketik manual.'),
EOD;

$newKK = <<<'EOD'
                        Forms\Components\Select::make('no_kk')
                            ->label('No KK')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $results = \App\Models\KartuKeluarga::where('no_kk', 'like', "%{$search}%")
                                    ->orWhere('nama_kepala_keluarga', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->no_kk => $item->no_kk . ' - ' . $item->nama_kepala_keluarga])
                                    ->toArray();
                                if ($search && !isset($results[$search])) {
                                    $results = [$search => '✎ Pakai: ' . $search] + $results;
                                }
                                return $results;
                            })
                            ->getOptionLabelUsing(fn ($value): string => $value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $ref = \App\Models\KartuKeluarga::where('no_kk', $state)->first();
                                    if ($ref) {
                                        $set('nama_kepala_keluarga', $ref->nama_kepala_keluarga);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->helperText('Ketik untuk mencari data lama, atau langsung ketik nomor baru jika belum ada.'),
EOD;

safeReplace(__DIR__ . '/app/Filament/Resources/KartuKeluargaResource.php', trim($oldKK), trim($newKK));

// PindahDatang uses afterStateUpdated with $ref variable name
$oldKKPindah = str_replace(
    '$record = \App\Models\KartuKeluarga::where(\'no_kk\', $state)->first();
                                    if ($record) {
                                        $set(\'nama_kepala_keluarga\', $record->nama_kepala_keluarga);',
    '$ref = \App\Models\KartuKeluarga::where(\'no_kk\', $state)->first();
                                    if ($ref) {
                                        $set(\'nama_kepala_keluarga\', $ref->nama_kepala_keluarga);',
    $oldKK
);
safeReplace(__DIR__ . '/app/Filament/Resources/PindahDatangResource.php', trim($oldKKPindah), trim($newKK));

// ============================================================
// KtpElResource & KiaResource - nik
// ============================================================
$oldNIKKtp = <<<'EOD'
                        Forms\Components\Select::make('nik')
                            ->label('NIK')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => 
                                \App\Models\KtpEl::where('nik', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nik => $item->nik . ' - ' . $item->nama])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => 
                                \App\Models\KtpEl::where('nik', $value)->first()
                                    ? \App\Models\KtpEl::where('nik', $value)->first()->nik
                                    : $value
                            )
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nik_manual')
                                    ->label('Input NIK Manual')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data, Forms\Set $set) {
                                return $data['nik_manual'];
                            })
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
                            ->helperText('Cari berdasar NIK/Nama, atau klik tombol + untuk ketik manual.'),
EOD;

$newNIKKtp = <<<'EOD'
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
                                    $results = [$search => '✎ Pakai: ' . $search] + $results;
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
EOD;
safeReplace(__DIR__ . '/app/Filament/Resources/KtpElResource.php', trim($oldNIKKtp), trim($newNIKKtp));

$oldNIKKia = str_replace('\App\Models\KtpEl', '\App\Models\Kia', $oldNIKKtp);
$newNIKKia = str_replace(['\App\Models\KtpEl', "Cari berdasar NIK/Nama, atau klik tombol + untuk ketik manual."], ['\App\Models\Kia', "Ketik untuk mencari data lama, atau langsung ketik NIK baru jika belum ada."], $newNIKKtp);
safeReplace(__DIR__ . '/app/Filament/Resources/KiaResource.php', trim($oldNIKKia), trim($newNIKKia));

echo "\n=== Verifying syntax for all changed files ===\n";
$files = [
    'app/Filament/Resources/KutipanDuaAktaKelahiranResource.php',
    'app/Filament/Resources/KutipanDuaAktaKematianResource.php',
    'app/Filament/Resources/KutipanDuaAktaPerkawinanResource.php',
    'app/Filament/Resources/KutipanDuaAktaPerceraianResource.php',
    'app/Filament/Resources/CatatanPinggirResource.php',
    'app/Filament/Resources/KartuKeluargaResource.php',
    'app/Filament/Resources/PindahDatangResource.php',
    'app/Filament/Resources/KtpElResource.php',
    'app/Filament/Resources/KiaResource.php',
];
foreach ($files as $f) {
    $out = trim(shell_exec("php -l $f 2>&1"));
    $status = strpos($out, 'No syntax errors') !== false ? 'OK ✓' : 'ERROR ✗';
    echo "$status: $f\n";
    if ($status === 'ERROR ✗') echo "  → $out\n";
}
