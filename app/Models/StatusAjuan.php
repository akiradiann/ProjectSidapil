<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusAjuan extends Model
{
    protected $table = 'status_ajuan';
    
    public $timestamps = false;

    protected $fillable = [
        'nama_status',
    ];

    // Status constants
    const DIPROSES = 1;
    const DITOLAK = 2;
    const SIAP_KIRIM = 3;
    const SIAP_DIAMBIL = 4;
    const SELESAI = 5;
}

