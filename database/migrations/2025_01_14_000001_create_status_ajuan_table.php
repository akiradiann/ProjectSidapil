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
        Schema::create('status_ajuan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_status');
        });

        // Insert default data
        DB::table('status_ajuan')->insert([
            ['id' => 1, 'nama_status' => 'DIPROSES'],
            ['id' => 2, 'nama_status' => 'DITOLAK'],
            ['id' => 3, 'nama_status' => 'SIAP KIRIM'],
            ['id' => 4, 'nama_status' => 'SIAP DIAMBIL'],
            ['id' => 5, 'nama_status' => 'SELESAI'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_ajuan');
    }
};
