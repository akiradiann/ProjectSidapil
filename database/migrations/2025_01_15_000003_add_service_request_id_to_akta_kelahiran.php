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
        if (Schema::hasTable('akta_kelahiran') && !Schema::hasColumn('akta_kelahiran', 'service_request_id')) {
            Schema::table('akta_kelahiran', function (Blueprint $table) {
                $table->foreignId('service_request_id')->nullable()->after('catatan')->constrained('service_requests')->cascadeOnDelete();
                $table->index('service_request_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('akta_kelahiran') && Schema::hasColumn('akta_kelahiran', 'service_request_id')) {
            Schema::table('akta_kelahiran', function (Blueprint $table) {
                $table->dropForeign(['service_request_id']);
                $table->dropIndex(['service_request_id']);
                $table->dropColumn('service_request_id');
            });
        }
    }
};

