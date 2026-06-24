<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PindahDatang extends Model
{
    use \App\Traits\HasServiceRequestLogs;
    protected $table = 'pindah_datang';

    protected $fillable = [
        'nomor',
        'ajuan',
        'no_kk',
        'nik',
        'nama_kepala_keluarga',
        'nama_pemohon',
        'no_hp',
        'layanan_id',
        'status_pelapor_id',
        'produk_id',
        'file_produk',
        'status_ajuan_id',
        'selesai_at',
        'catatan',
        'service_request_id',
    ];

    protected $casts = [
        'file_produk' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'selesai_at' => 'datetime',
    ];

    // Ajuan options
    const AJUAN_KEPINDAHAN = 'KEPINDAHAN';
    const AJUAN_KEDATANGAN = 'KEDATANGAN';

    public static function getAjuanOptions(): array
    {
        return [
            self::AJUAN_KEPINDAHAN => self::AJUAN_KEPINDAHAN,
            self::AJUAN_KEDATANGAN => self::AJUAN_KEDATANGAN,
        ];
    }

    /**
     * Generate nomor automatically: {urutan}/{tahun}
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($pd) {
            if (empty($pd->nomor)) {
                $year = date('Y');

                // Get the maximum urutan number for same year
                // Extract urutan from existing nomor format: {urutan}/{tahun}
                $existingPD = static::whereYear('created_at', $year)
                    ->whereNotNull('nomor')
                    ->get();

                $maxUrutan = 0;
                foreach ($existingPD as $existing) {
                    if (preg_match('/^(\d+)\/' . $year . '$/', $existing->nomor, $matches)) {
                        $urutan = (int) $matches[1];
                        if ($urutan > $maxUrutan) {
                            $maxUrutan = $urutan;
                        }
                    }
                }

                $urutan = $maxUrutan + 1; // Next sequential number
                $pd->nomor = $urutan . '/' . $year;
                $pd->saveQuietly(); // Save without triggering events
            }
        });

        static::updated(function ($pd) {

            if (
                $pd->serviceRequest && (
                    $pd->wasChanged('status_ajuan_id') ||
                    $pd->wasChanged('catatan') ||
                    $pd->wasChanged('produk_id') ||
                    $pd->wasChanged('no_hp') ||
                    $pd->wasChanged('nama_pemohon')
                )
            ) {
                $updateData = [
                    'status_ajuan_id' => $pd->status_ajuan_id,
                    'catatan' => $pd->catatan,
                    'jenis_produk_id' => $pd->produk_id,
                    'no_hp' => $pd->no_hp,
                    'nama_pemohon' => $pd->nama_pemohon,
                ];

                // Set operator_id if user is operator
                if (auth()->check() && auth()->user()->isOperator()) {
                    $updateData['operator_id'] = auth()->id();
                }

                // Set loket_id and selesai_at if status becomes SELESAI
                if ($pd->status_ajuan_id == StatusAjuan::SELESAI && auth()->check() && auth()->user()->isLoket()) {
                    $updateData['loket_id'] = auth()->id();
                    $updateData['selesai_at'] = now();
                }

                $pd->serviceRequest->update($updateData);
            }
        });

        static::deleting(function ($pd) {
            // Delete related ServiceRequest when PindahDatang is deleted
            if ($pd->serviceRequest) {
                $pd->serviceRequest->delete();
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
