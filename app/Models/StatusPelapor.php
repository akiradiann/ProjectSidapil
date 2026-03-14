<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusPelapor extends Model
{
    protected $table = 'status_pelapor';
    
    public $timestamps = false;

    protected $fillable = [
        'nama_status',
    ];
}

