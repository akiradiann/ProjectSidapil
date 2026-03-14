-- Script untuk memperbaiki error migration
-- Jalankan script ini di phpMyAdmin atau MySQL command line

-- 1. Hapus tabel yang gagal dibuat karena foreign key error
DROP TABLE IF EXISTS service_requests;
DROP TABLE IF EXISTS service_request_logs;

-- 2. Hapus record migration yang gagal dari tabel migrations
DELETE FROM migrations WHERE migration IN (
    '2025_01_14_000006_create_service_requests_table',
    '2025_01_14_000007_create_service_request_logs_table'
);

-- 3. Pastikan tabel status_ajuan ada dan lengkap
-- Jika tabel belum ada, buat manual (atau biarkan migration yang buat)
CREATE TABLE IF NOT EXISTS `status_ajuan` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `nama_status` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Insert data status_ajuan jika belum ada
INSERT IGNORE INTO status_ajuan (id, nama_status) VALUES 
(1, 'DIPROSES'),
(2, 'DITOLAK'),
(3, 'SIAP KIRIM'),
(4, 'SIAP DIAMBIL'),
(5, 'SELESAI');

-- 5. Mark migration status_ajuan sebagai sudah berjalan (jika tabel sudah ada)
INSERT IGNORE INTO migrations (migration, batch) 
VALUES ('2025_01_14_000001_create_status_ajuan_table', 1);

-- Selesai! Sekarang jalankan: php artisan migrate

