<?php

namespace App\Traits;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

trait HasServiceRequestLogs
{
    /**
     * Get all logs related to this service request.
     */
    public function logs(): HasManyThrough
    {
        return $this->hasManyThrough(
            ServiceRequestLog::class,
            ServiceRequest::class,
            'id', // Foreign key on service_requests table
            'service_request_id', // Foreign key on service_request_logs table
            'service_request_id', // Local key on this model table
            'id' // Local key on service_requests table
        );
    }
}
