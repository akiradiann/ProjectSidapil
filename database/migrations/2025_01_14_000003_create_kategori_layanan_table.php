<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kategori_layanan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori', 150);
        });

        // Insert default data seperti di SQL
        DB::table('kategori_layanan')->insert([
            ['id' => 1, 'nama_kategori' => 'AKTA KELAHIRAN'],
            ['id' => 2, 'nama_kategori' => 'AKTA KEMATIAN'],
            ['id' => 3, 'nama_kategori' => 'AKTA PERKAWINAN'],
            ['id' => 4, 'nama_kategori' => 'AKTA PERCERAIAN'],
            ['id' => 5, 'nama_kategori' => 'KUTIPAN DUA KELAHIRAN'],
            ['id' => 6, 'nama_kategori' => 'KUTIPAN DUA KEMATIAN'],
            ['id' => 7, 'nama_kategori' => 'KUTIPAN DUA PERKAWINAN'],
            ['id' => 8, 'nama_kategori' => 'KUTIPAN DUA PERCERAIAN'],
            ['id' => 9, 'nama_kategori' => 'CATATAN PINGGIR'],
            ['id' => 10, 'nama_kategori' => 'KARTU KELUARGA'],
            ['id' => 11, 'nama_kategori' => 'PINDAH DATANG'],
            ['id' => 12, 'nama_kategori' => 'KTP-EL'],
            ['id' => 13, 'nama_kategori' => 'KIA'],
            ['id' => 14, 'nama_kategori' => 'SURAT'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_layanan');
    }
};

