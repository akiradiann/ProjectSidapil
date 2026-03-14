<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KtpEl extends Model
{
    protected $table = 'ktp_el';

    protected $fillable = [
        'nomor',
        'nik',
        'nama',
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

        static::created(function ($ktp) {
            if (empty($ktp->nomor)) {
                $year = date('Y');

                // Get the maximum urutan number for same year
                // Extract urutan from existing nomor format: {urutan}/{tahun}
                $existingKtp = static::whereYear('created_at', $year)
                    ->whereNotNull('nomor')
                    ->get();

                $maxUrutan = 0;
                foreach ($existingKtp as $existing) {
                    if (preg_match('/^(\d+)\/' . $year . '$/', $existing->nomor, $matches)) {
                        $urutan = (int) $matches[1];
                        if ($urutan > $maxUrutan) {
                            $maxUrutan = $urutan;
                        }
                    }
                }

                $urutan = $maxUrutan + 1; // Next sequential number
                $ktp->nomor = $urutan . '/' . $year;
                $ktp->saveQuietly(); // Save without triggering events
            }
        });

        static::updated(function ($ktp) {

            if (
                $ktp->serviceRequest && (
                    $ktp->wasChanged('status_ajuan_id') ||
                    $ktp->wasChanged('catatan') ||
                    $ktp->wasChanged('produk_id')
                )
            ) {
                $updateData = [
                    'status_ajuan_id' => $ktp->status_ajuan_id,
                    'catatan' => $ktp->catatan,
                    'jenis_produk_id' => $ktp->produk_id,
                ];

                // Set operator_id if user is operator
                if (auth()->check() && auth()->user()->isOperator()) {
                    $updateData['operator_id'] = auth()->id();
                }

                // Set loket_id and selesai_at if status becomes SELESAI
                if ($ktp->status_ajuan_id == StatusAjuan::SELESAI && auth()->check() && auth()->user()->isLoket()) {
                    $updateData['loket_id'] = auth()->id();
                    $updateData['selesai_at'] = now();
                }

                $ktp->serviceRequest->update($updateData);
            }
        });

        static::deleting(function ($ktp) {
            // Delete related ServiceRequest when KtpEl is deleted
            if ($ktp->serviceRequest) {
                $ktp->serviceRequest->delete();
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
