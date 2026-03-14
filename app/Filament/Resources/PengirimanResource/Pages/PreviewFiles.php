<?php

namespace App\Filament\Resources\PengirimanResource\Pages;

use App\Filament\Resources\PengirimanResource;
use App\Models\ServiceRequest;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class PreviewFiles extends Page
{
    protected static string $resource = PengirimanResource::class;

    protected static string $view = 'filament.resources.pengiriman-resource.pages.preview-files';

    protected static ?string $title = 'Preview File Produk';

    protected ?ServiceRequest $serviceRequest = null;

    public function mount(int | string $record)
    {
        // Get record ID - handle both object and ID
        $recordId = is_object($record) ? $record->id : $record;
        
        // Find the record
        $this->serviceRequest = ServiceRequest::findOrFail($recordId);
        
        if (!$this->serviceRequest->file_produk) {
            abort(404, 'File tidak ditemukan');
        }
    }

    public function getTitle(): string
    {
        $files = $this->getFiles();
        if ($files->isNotEmpty()) {
            $filePath = request()->query('file');
            $selectedFile = $filePath 
                ? $files->firstWhere('path', $filePath) 
                : $files->first();
            
            if ($selectedFile) {
                return 'Preview: ' . $selectedFile['name'];
            }
        }
        
        return static::$title;
    }

    public function getFiles()
    {
        $fileProduk = $this->serviceRequest->file_produk;
        
        // Handle both array (new format) and string (old format)
        if (is_array($fileProduk)) {
            $files = $fileProduk;
        } elseif (is_string($fileProduk)) {
            // Try to decode JSON, if fails treat as single file
            $decoded = json_decode($fileProduk, true);
            $files = $decoded !== null ? $decoded : [$fileProduk];
        } else {
            $files = [];
        }

        $filesData = collect($files)->map(function ($filePath, $index) {
            if (!Storage::disk('local')->exists($filePath)) {
                return null;
            }

            $fileName = basename($filePath);
            $downloadUrl = route('filament.admin.resources.pengirimen.download-file-direct', [
                'record' => $this->serviceRequest->id,
                'file' => $filePath,
            ]);
            
            // For PDF preview, use stream URL
            $streamUrl = route('filament.admin.resources.pengirimen.stream-file-direct', [
                'record' => $this->serviceRequest->id,
                'file' => $filePath,
            ]);

            return [
                'name' => $fileName,
                'path' => $filePath,
                'downloadUrl' => $downloadUrl,
                'streamUrl' => $streamUrl,
                'index' => $index,
            ];
        })->filter()->values(); // Reset keys after filter

        // Re-index after filtering
        return $filesData->map(function ($file, $newIndex) {
            $file['index'] = $newIndex;
            return $file;
        });
    }

    protected function getViewData(): array
    {
        return [
            'serviceRequest' => $this->serviceRequest,
            'files' => $this->getFiles(),
        ];
    }
}

