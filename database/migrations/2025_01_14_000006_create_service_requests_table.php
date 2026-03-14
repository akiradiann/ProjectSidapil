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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_layanan', 50)->nullable();
            $table->foreignId('kategori_layanan_id')->constrained('kategori_layanan')->restrictOnDelete();
            $table->foreignId('jenis_layanan_id')->nullable()->constrained('jenis_layanan')->nullOnDelete();
            $table->foreignId('jenis_produk_id')->nullable()->constrained('jenis_produk')->nullOnDelete();
            $table->foreignId('status_pelapor_id')->nullable()->constrained('status_pelapor')->nullOnDelete();
            $table->foreignId('status_ajuan_id')->default(1)->constrained('status_ajuan')->restrictOnDelete();
            $table->foreignId('fo_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cs_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('loket_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_produk')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('nomor_layanan');
            $table->index('kategori_layanan_id');
            $table->index('status_ajuan_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};

