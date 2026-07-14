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

// 1. KutipanDuaAktaKelahiran
$search = <<<'EOD'
                        Forms\Components\TextInput::make('no_akta')
                            ->label('No Akta')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Nomor akta sebelumnya'),
EOD;
$replace = <<<'EOD'
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
replaceField(__DIR__ . '/app/Filament/Resources/KutipanDuaAktaKelahiranResource.php', trim($search), trim($replace));

// 2. KutipanDuaAktaKematian
$search = <<<'EOD'
                        Forms\Components\TextInput::make('no_akta')
                            ->label('No Akta')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Nomor akta sebelumnya'),
EOD;
$replace = <<<'EOD'
                        Forms\Components\Select::make('no_akta')
                            ->label('No Akta')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => 
                                \App\Models\AktaKematian::where('nomor', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [$item->nomor => $item->nomor . ' - ' . $item->nama])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => 
                                \App\Models\AktaKematian::where('nomor', $value)->first()
                                    ? \App\Models\AktaKematian::where('nomor', $value)->first()->nomor
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
                                    $record = \App\Models\AktaKematian::where('nomor', $state)->first();
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
replaceField(__DIR__ . '/app/Filament/Resources/KutipanDuaAktaKematianResource.php', trim($search), trim($replace));

// 3. KutipanDuaAktaPerkawinan
$search = <<<'EOD'
                        Forms\Components\TextInput::make('no_akta')
                            ->label('No Akta')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Nomor akta sebelumnya'),
EOD;
$replace = <<<'EOD'
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
replaceField(__DIR__ . '/app/Filament/Resources/KutipanDuaAktaPerkawinanResource.php', trim($search), trim($replace));

// 4. KutipanDuaAktaPerceraian
$search = <<<'EOD'
                        Forms\Components\TextInput::make('nomor_akta')
                            ->label('No Akta')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($record) => $record && !$isFrontOffice && !$isAdmin)
                            ->helperText('Nomor akta sebelumnya'),
EOD;
$replace = <<<'EOD'
                        Forms\Components\Select::make('nomor_akta')
                            ->label('No Akta')
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
replaceField(__DIR__ . '/app/Filament/Resources/KutipanDuaAktaPerceraianResource.php', trim($search), trim($replace));
