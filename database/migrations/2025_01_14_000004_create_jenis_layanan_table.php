<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jenis_layanan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_layanan');
        });

        DB::table('jenis_layanan')->insert([
            ['id' => 1, 'nama_layanan' => 'LOKET DINAS'],
            ['id' => 2, 'nama_layanan' => 'ONLINE'],
            ['id' => 3, 'nama_layanan' => 'LOKET MPP'],
            ['id' => 4, 'nama_layanan' => 'LOKET CFD'],
            ['id' => 5, 'nama_layanan' => 'LOKET LAYANAN KELILING'],
            ['id' => 6, 'nama_layanan' => 'IKD'],
            ['id' => 7, 'nama_layanan' => 'LOKET KECAMATAN'],
            ['id' => 8, 'nama_layanan' => 'INOVASI'],
            ['id' => 9, 'nama_layanan' => 'PENCATATAN KELUAR'],
            ['id' => 10, 'nama_layanan' => 'LAINNYA'],
            ['id' => 11, 'nama_layanan' => 'PANDUSAKTI'],
            ['id' => 12, 'nama_layanan' => 'DUKATAMAT'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_layanan');
    }
};

