<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class DownloadFile extends Page
{
    protected static string $resource = ServiceRequestResource::class;

    protected static string $view = 'filament.resources.service-request-resource.pages.download-file';

    protected static ?string $title = 'Download File';

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

        // Check if file exists
        if (!Storage::disk('local')->exists($this->serviceRequest->file_produk)) {
            abort(404, 'File tidak ditemukan di storage');
        }
    }

    protected function getViewData(): array
    {
        return [
            'downloadUrl' => route('filament.admin.resources.service-requests.download-file-direct', $this->serviceRequest->id),
        ];
    }
}

