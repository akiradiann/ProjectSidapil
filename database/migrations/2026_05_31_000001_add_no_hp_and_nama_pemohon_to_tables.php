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
        // 1. Add columns to service_requests
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('no_hp', 50)->nullable()->after('loket_id');
            $table->string('nama_pemohon', 255)->nullable()->after('no_hp');
        });

        // 2. Add no_hp to ktp_el, kia, kartu_keluarga, pindah_datang
        Schema::table('ktp_el', function (Blueprint $table) {
            $table->string('no_hp', 50)->nullable()->after('nama');
        });

        Schema::table('kia', function (Blueprint $table) {
            $table->string('no_hp', 50)->nullable()->after('nama');
        });

        Schema::table('kartu_keluarga', function (Blueprint $table) {
            $table->string('no_hp', 50)->nullable()->after('nama_pemohon');
        });

        Schema::table('pindah_datang', function (Blueprint $table) {
            $table->string('no_hp', 50)->nullable()->after('nama_pemohon');
        });

        // 3. Migrate existing data from child tables to service_requests
        $this->migrateExistingData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['no_hp', 'nama_pemohon']);
        });

        if (Schema::hasColumn('ktp_el', 'no_hp')) {
            Schema::table('ktp_el', function (Blueprint $table) {
                $table->dropColumn('no_hp');
            });
        }

        if (Schema::hasColumn('kia', 'no_hp')) {
            Schema::table('kia', function (Blueprint $table) {
                $table->dropColumn('no_hp');
            });
        }

        if (Schema::hasColumn('kartu_keluarga', 'no_hp')) {
            Schema::table('kartu_keluarga', function (Blueprint $table) {
                $table->dropColumn('no_hp');
            });
        }

        if (Schema::hasColumn('pindah_datang', 'no_hp')) {
            Schema::table('pindah_datang', function (Blueprint $table) {
                $table->dropColumn('no_hp');
            });
        }
    }

    /**
     * Migrate existing data
     */
    private function migrateExistingData(): void
    {
        $mappings = [
            'akta_kelahiran' => ['no_hp' => 'no_hp', 'nama_pemohon' => 'nama_pelapor'],
            'akta_kematian' => ['no_hp' => 'no_hp', 'nama_pemohon' => 'nama_pelapor'],
            'akta_perkawinan' => ['no_hp' => 'no_hp', 'nama_pemohon' => 'nama_pelapor'],
            'akta_perceraian' => ['no_hp' => 'no_hp', 'nama_pemohon' => 'nama_pelapor'],
            'catatan_pinggir' => ['no_hp' => 'no_hp', 'nama_pemohon' => 'nama_pelapor'],
            'kutipan_dua_akta_kelahiran' => ['no_hp' => 'no_hp', 'nama_pemohon' => 'nama_pelapor'],
            'kutipan_dua_akta_kematian' => ['no_hp' => 'no_hp', 'nama_pemohon' => 'nama_pelapor'],
            'kutipan_dua_akta_perkawinan' => ['no_hp' => 'no_hp', 'nama_pemohon' => 'nama_pelapor'],
            'kutipan_dua_akta_perceraian' => ['no_hp' => 'no_hp', 'nama_pemohon' => 'nama_pelapor'],
            'surat' => ['no_hp' => 'no_hp', 'nama_pemohon' => 'nama_pemohon'],
            
            // For tables without no_hp, only pull name
            'kartu_keluarga' => ['no_hp' => null, 'nama_pemohon' => 'nama_pemohon'],
            'pindah_datang' => ['no_hp' => null, 'nama_pemohon' => 'nama_pemohon'],
            'ktp_el' => ['no_hp' => null, 'nama_pemohon' => 'nama'],
            'kia' => ['no_hp' => null, 'nama_pemohon' => 'nama'],
        ];

        foreach ($mappings as $table => $cols) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                $records = DB::table($table)->whereNotNull('service_request_id')->get();
                foreach ($records as $rec) {
                    $updateData = [];
                    if ($cols['no_hp'] && isset($rec->{$cols['no_hp']})) {
                        $updateData['no_hp'] = $rec->{$cols['no_hp']};
                    }
                    if ($cols['nama_pemohon'] && isset($rec->{$cols['nama_pemohon']})) {
                        $updateData['nama_pemohon'] = $rec->{$cols['nama_pemohon']};
                    }

                    if (!empty($updateData)) {
                        DB::table('service_requests')
                            ->where('id', $rec->service_request_id)
                            ->update($updateData);
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Failed to migrate data for table {$table}: " . $e->getMessage());
            }
        }
    }
};
