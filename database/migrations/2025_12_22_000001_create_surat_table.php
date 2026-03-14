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
        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->nullable();
            $table->string('jenis', 50); // PERMOHONAN, KEABSAHAN
            $table->string('nama');
            $table->string('no_akta');
            $table->string('tujuan');
            $table->string('nama_pemohon');
            $table->string('no_hp', 20)->nullable();
            
            // Relationships
            $table->foreignId('layanan_id')->nullable()->constrained('jenis_layanan')->nullOnDelete();
            $table->foreignId('status_pelapor_id')->nullable()->constrained('status_pelapor')->nullOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('jenis_produk')->nullOnDelete();
            $table->foreignId('status_ajuan_id')->default(1)->constrained('status_ajuan')->restrictOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained('service_requests')->cascadeOnDelete();
            
            $table->text('file_produk')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('nomor');
            $table->index('status_ajuan_id');
            $table->index('service_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};
