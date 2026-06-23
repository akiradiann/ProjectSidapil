<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Surat extends Model
{
    protected $table = 'surat';

    protected $fillable = [
        'nomor',
        'jenis',
        'nama',
        'no_akta',
        'tujuan',
        'nama_pemohon',
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

    /**
     * Generate nomor automatically: {urutan}/SURAT/{tahun}
     * Adjust format as needed.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($surat) {
            if (empty($surat->nomor) || $surat->nomor === 'Otomatis') {
                $year = date('Y');

                // Get the maximum urutan number
                $existing = static::whereYear('created_at', $year)
                    ->whereNotNull('nomor')
                    ->get();

                $maxUrutan = 0;
                foreach ($existing as $item) {
                    if (preg_match('/^(\d+)\//', $item->nomor, $matches)) {
                        $urutan = (int) $matches[1];
                        if ($urutan > $maxUrutan) {
                            $maxUrutan = $urutan;
                        }
                    }
                }

                $urutan = $maxUrutan + 1;
                $surat->nomor = $urutan . '/SURAT/' . $year;
                $surat->saveQuietly();
            }
        });

        static::updated(function ($surat) {
            // Sync with service request if any relevant field changes
            if (
                $surat->serviceRequest && (
                    $surat->wasChanged('status_ajuan_id') ||
                    $surat->wasChanged('file_produk') ||
                    $surat->wasChanged('catatan') ||
                    $surat->wasChanged('produk_id') ||
                    $surat->wasChanged('no_hp') ||
                    $surat->wasChanged('nama_pemohon')
                )
            ) {
                $updateData = [
                    'status_ajuan_id' => $surat->status_ajuan_id,
                    'file_produk' => $surat->file_produk,
                    'catatan' => $surat->catatan,
                    'jenis_produk_id' => $surat->produk_id,
                    'no_hp' => $surat->no_hp,
                    'nama_pemohon' => $surat->nama_pemohon,
                ];

                if (auth()->check() && auth()->user()->isOperator()) {
                    $updateData['operator_id'] = auth()->id();
                }

                // Set loket_id and selesai_at if status becomes SELESAI
                if ($surat->status_ajuan_id == StatusAjuan::SELESAI && auth()->check() && auth()->user()->isLoket()) {
                    $updateData['loket_id'] = auth()->id();
                    $updateData['selesai_at'] = now();
                }

                $surat->serviceRequest->update($updateData);
            }
        });

        static::deleting(function ($surat) {
            if ($surat->serviceRequest) {
                $surat->serviceRequest->delete();
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
