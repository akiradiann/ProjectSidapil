<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AktaKematian extends Model
{
    protected $table = 'akta_kematian';

    protected $fillable = [
        'nomor',
        'kode',
        'nama',
        'jenis_kelamin',
        'tanggal_kematian',
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
        'tanggal_kematian' => 'date',
        'file_produk' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate nomor automatically: {urutan}/KM/{tahun}
     * and default kode: KM/{tahun}
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($akta) {
            $year = date('Y');
            if (empty($akta->kode)) {
                $akta->kode = 'KM/' . $year;
            }
        });

        static::created(function ($akta) {
            if (empty($akta->nomor)) {
                $year = date('Y');

                // Get the maximum urutan number for ALL records in the same year
                $existingAktas = static::whereYear('created_at', $year)
                    ->whereNotNull('nomor')
                    ->get();

                $maxUrutan = 0;
                foreach ($existingAktas as $existing) {
                    // Extract urutan from format: {urutan}/KM/{tahun}
                    if (preg_match('/^(\d+)\//', $existing->nomor, $matches)) {
                        $urutan = (int) $matches[1];
                        if ($urutan > $maxUrutan) {
                            $maxUrutan = $urutan;
                        }
                    }
                }

                $urutan = $maxUrutan + 1;
                $akta->nomor = $urutan . '/KM/' . $year;
                $akta->saveQuietly();
            }
        });


        static::updated(function ($akta) {
            // Ensure service request exists for legacy data
            $akta->ensureServiceRequestExists();

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
            // Delete related ServiceRequest when AktaKematian is deleted
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

    public function ensureServiceRequestExists(): ?ServiceRequest
    {
        if ($this->serviceRequest) {
            return $this->serviceRequest;
        }

        if (!$this->layanan_id) {
            return null;
        }

        $kategoriLayananId = KategoriLayanan::where('nama_kategori', 'AKTA KEMATIAN')->first()?->id ?? 2;

        $serviceRequest = ServiceRequest::create([
            'nomor_layanan' => null,
            'kategori_layanan_id' => $kategoriLayananId,
            'jenis_layanan_id' => $this->layanan_id,
            'jenis_produk_id' => $this->produk_id,
            'status_pelapor_id' => $this->status_pelapor_id,
            'status_ajuan_id' => $this->status_ajuan_id ?? StatusAjuan::DIPROSES,
            'fo_id' => auth()->id(),
            'file_produk' => $this->file_produk,
            'catatan' => $this->catatan,
        ]);

        $this->service_request_id = $serviceRequest->id;
        $this->saveQuietly();
        $this->setRelation('serviceRequest', $serviceRequest);

        return $serviceRequest;
    }
}


