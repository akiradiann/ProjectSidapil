<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanPinggir extends Model
{
    protected $table = 'catatan_pinggir';

    protected $fillable = [
        'nomor',
        'kode',
        // PRB - Perubahan Nama
        'nomor_akta_prb',
        'nama_sebelum',
        'nama_sesudah',
        'no_penetapan_pengadilan_prb',
        'tanggal_penetapan_prb',
        // PGSH - Pengesahan
        'nomor_akta_pgsh',
        'nama_anak_pgsh',
        'nama_ibu_pgsh',
        'nama_ayah_pgsh',
        'dasar_pengesahan',
        // PGN - Pengangkatan Anak
        'nomor_akta_pgn',
        'nama_anak_pgn',
        'nama_ayah_kandung',
        'nama_ibu_kandung',
        'no_penetapan_pengadilan_pgn',
        'nama_ayah_angkat',
        'nama_ibu_angkat',
        // PGK - Pengakuan Anak
        'nomor_akta_pgk',
        'nama_anak_pgk',
        'nama_ibu_pgk',
        'nama_ayah_pgk',
        'dasar_pengakuan',
        // PKOI - Perubahan Kewarganegaraan
        'perubahan_kewarganegaraan',
        'nama_pkoi',
        'tanggal_lahir_pkoi',
        'jenis_kelamin_pkoi',
        'alamat_pkoi',
        'negara_asal',
        'negara_tujuan',
        'surat_dasar_keputusan',
        'tanggal_surat_keputusan',
        'alasan_perubahan',
        // Field default
        'nama_pelapor',
        'no_hp',
        'layanan_id',
        'status_pelapor_id',
        'produk_id',
        'status_ajuan_id',
        'file_produk',
        'catatan',
        'service_request_id',
    ];

    protected $casts = [
        'tanggal_penetapan_prb' => 'date',
        'tanggal_lahir_pkoi' => 'date',
        'tanggal_surat_keputusan' => 'date',
        'file_produk' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Kode options (tanpa tahun)
    const KODE_PRB = 'PRB';
    const KODE_PGSH = 'PGSH';
    const KODE_PGN = 'PGN';
    const KODE_PGK = 'PGK';
    const KODE_PKOI = 'PKOI';

    public static function getKodeOptions(): array
    {
        return [
            self::KODE_PRB => self::KODE_PRB,
            self::KODE_PGSH => self::KODE_PGSH,
            self::KODE_PGN => self::KODE_PGN,
            self::KODE_PGK => self::KODE_PGK,
            self::KODE_PKOI => self::KODE_PKOI,
        ];
    }

    /**
     * Get nama untuk display berdasarkan kode
     */
    public function getNamaAttribute(): ?string
    {
        return match ($this->kode) {
            self::KODE_PRB => $this->nama_sesudah,
            self::KODE_PGSH => $this->nama_anak_pgsh,
            self::KODE_PGN => $this->nama_anak_pgn,
            self::KODE_PGK => $this->nama_anak_pgk,
            self::KODE_PKOI => $this->nama_pkoi,
            default => null,
        };
    }

    /**
     * Generate nomor automatically: {urutan}/{kode}/{tahun}
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($catatan) {
            if (empty($catatan->nomor)) {
                $year = date('Y');
                $kode = $catatan->kode;

                // Get the maximum urutan number for ALL kode in the same year
                $existingCatatan = static::whereYear('created_at', $year)
                    ->whereNotNull('nomor')
                    ->get();

                $maxUrutan = 0;
                foreach ($existingCatatan as $existing) {
                    if (preg_match('/^(\d+)\//', $existing->nomor, $matches)) {
                        $urutan = (int) $matches[1];
                        if ($urutan > $maxUrutan) {
                            $maxUrutan = $urutan;
                        }
                    }
                }

                $urutan = $maxUrutan + 1;
                $catatan->nomor = $urutan . '/' . $kode . '/' . $year;
                $catatan->saveQuietly();
            }
        });

        static::updated(function ($catatan) {
            // Sync with service request if any relevant field changes
            if (
                $catatan->serviceRequest && (
                    $catatan->wasChanged('status_ajuan_id') ||
                    $catatan->wasChanged('file_produk') ||
                    $catatan->wasChanged('catatan') ||
                    $catatan->wasChanged('produk_id')
                )
            ) {
                $updateData = [
                    'status_ajuan_id' => $catatan->status_ajuan_id,
                    'file_produk' => $catatan->file_produk,
                    'catatan' => $catatan->catatan,
                    'jenis_produk_id' => $catatan->produk_id,
                ];

                // Set operator_id if user is operator
                if (auth()->check() && auth()->user()->isOperator()) {
                    $updateData['operator_id'] = auth()->id();
                }

                // Set loket_id and selesai_at if status becomes SELESAI
                if ($catatan->status_ajuan_id == StatusAjuan::SELESAI && auth()->check() && auth()->user()->isLoket()) {
                    $updateData['loket_id'] = auth()->id();
                    $updateData['selesai_at'] = now();
                }

                $catatan->serviceRequest->update($updateData);
            }
        });

        static::deleting(function ($catatan) {
            // Delete related ServiceRequest when CatatanPinggir is deleted
            if ($catatan->serviceRequest) {
                $catatan->serviceRequest->delete();
            }
        });
    }

    // Relationships
    public function jenisLayanan()
    {
        return $this->belongsTo(JenisLayanan::class, 'layanan_id');
    }

    public function statusPelapor()
    {
        return $this->belongsTo(StatusPelapor::class, 'status_pelapor_id');
    }

    public function jenisProduk()
    {
        return $this->belongsTo(JenisProduk::class, 'produk_id');
    }

    public function statusAjuan()
    {
        return $this->belongsTo(StatusAjuan::class, 'status_ajuan_id');
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }
}





