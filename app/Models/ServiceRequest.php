<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ServiceRequest extends Model
{
    protected $table = 'service_requests';

    protected $fillable = [
        'nomor_layanan',
        'kategori_layanan_id',
        'jenis_layanan_id',
        'jenis_produk_id',
        'status_pelapor_id',
        'status_ajuan_id',
        'fo_id',
        'operator_id',
        'cs_id',
        'loket_id',
        'no_hp',
        'nama_pemohon',
        'file_produk',
        'catatan',
        'catatan_tambahan',
        'selesai_at',
    ];

    protected $casts = [
        'file_produk' => 'array',
        'selesai_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate nomor layanan automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($serviceRequest) {
            if (empty($serviceRequest->nomor_layanan)) {
                $year = date('Y');
                $lastRequest = static::whereYear('created_at', $year)
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = $lastRequest ? ((int) substr($lastRequest->nomor_layanan, -4)) + 1 : 1;
                $serviceRequest->nomor_layanan = 'SRV/' . $year . '/' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }

            // Set default status to DIPROSES if not set
            if (empty($serviceRequest->status_ajuan_id)) {
                $serviceRequest->status_ajuan_id = StatusAjuan::DIPROSES;
            }

            // Set FO ID if user is front office
            if (auth()->check() && auth()->user()->isFrontOffice()) {
                $serviceRequest->fo_id = auth()->id();
            }

            // Set selesai_at if status is already SELESAI on creation
            if ($serviceRequest->status_ajuan_id == StatusAjuan::SELESAI) {
                $serviceRequest->selesai_at = now();
            }
        });

        static::created(function ($serviceRequest) {
            // Log status change on creation
            if (auth()->check()) {
                ServiceRequestLog::create([
                    'service_request_id' => $serviceRequest->id,
                    'status_ajuan_id' => $serviceRequest->status_ajuan_id,
                    'user_id' => auth()->id(),
                    'catatan' => $serviceRequest->catatan,
                ]);
            }

            // WhatsApp notification logic has been temporarily removed
        });

        static::updating(function ($serviceRequest) {
            // Track status changes
            if ($serviceRequest->isDirty('status_ajuan_id')) {
                $oldStatus = $serviceRequest->getOriginal('status_ajuan_id');
                $newStatus = $serviceRequest->status_ajuan_id;

                // Set operator_id if user is operator
                if (auth()->check() && auth()->user()->isOperator()) {
                    $serviceRequest->operator_id = auth()->id();
                }

                // Set selesai_at when status becomes SELESAI
                if ($newStatus == StatusAjuan::SELESAI) {
                    $serviceRequest->selesai_at = now();
                }
            }
        });

        static::updated(function ($serviceRequest) {
            // Log status change after update
            if ($serviceRequest->wasChanged('status_ajuan_id')) {
                if (auth()->check()) {
                    ServiceRequestLog::create([
                        'service_request_id' => $serviceRequest->id,
                        'status_ajuan_id' => $serviceRequest->status_ajuan_id,
                        'user_id' => auth()->id(),
                        'catatan' => $serviceRequest->catatan,
                    ]);
                }

                // WhatsApp notification logic has been temporarily removed
            }
        });

        static::deleting(function ($serviceRequest) {
            // Delete related logs
            $serviceRequest->logs()->delete();

            // Delete file_produk if exists (handle both single file and multiple files)
            if ($serviceRequest->file_produk) {
                $files = is_array($serviceRequest->file_produk)
                    ? $serviceRequest->file_produk
                    : (is_string($serviceRequest->file_produk) ? json_decode($serviceRequest->file_produk, true) ?? [$serviceRequest->file_produk] : []);

                foreach ($files as $file) {
                    if ($file && Storage::disk('local')->exists($file)) {
                        Storage::disk('local')->delete($file);
                    }
                }
            }
        });
    }

    // Relationships
    public function kategoriLayanan(): BelongsTo
    {
        return $this->belongsTo(KategoriLayanan::class, 'kategori_layanan_id');
    }

    public function jenisLayanan(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class, 'jenis_layanan_id');
    }

    public function jenisProduk(): BelongsTo
    {
        return $this->belongsTo(JenisProduk::class, 'jenis_produk_id');
    }

    public function statusPelapor(): BelongsTo
    {
        return $this->belongsTo(StatusPelapor::class, 'status_pelapor_id');
    }

    public function statusAjuan(): BelongsTo
    {
        return $this->belongsTo(StatusAjuan::class, 'status_ajuan_id');
    }

    public function fo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fo_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function cs(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cs_id');
    }

    public function loket(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loket_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ServiceRequestLog::class, 'service_request_id');
    }

    public function aktaKelahiran()
    {
        return $this->hasOne(AktaKelahiran::class, 'service_request_id');
    }

    public function kutipanDuaAktaKelahiran()
    {
        return $this->hasOne(KutipanDuaAktaKelahiran::class, 'service_request_id');
    }

    public function aktaKematian()
    {
        return $this->hasOne(AktaKematian::class, 'service_request_id');
    }

    public function kartuKeluarga()
    {
        return $this->hasOne(KartuKeluarga::class, 'service_request_id');
    }

    public function aktaPerkawinan()
    {
        return $this->hasOne(AktaPerkawinan::class, 'service_request_id');
    }

    public function aktaPerceraian()
    {
        return $this->hasOne(AktaPerceraian::class, 'service_request_id');
    }

    public function pindahDatang()
    {
        return $this->hasOne(PindahDatang::class, 'service_request_id');
    }

    public function ktpEl()
    {
        return $this->hasOne(KtpEl::class, 'service_request_id');
    }

    public function kia()
    {
        return $this->hasOne(Kia::class, 'service_request_id');
    }

    public function kutipanDuaAktaKematian()
    {
        return $this->hasOne(KutipanDuaAktaKematian::class, 'service_request_id');
    }

    public function kutipanDuaAktaPerkawinan()
    {
        return $this->hasOne(KutipanDuaAktaPerkawinan::class, 'service_request_id');
    }

    public function kutipanDuaAktaPerceraian()
    {
        return $this->hasOne(KutipanDuaAktaPerceraian::class, 'service_request_id');
    }

    public function catatanPinggir()
    {
        return $this->hasOne(CatatanPinggir::class, 'service_request_id');
    }

    public function surat()
    {
        return $this->hasOne(Surat::class, 'service_request_id');
    }

    // Helper methods
    public function isReadyForDelivery(): bool
    {
        return $this->status_ajuan_id == StatusAjuan::SIAP_KIRIM &&
            $this->jenis_produk_id == 2; // FILE
    }

    public function isRejected(): bool
    {
        return $this->status_ajuan_id == StatusAjuan::DITOLAK;
    }

    public function isReadyForPickup(): bool
    {
        return $this->status_ajuan_id == StatusAjuan::SIAP_DIAMBIL;
    }

    public function isCompleted(): bool
    {
        return $this->status_ajuan_id == StatusAjuan::SELESAI;
    }
}

