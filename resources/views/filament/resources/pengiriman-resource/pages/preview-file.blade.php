<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Pratinjau File</h2>
                <p class="text-sm text-gray-600">Pilih file untuk dipratinjau dan unduh.</p>
            </div>
        </div>

        @if($files->isEmpty())
            <div class="p-4 text-sm text-gray-600 bg-gray-50 rounded-lg">
                Tidak ada file tersedia.
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <div class="lg:col-span-1 space-y-2">
                    @foreach($files as $index => $file)
                        <div class="flex items-center justify-between p-2 border rounded @if($index === 0) bg-blue-50 border-blue-200 @else bg-white @endif">
                            <div class="text-sm text-gray-800 truncate">{{ $file['name'] }}</div>
                            <a class="text-blue-600 text-sm font-medium" href="?file={{ urlencode($file['name']) }}">Preview</a>
                        </div>
                    @endforeach
                </div>
                <div class="lg:col-span-3">
                    @php
                        $currentFile = $files->firstWhere('name', request('file')) ?? $files->first();
                    @endphp
                    <div class="mb-2 flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-700">{{ $currentFile['name'] ?? 'File' }}</div>
                        @if($currentFile)
                            <a class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700"
                               href="{{ $currentFile['downloadUrl'] }}">Unduh</a>
                        @endif
                    </div>
                    @if($currentFile)
                        <div class="aspect-[16/9] border rounded overflow-hidden bg-gray-50">
                            <iframe src="{{ $currentFile['streamUrl'] }}" class="w-full h-full" frameborder="0"></iframe>
                        </div>
                    @else
                        <div class="p-4 text-sm text-gray-600 bg-gray-50 rounded">
                            File tidak ditemukan.
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>


