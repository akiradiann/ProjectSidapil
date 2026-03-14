<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequestLog extends Model
{
    protected $table = 'service_request_logs';

    public $timestamps = false;

    protected $fillable = [
        'service_request_id',
        'status_ajuan_id',
        'user_id',
        'catatan',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function statusAjuan(): BelongsTo
    {
        return $this->belongsTo(StatusAjuan::class, 'status_ajuan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

