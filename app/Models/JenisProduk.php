<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisProduk extends Model
{
    protected $table = 'jenis_produk';
    
    public $timestamps = false;

    protected $fillable = [
        'nama_produk',
    ];
}

