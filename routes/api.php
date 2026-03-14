<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Sistem SIDAPIL menggunakan Filament sebagai interface utama.
| API routes ini bisa digunakan untuk integrasi eksternal jika diperlukan.
|
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'SIDAPIL',
        'version' => '1.0.0',
    ]);
});
