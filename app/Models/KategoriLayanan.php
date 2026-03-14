<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriLayanan extends Model
{
    protected $table = 'kategori_layanan';
    
    public $timestamps = false;

    protected $fillable = [
        'nama_kategori',
    ];
}

