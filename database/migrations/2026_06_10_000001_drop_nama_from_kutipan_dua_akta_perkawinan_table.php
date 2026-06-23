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
        Schema::table('kutipan_dua_akta_perkawinan', function (Blueprint $table) {
            // Drop stray column if it exists
            if (Schema::hasColumn('kutipan_dua_akta_perkawinan', 'nama')) {
                $table->dropColumn('nama');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kutipan_dua_akka_perkawinan', function (Blueprint $table) {
            $table->string('nama')->nullable();
        });
    }
};
