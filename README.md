# SIDAPIL - Sistem Informasi Pendaftaran Sipil

Project ini dibangun menggunakan **Laravel** (PHP framework) dan **Filament** (Admin Panel).

## Persyaratan Sistem (Requirements)
Pastikan laptop/pc kamu sudah terinstall:
- [PHP](https://www.php.net/downloads) (Versi 8.1 atau lebih baru)
- [Composer](https://getcomposer.org/) (PHP Dependency Manager)
- [Node.js & NPM](https://nodejs.org/) (Untuk build asset)
- [MySQL / MariaDB](https://mariadb.org/) (Untuk database, bisa pakai XAMPP/Laragon)

## Cara Install (Cloning Project)

Ikuti langkah-langkah ini jika kamu baru pertama kali mengambil (clone) project ini dari GitHub.

### 1. Clone Project
Buka terminal (Git Bash / CMD / Powershell) dan jalankan:
```bash
git clone https://github.com/alifamifta/SIDAPIL.git
cd SIDAPIL
```

### 2. Install Dependency PHP (Composer)
Ini akan mendownload semua library PHP yang dibutuhkan Laravel.
```bash
composer install
```
*Tunggu sampai proses selesai. Jika error, pastikan versi PHP kamu sesuai.*

### 3. Install Dependency Asset (NPM)
Ini akan mendownload tools untuk styling (CSS/JS).
```bash
npm install
```

### 4. Setup File Environment (.env)
Copy file contoh `.env.example` menjadi `.env`.
```bash
cp .env.example .env
```
*(Jika di Windows CMD tidak bisa perintah `cp`, copy paste manual saja file `.env.example` lalu rename jad `.env`)*

### 5. Generate Application Key
Kunci enkripsi agar aplikasi aman.
```bash
php artisan key:generate
```

### 6. Setup Database
1.  Buka phpMyAdmin (http://localhost/phpmyadmin) atau aplikasi database manager lain (DBeaver/HeidiSQL).
2.  Buat database baru dengan nama: `sidapil`.
3.  Buka file `.env` yang baru kamu buat tadi, pastikan settingannya sesuai:
    ```ini
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=sidapil
    DB_USERNAME=root
    DB_PASSWORD=
    ```
    

### 7. Jalankan Migrasi & Seeding
Ini akan membuat tabel-tabel di database dan mengisi data awal (seperti akun Admin).
```bash
php artisan migrate:fresh --seed
```

### 8. Link Storage (Untuk Upload Foto)
Agar file upload (foto/pdf) bisa diakses publik.
```bash
php artisan storage:link
```

### 9. Jalankan Aplikasi
1.  **Terminal 1 (Jalankan Server PHP):**
    ```bash
    php artisan serve
    ```
    Aplikasi bisa diakses di: [http://127.0.0.1:8000](http://127.0.0.1:8000)



## Akun Login Default
Setelah menjalankan seeding, kamu bisa login dengan akun admin:
- **Email:** `admin@admin.com`
- **Password:** `password`

---
Selamat bekerja!
