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
        Schema::create('akta_kematian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->nullable();
            $table->string('kode', 50);
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->date('tanggal_kematian');
            $table->string('nama_pelapor');
            $table->string('no_hp', 20)->nullable();
            $table->string('file_produk')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Store API codes as string (not foreign keys to local tables)
            $table->string('kecamatan_id', 20);
            $table->string('desa_id', 20);
            $table->foreignId('layanan_id')->nullable()->constrained('jenis_layanan')->nullOnDelete();
            $table->foreignId('status_pelapor_id')->nullable()->constrained('status_pelapor')->nullOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('jenis_produk')->nullOnDelete();
            $table->foreignId('status_ajuan_id')->default(1)->constrained('status_ajuan')->restrictOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained('service_requests')->cascadeOnDelete();

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
        Schema::dropIfExists('akta_kematian');
    }
};




