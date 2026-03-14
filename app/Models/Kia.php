<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kia extends Model
{
    protected $table = 'kia';

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

        static::created(function ($kia) {
            if (empty($kia->nomor)) {
                $year = date('Y');

                // Get the maximum urutan number for same year
                // Extract urutan from existing nomor format: {urutan}/{tahun}
                $existingKia = static::whereYear('created_at', $year)
                    ->whereNotNull('nomor')
                    ->get();

                $maxUrutan = 0;
                foreach ($existingKia as $existing) {
                    if (preg_match('/^(\d+)\/' . $year . '$/', $existing->nomor, $matches)) {
                        $urutan = (int) $matches[1];
                        if ($urutan > $maxUrutan) {
                            $maxUrutan = $urutan;
                        }
                    }
                }

                $urutan = $maxUrutan + 1; // Next sequential number
                $kia->nomor = $urutan . '/' . $year;
                $kia->saveQuietly(); // Save without triggering events
            }
        });

        static::updated(function ($kia) {

            if (
                $kia->serviceRequest && (
                    $kia->wasChanged('status_ajuan_id') ||
                    $kia->wasChanged('catatan') ||
                    $kia->wasChanged('produk_id')
                )
            ) {
                $updateData = [
                    'status_ajuan_id' => $kia->status_ajuan_id,
                    'catatan' => $kia->catatan,
                    'jenis_produk_id' => $kia->produk_id,
                ];

                // Set operator_id if user is operator
                if (auth()->check() && auth()->user()->isOperator()) {
                    $updateData['operator_id'] = auth()->id();
                }

                // Set loket_id and selesai_at if status becomes SELESAI
                if ($kia->status_ajuan_id == StatusAjuan::SELESAI && auth()->check() && auth()->user()->isLoket()) {
                    $updateData['loket_id'] = auth()->id();
                    $updateData['selesai_at'] = now();
                }

                $kia->serviceRequest->update($updateData);
            }
        });

        static::deleting(function ($kia) {
            // Delete related ServiceRequest when Kia is deleted
            if ($kia->serviceRequest) {
                $kia->serviceRequest->delete();
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
