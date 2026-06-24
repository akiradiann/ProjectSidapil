<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AktaKelahiran extends Model
{
    use \App\Traits\HasServiceRequestLogs;
    protected $table = 'akta_kelahiran';

    protected $fillable = [
        'nomor',
        'kode',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'kecamatan_id',
        'desa_id',
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
        'tanggal_lahir' => 'date',
        'file_produk' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Kode options (tanpa tahun)
    const KODE_R = 'R';
    const KODE_R_LN = 'R/LN';
    const KODE_R_SPTJM = 'R/SPTJM';
    const KODE_TP = 'TP';
    const KODE_TP_LN = 'TP/LN';
    const KODE_TP_SPTJM = 'TP/SPTJM';

    public static function getKodeOptions(): array
    {
        return [
            self::KODE_R => self::KODE_R,
            self::KODE_R_LN => self::KODE_R_LN,
            self::KODE_R_SPTJM => self::KODE_R_SPTJM,
            self::KODE_TP => self::KODE_TP,
            self::KODE_TP_LN => self::KODE_TP_LN,
            self::KODE_TP_SPTJM => self::KODE_TP_SPTJM,
        ];
    }

    /**
     * Generate nomor automatically: {urutan}/{kode}/{tahun}
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($akta) {
            if (empty($akta->nomor)) {
                $year = date('Y');
                $kode = $akta->kode;

                // Get the maximum urutan number for ALL kode in the same year
                // Urutan nomor berdasarkan inputan, tidak peduli kode apa
                $existingAktas = static::whereYear('created_at', $year)
                    ->whereNotNull('nomor')
                    ->get();

                $maxUrutan = 0;
                foreach ($existingAktas as $existing) {
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
                $akta->nomor = $urutan . '/' . $kode . '/' . $year;
                $akta->saveQuietly(); // Save without triggering events
            }
        });


        static::updated(function ($akta) {
            // Sync with service request if any relevant field changes
            if (
                $akta->serviceRequest && (
                    $akta->wasChanged('status_ajuan_id') ||
                    $akta->wasChanged('file_produk') ||
                    $akta->wasChanged('catatan') ||
                    $akta->wasChanged('produk_id') ||
                    $akta->wasChanged('no_hp') ||
                    $akta->wasChanged('nama_pelapor')
                )
            ) {
                $updateData = [
                    'status_ajuan_id' => $akta->status_ajuan_id,
                    'file_produk' => $akta->file_produk,
                    'catatan' => $akta->catatan,
                    'jenis_produk_id' => $akta->produk_id,
                    'no_hp' => $akta->no_hp,
                    'nama_pemohon' => $akta->nama_pelapor,
                ];

                // Set operator_id if user is operator
                if (auth()->check() && auth()->user()->isOperator()) {
                    $updateData['operator_id'] = auth()->id();
                }

                // Set loket_id and selesai_at if status becomes SELESAI
                if ($akta->status_ajuan_id == StatusAjuan::SELESAI && auth()->check() && auth()->user()->isLoket()) {
                    $updateData['loket_id'] = auth()->id();
                    $updateData['selesai_at'] = now();
                }

                $akta->serviceRequest->update($updateData);
            }
        });

        static::deleting(function ($akta) {
            // Delete related ServiceRequest when AktaKelahiran is deleted
            if ($akta->serviceRequest) {
                $akta->serviceRequest->delete();
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

    // Accessors for API data
    public function getKecamatanNameAttribute(): ?string
    {
        if (!$this->kecamatan_id)
            return null;
        $district = \App\Services\WilayahService::getDistrict($this->kecamatan_id);
        return $district ? $district['name'] : $this->kecamatan_id;
    }

    public function getDesaNameAttribute(): ?string
    {
        if (!$this->desa_id || !$this->kecamatan_id)
            return null;
        $village = \App\Services\WilayahService::getVillage($this->kecamatan_id, $this->desa_id);
        return $village ? $village['name'] : $this->desa_id;
    }
}

