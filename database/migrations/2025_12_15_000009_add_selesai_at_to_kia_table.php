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
        Schema::table('kia', function (Blueprint $table) {
            $table->timestamp('selesai_at')->nullable()->after('status_ajuan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kia', function (Blueprint $table) {
            $table->dropColumn('selesai_at');
        });
    }
};
