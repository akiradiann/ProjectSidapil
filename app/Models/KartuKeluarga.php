<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KartuKeluarga extends Model
{
    protected $table = 'kartu_keluarga';

    protected $fillable = [
        'nomor',
        'no_kk',
        'nama_kepala_keluarga',
        'nama_pemohon',
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

    /**
     * Generate nomor automatically: {urutan}/{tahun}
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($kk) {
            if (empty($kk->nomor)) {
                $year = date('Y');

                // Get the maximum urutan number for same year
                // Extract urutan from existing nomor format: {urutan}/{tahun}
                $existingKK = static::whereYear('created_at', $year)
                    ->whereNotNull('nomor')
                    ->get();

                $maxUrutan = 0;
                foreach ($existingKK as $existing) {
                    if (preg_match('/^(\d+)\/' . $year . '$/', $existing->nomor, $matches)) {
                        $urutan = (int) $matches[1];
                        if ($urutan > $maxUrutan) {
                            $maxUrutan = $urutan;
                        }
                    }
                }

                $urutan = $maxUrutan + 1; // Next sequential number
                $kk->nomor = $urutan . '/' . $year;
                $kk->saveQuietly(); // Save without triggering events
            }
        });

        static::updated(function ($kk) {
            // Sync with service request if any relevant field changes
            if (
                $kk->serviceRequest && (
                    $kk->wasChanged('status_ajuan_id') ||
                    $kk->wasChanged('catatan') ||
                    $kk->wasChanged('produk_id')
                )
            ) {
                $updateData = [
                    'status_ajuan_id' => $kk->status_ajuan_id,
                    'catatan' => $kk->catatan,
                    'jenis_produk_id' => $kk->produk_id,
                ];

                // Set operator_id if user is operator
                if (auth()->check() && auth()->user()->isOperator()) {
                    $updateData['operator_id'] = auth()->id();
                }

                // Set loket_id and selesai_at if status becomes SELESAI
                if ($kk->status_ajuan_id == StatusAjuan::SELESAI && auth()->check() && auth()->user()->isLoket()) {
                    $updateData['loket_id'] = auth()->id();
                    $updateData['selesai_at'] = now();
                }

                $kk->serviceRequest->update($updateData);
            }
        });

        static::deleting(function ($kk) {
            // Delete related ServiceRequest when KartuKeluarga is deleted
            if ($kk->serviceRequest) {
                $kk->serviceRequest->delete();
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
