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
        Schema::create('catatan_pinggir', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->nullable();
            $table->string('kode', 50);
            
            // PRB - Perubahan Nama
            $table->string('nomor_akta_prb')->nullable();
            $table->string('nama_sebelum')->nullable();
            $table->string('nama_sesudah')->nullable();
            $table->string('no_penetapan_pengadilan_prb')->nullable();
            $table->date('tanggal_penetapan_prb')->nullable();
            
            // PGSH - Pengesahan
            $table->string('nomor_akta_pgsh')->nullable();
            $table->string('nama_anak_pgsh')->nullable();
            $table->string('nama_ibu_pgsh')->nullable();
            $table->string('nama_ayah_pgsh')->nullable();
            $table->text('dasar_pengesahan')->nullable();
            
            // PGN - Pengangkatan Anak
            $table->string('nomor_akta_pgn')->nullable();
            $table->string('nama_anak_pgn')->nullable();
            $table->string('nama_ayah_kandung')->nullable();
            $table->string('nama_ibu_kandung')->nullable();
            $table->string('no_penetapan_pengadilan_pgn')->nullable();
            $table->string('nama_ayah_angkat')->nullable();
            $table->string('nama_ibu_angkat')->nullable();
            
            // PGK - Pengakuan Anak
            $table->string('nomor_akta_pgk')->nullable();
            $table->string('nama_anak_pgk')->nullable();
            $table->string('nama_ibu_pgk')->nullable();
            $table->string('nama_ayah_pgk')->nullable();
            $table->text('dasar_pengakuan')->nullable();
            
            // PKOI - Perubahan Kewarganegaraan
            $table->string('perubahan_kewarganegaraan')->nullable(); // WNI-WNA atau WNA-WNI
            $table->string('nama_pkoi')->nullable();
            $table->date('tanggal_lahir_pkoi')->nullable();
            $table->string('jenis_kelamin_pkoi', 1)->nullable(); // L atau P
            $table->text('alamat_pkoi')->nullable();
            $table->string('negara_asal')->nullable();
            $table->string('negara_tujuan')->nullable();
            $table->string('surat_dasar_keputusan')->nullable();
            $table->date('tanggal_surat_keputusan')->nullable();
            $table->string('alasan_perubahan')->nullable();
            
            // Field default
            $table->string('nama_pelapor');
            $table->string('no_hp', 20)->nullable();
            $table->string('file_produk')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreignId('layanan_id')->nullable()->constrained('jenis_layanan')->nullOnDelete();
            $table->foreignId('status_pelapor_id')->nullable()->constrained('status_pelapor')->nullOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('jenis_produk')->nullOnDelete();
            $table->foreignId('status_ajuan_id')->default(1)->constrained('status_ajuan')->restrictOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained('service_requests')->cascadeOnDelete();

            // Indexes
            $table->index('nomor');
            $table->index('kode');
            $table->index('status_ajuan_id');
            $table->index('service_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catatan_pinggir');
    }
};
