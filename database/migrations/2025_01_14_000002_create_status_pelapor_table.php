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
        Schema::create('status_pelapor', function (Blueprint $table) {
            $table->id();
            $table->string('nama_status');
        });

        // Insert default data
        DB::table('status_pelapor')->insert([
            ['id' => 1, 'nama_status' => 'MANDIRI'],
            ['id' => 2, 'nama_status' => 'OPERATOR DESA'],
            ['id' => 3, 'nama_status' => 'PANDUSAKTI'],
            ['id' => 4, 'nama_status' => 'PETUGAS KEAGAMAAN'],
            ['id' => 5, 'nama_status' => 'OPDES DUKATAMAT'],
            ['id' => 6, 'nama_status' => 'LAINNYA'],

        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_pelapor');
    }
};

