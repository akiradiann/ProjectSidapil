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
        Schema::create('jenis_produk', function (Blueprint $table) {
            $table->id();
            $table->string('nama_produk');
        });

        // Insert default data
        DB::table('jenis_produk')->insert([
            ['id' => 1, 'nama_produk' => 'DIAMBIL'],
            ['id' => 2, 'nama_produk' => 'FILE'],
            ['id' => 3, 'nama_produk' => 'POS'],
            ['id' => 4, 'nama_produk' => 'DISERAHKAN'],
            ['id' => 5, 'nama_produk' => 'ADEK MANJA'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_produk');
    }
};

