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
        Schema::table('pindah_datang', function (Blueprint $table) {
            $table->string('file_produk')->nullable()->after('produk_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pindah_datang', function (Blueprint $table) {
            $table->dropColumn('file_produk');
        });
    }
};
