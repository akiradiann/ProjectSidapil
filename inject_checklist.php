<?php

$resources = [
    'AktaKelahiran' => [
        'Surat Keterangan Kelahiran',
        'Kartu Keluarga',
        'Buku Nikah/Akta Perkawinan Orang Tua',
        'KTP-el Orang Tua/Pelapor',
        'SPTJM, Jika diperlukan'
    ],
    'AktaKematian' => [
        'Surat Keterangan Kematian',
        'Kartu Keluarga Almarhum/Almarhumah',
        'KTP-el Almarhum/Almarhumah',
        'KTP-el Pelapor'
    ],
    'AktaPerkawinan' => [
        'Surat Keterangan Perkawinan dari Pemuka Agama',
        'Kartu Keluarga Kedua Mempelai',
        'KTP-el Kedua Mempelai',
        'Akta Kelahiran Kedua Mempelai',
        'Pasfoto Berdampingan Suami dan Istri'
    ],
    'AktaPerceraian' => [
        'Salinan Putusan Pengadilan yang Berkekuatan Hukum Tetap',
        'Salinan Akta Perkawinan',
        'Kartu Keluarga',
        'KTP-el Pemohon'
    ],
    'KutipanDuaAktaKelahiran' => [
        'Kartu Keluarga',
        'KTP-el Pemilik Akta/Pemohon',
        'Surat Kehilangan dari Kepolisian, jika hilang',
        'Fotokopi atau Foto Akta Lama, jika tersedia'
    ],
    'KutipanDuaAktaKematian' => [
        'Kartu Keluarga Pemohon/Keluarga Almarhum',
        'KTP-el Pemohon',
        'Surat Kehilangan dari Kepolisian, jika hilang',
        'Fotokopi atau Foto Akta Lama, jika tersedia'
    ],
    'KutipanDuaAktaPerkawinan' => [
        'Kartu Keluarga',
        'KTP-el Suami dan/atau Istri',
        'Surat Kehilangan dari Kepolisian, jika hilang',
        'Fotokopi atau Foto Akta Lama, jika tersedia',
        'Pasfoto Berdampingan Suami dan Istri, jika diperlukan'
    ],
    'KutipanDuaAktaPerceraian' => [
        'Kartu Keluarga',
        'KTP-el Pemohon',
        'Surat Kehilangan dari Kepolisian, jika hilang',
        'Fotokopi atau Foto Akta Lama, jika tersedia'
    ],
    'KartuKeluarga' => [
        'Kartu Keluarga Lama',
        'KTP-el Pemohon atau Kepala Keluarga',
        'Buku Nikah/Akta Perkawinan, jika terkait status perkawinan',
        'Akta Kelahiran, jika terdapat penambahan anggota keluarga',
        'Akta Kematian, jika terdapat pengurangan anggota keluarga',
        'Surat Keterangan Pindah, jika berasal dari daerah lain',
        'Dokumen Pendukung Perubahan Data, jika terdapat perubahan data'
    ],
    'PindahDatang' => [
        'Kartu Keluarga',
        'KTP-el Pemohon atau Anggota Keluarga yang Pindah',
        'Formulir Pendaftaran Perpindahan Penduduk',
        'Surat Keterangan Pindah atau Data Perpindahan dari Dukcapil Daerah Asal'
    ],
    'KtpEl' => [
        'Kartu Keluarga',
        'KTP-el Lama, jika mengajukan perubahan data atau penggantian',
        'Dokumen Pendukung Perubahan Data, jika terdapat perubahan data',
        'Surat Kehilangan dari Kepolisian, jika KTP-el hilang',
        'KTP-el yang Rusak, jika mengajukan penggantian karena rusak',
        'Perekaman Biometrik, untuk pemohon yang belum pernah melakukan perekaman'
    ],
    'Kia' => [
        'Kutipan Akta Kelahiran Anak',
        'Kartu Keluarga Orang Tua/Wali',
        'KTP-el Orang Tua/Wali',
        'Pasfoto Berwarna Anak, untuk anak usia 5 tahun ke atas'
    ]
];

foreach ($resources as $res => $options) {
    $file = __DIR__ . '/app/Filament/Resources/' . $res . 'Resource.php';
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    if (strpos($content, 'Checklist Persyaratan') !== false) {
        echo "Skipping $res (Already added)\n";
        continue;
    }
    
    $optString = '';
    foreach ($options as $opt) {
        $optString .= "                                    '" . addslashes($opt) . "' => '" . addslashes($opt) . "',\n";
    }
    
    $injection = "                Forms\Components\Section::make('Checklist Persyaratan')\n" .
        "                    ->description('Centang dokumen persyaratan yang sudah lengkap')\n" .
        "                    ->schema([\n" .
        "                        Forms\Components\CheckboxList::make('serviceRequest.checklist_persyaratan')\n" .
        "                            ->label('Persyaratan')\n" .
        "                            ->options([\n" .
        $optString .
        "                            ])\n" .
        "                            ->bulkToggleable()\n" .
        "                            ->columns(1)\n" .
        "                    ])\n" .
        "                    ->visible(fn (\$record) => \$isOperator && \$record !== null)\n" .
        "                    ->collapsible(),\n\n";
        
    $content = str_replace("Forms\Components\Section::make('Status & Produk')", $injection . "                Forms\Components\Section::make('Status & Produk')", $content);
    file_put_contents($file, $content);
    echo "Updated $res\n";
}
