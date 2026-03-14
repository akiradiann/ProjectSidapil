#!/bin/bash
# Quick Fix Script untuk Migration Error

echo "🔧 Memperbaiki error migration..."
echo ""

# Baca database credentials dari .env (jika perlu)
# Atau ganti dengan credentials Anda

echo "⚠️  Pastikan Anda sudah mengganti username dan nama_database!"
echo ""
echo "Langkah 1: Clean up database"
echo "Masuk ke MySQL dan jalankan:"
echo ""
echo "DROP TABLE IF EXISTS service_requests;"
echo "DROP TABLE IF EXISTS service_request_logs;"
echo "DELETE FROM migrations WHERE migration IN ('2025_01_14_000006_create_service_requests_table', '2025_01_14_000007_create_service_request_logs_table');"
echo ""
echo "Langkah 2: Jalankan migration"
echo "php artisan migrate"

