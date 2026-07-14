<?php
/**
 * Script to safely add autofill Select for no_kk and nik fields
 * in Pendaftaran Penduduk resources.
 * Uses exact string match with flexible line ending handling.
 */

function safeReplace(string $file, string $searchPattern, string $replacement): bool {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        return false;
    }
    $content = file_get_contents($file);
    // Normalize line endings for search matching
    $normalized = str_replace("\r\n", "\n", $content);
    $normalizedSearch = str_replace("\r\n", "\n", $searchPattern);
    
    if (strpos($normalized, $normalizedSearch) === false) {
        echo "Pattern not found in: $file\n";
        return false;
    }
    
    $result = str_replace($normalizedSearch, $replacement, $normalized);
    // Restore CRLF if original used it
    if (strpos($content, "\r\n") !== false) {
        $result = str_replace("\n", "\r\n", $result);
    }
    file_put_contents($file, $result);
    echo "Updated: $file\n";
    return true;
}

// =====================================================
// 1. KartuKeluargaResource - no_kk (already done, skip)
// =====================================================

// =====================================================
// 2. PindahDatangResource - no_kk -> KartuKeluarga
// =====================================================
$search = <<<'EOD'
                        Forms\Components\TextInput::make('no_kk')
                            ->label('No KK')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
EOD;
$replace = <<<'EOD'
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
                                    $ref = \App\Models\KartuKeluarga::where('no_kk', $state)->first();
                                    if ($ref) {
                                        $set('nama_kepala_keluarga', $ref->nama_kepala_keluarga);
                                    }
                                }
                            })
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator)
                            ->helperText('Cari berdasar No KK/Nama KK, atau klik tombol + untuk ketik manual.'),
EOD;
safeReplace(__DIR__ . '/app/Filament/Resources/PindahDatangResource.php', trim($search), trim($replace));

// =====================================================
// 3. KtpElResource - nik -> KtpEl
// =====================================================
$search = <<<'EOD'
                        Forms\Components\TextInput::make('nik')
                            ->label('NIK')
                            ->required()
                            ->maxLength(16)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin && !$isOperator),
EOD;
$replace = <<<'EOD'
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
safeReplace(__DIR__ . '/app/Filament/Resources/KtpElResource.php', trim($search), trim($replace));

// =====================================================
// 4. KiaResource - nik -> Kia
// =====================================================
safeReplace(__DIR__ . '/app/Filament/Resources/KiaResource.php', trim($search), str_replace(
    ['\App\Models\KtpEl', "->label('Input NIK Manual')", "->helperText('Cari berdasar NIK/Nama, atau klik tombol + untuk ketik manual.')"],
    ['\App\Models\Kia', "->label('Input NIK Manual')", "->helperText('Cari berdasar NIK/Nama (KIA lama), atau klik tombol + untuk ketik manual.')"],
    trim($replace)
));

echo "\nDone! Verifying syntax...\n";
$files = [
    'app/Filament/Resources/KartuKeluargaResource.php',
    'app/Filament/Resources/PindahDatangResource.php',
    'app/Filament/Resources/KtpElResource.php',
    'app/Filament/Resources/KiaResource.php',
];
foreach ($files as $f) {
    $out = shell_exec("php -l $f 2>&1");
    echo "$f: " . trim($out) . "\n";
}
