<?php

use App\Models\ServiceRequest;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Filament sudah menangani semua route di path ''
// Tidak perlu route tambahan karena Filament sebagai root

// Health check endpoint (opsional)
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Debug logs endpoint to view Laravel errors
Route::get('/debug-logs', function () {
    $logPath = storage_path('logs/laravel.log');
    if (!file_exists($logPath)) {
        return 'No log file found at ' . $logPath;
    }
    
    $lines = file($logPath);
    $lastLines = array_slice($lines, -100);
    
    return response(implode('', $lastLines), 200, ['Content-Type' => 'text/plain']);
});

// Download file routes for Filament resources
Route::middleware([
    'web',
    Authenticate::class,
])->group(function () {
    // Download file for PengirimanResource (handle multiple files)
    Route::get('/pengirimen/{record}/download-file-direct', function ($record) {
        $serviceRequest = ServiceRequest::findOrFail($record);
        
        if (!$serviceRequest->file_produk) {
            abort(404, 'File tidak ditemukan');
        }

        // Get file path from query parameter if provided
        $filePath = request()->query('file');
        
        if ($filePath) {
            // Download specific file from array
            if (!Storage::disk('local')->exists($filePath)) {
                abort(404, 'File tidak ditemukan di storage');
            }
            
            // Mark as downloaded
            $serviceRequest->update(['is_downloaded' => true]);
            
            $fileName = basename($filePath);
            
            return Storage::disk('local')->download(
                $filePath,
                $fileName,
                [
                    'Content-Disposition' => 'attachment; filename="' . addslashes($fileName) . '"',
                ]
            );
        }

        // Legacy: Download first file if file_produk is array
        $fileProduk = $serviceRequest->file_produk;
        if (is_array($fileProduk) && !empty($fileProduk)) {
            $firstFile = $fileProduk[0];
            if (Storage::disk('local')->exists($firstFile)) {
                // Mark as downloaded
                $serviceRequest->update(['is_downloaded' => true]);
                
                $fileName = basename($firstFile);
                return Storage::disk('local')->download(
                    $firstFile,
                    $fileName,
                    [
                        'Content-Disposition' => 'attachment; filename="' . addslashes($fileName) . '"',
                    ]
                );
            }
        } elseif (is_string($fileProduk)) {
            // Handle old format (single file as string)
            if (Storage::disk('local')->exists($fileProduk)) {
                // Mark as downloaded
                $serviceRequest->update(['is_downloaded' => true]);
                
                $fileName = basename($fileProduk);
                return Storage::disk('local')->download(
                    $fileProduk,
                    $fileName,
                    [
                        'Content-Disposition' => 'attachment; filename="' . addslashes($fileName) . '"',
                    ]
                );
            }
        }

        abort(404, 'File tidak ditemukan di storage');
    })->name('filament.admin.resources.pengirimen.download-file-direct');

    // Stream file for preview (PDF viewer)
    Route::get('/pengirimen/{record}/stream-file-direct', function ($record) {
        $serviceRequest = ServiceRequest::findOrFail($record);
        
        $filePath = request()->query('file');
        
        if (!$filePath) {
            // Get first file if no file specified
            $fileProduk = $serviceRequest->file_produk;
            if (is_array($fileProduk) && !empty($fileProduk)) {
                $filePath = $fileProduk[0];
            } elseif (is_string($fileProduk)) {
                $filePath = $fileProduk;
            }
        }

        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            abort(404, 'File tidak ditemukan');
        }

        $fileName = basename($filePath);

        return response()->file(Storage::disk('local')->path($filePath), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
        ]);
    })->name('filament.admin.resources.pengirimen.stream-file-direct');

    // Download file for ServiceRequestResource
    Route::get('/service-requests/{record}/download-file-direct', function ($record) {
        $serviceRequest = ServiceRequest::findOrFail($record);
        
        if (!$serviceRequest->file_produk) {
            abort(404, 'File tidak ditemukan');
        }

        if (!Storage::disk('local')->exists($serviceRequest->file_produk)) {
            abort(404, 'File tidak ditemukan di storage');
        }

        return Storage::disk('local')->download(
            $serviceRequest->file_produk,
            basename($serviceRequest->file_produk)
        );
    })->name('filament.admin.resources.service-requests.download-file-direct');
});
