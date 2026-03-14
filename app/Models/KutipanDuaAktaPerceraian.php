<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KutipanDuaAktaPerceraian extends Model
{
    protected $table = 'kutipan_dua_akta_perceraian';

    protected $fillable = [
        'nomor',
        'kode',
        'nomor_akta',
        'nama_suami',
        'nama_istri',
        'alasan',
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
        'file_produk' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Kode options (tanpa tahun)
    const KODE_ACR_K2 = 'ACR/K2';
    const KODE_ACR_PB = 'ACR/PB';
    const KODE_ACR_BTL = 'ACR/BTL';

    public static function getKodeOptions(): array
    {
        return [
            self::KODE_ACR_K2 => self::KODE_ACR_K2,
            self::KODE_ACR_PB => self::KODE_ACR_PB,
            self::KODE_ACR_BTL => self::KODE_ACR_BTL,
        ];
    }

    /**
     * Generate nomor automatically: {urutan}/{kode}/{tahun}
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($kutipan) {
            if (empty($kutipan->nomor)) {
                $year = date('Y');
                $kode = $kutipan->kode;

                // Get the maximum urutan number for ALL kode in the same year
                // Urutan nomor berdasarkan inputan, tidak peduli kode apa
                $existingKutipan = static::whereYear('created_at', $year)
                    ->whereNotNull('nomor')
                    ->get();

                $maxUrutan = 0;
                foreach ($existingKutipan as $existing) {
                    // Extract urutan dari format: {urutan}/{kode}/{tahun}
                    // Pattern: ambil angka di awal sebelum slash pertama, karena kode bisa punya slash
                    if (preg_match('/^(\d+)\//', $existing->nomor, $matches)) {
                        $urutan = (int) $matches[1];
                        if ($urutan > $maxUrutan) {
                            $maxUrutan = $urutan;
                        }
                    }
                }

                $urutan = $maxUrutan + 1; // Next sequential number (global untuk semua kode)
                $kutipan->nomor = $urutan . '/' . $kode . '/' . $year;
                $kutipan->saveQuietly(); // Save without triggering events
            }
        });

        static::updated(function ($kutipan) {
            // Sync with service request if any relevant field changes
            if (
                $kutipan->serviceRequest && (
                    $kutipan->wasChanged('status_ajuan_id') ||
                    $kutipan->wasChanged('file_produk') ||
                    $kutipan->wasChanged('catatan') ||
                    $kutipan->wasChanged('produk_id')
                )
            ) {
                $updateData = [
                    'status_ajuan_id' => $kutipan->status_ajuan_id,
                    'file_produk' => $kutipan->file_produk,
                    'catatan' => $kutipan->catatan,
                    'jenis_produk_id' => $kutipan->produk_id,
                ];

                // Set operator_id if user is operator
                if (auth()->check() && auth()->user()->isOperator()) {
                    $updateData['operator_id'] = auth()->id();
                }

                // Set loket_id and selesai_at if status becomes SELESAI
                if ($kutipan->status_ajuan_id == StatusAjuan::SELESAI && auth()->check() && auth()->user()->isLoket()) {
                    $updateData['loket_id'] = auth()->id();
                    $updateData['selesai_at'] = now();
                }

                $kutipan->serviceRequest->update($updateData);
            }
        });

        static::deleting(function ($kutipan) {
            // Delete related ServiceRequest when KutipanDuaAktaPerceraian is deleted
            if ($kutipan->serviceRequest) {
                $kutipan->serviceRequest->delete();
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





