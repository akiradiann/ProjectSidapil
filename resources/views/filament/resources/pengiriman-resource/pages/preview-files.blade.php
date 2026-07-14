@php
    $selectedFile = request('file') 
        ? $files->firstWhere('path', request('file')) 
        : $files->first();
@endphp

<x-filament-panels::page>
    <x-slot name="heading">
        {{ $selectedFile ? 'Preview: ' . $selectedFile['name'] : 'Preview File Produk' }}
    </x-slot>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">{{ $selectedFile ? 'Preview: ' . $selectedFile['name'] : 'Preview File Produk' }}</h2>
                <p class="text-sm text-gray-600">Pilih file untuk dipratinjau dan unduh.</p>
            </div>
            <div class="text-sm text-gray-500">
                Total: {{ $files->count() }} file
            </div>
        </div>

        @if($files->isEmpty())
            <div class="p-4 text-sm text-gray-600 bg-gray-50 rounded-lg">
                Tidak ada file tersedia.
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                <div class="lg:col-span-1">
                    <h3 class="text-xs font-semibold text-gray-700 mb-2">Daftar File</h3>
                    <div class="space-y-1.5">
                        @foreach($files as $index => $file)
                            <div class="p-2 border rounded {{ request('file') == $file['path'] || (request('file') === null && $index === 0) ? 'bg-blue-50 border-blue-200' : 'bg-white' }}">
                                <div class="mb-1.5">
                                    <button type="button" 
                                            class="w-full text-left cursor-pointer hover:text-blue-600 transition-colors"
                                            onclick="window.location.href='?file={{ urlencode($file['path']) }}'">
                                        <div class="text-xs font-medium text-gray-800 truncate" title="{{ $file['name'] }}">{{ $file['name'] }}</div>
                                        <div class="text-xs text-gray-500">File {{ $index + 1 }}</div>
                                    </button>
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <a href="{{ $file['downloadUrl'] }}" 
                                       download="{{ $file['name'] }}"
                                       onclick="event.stopPropagation();"
                                       class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 transition-colors"
                                       title="Download {{ $file['name'] }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                        <span>Unduh</span>
                                    </a>
                                    <button type="button"
                                            onclick="window.location.href='?file={{ urlencode($file['path']) }}'"
                                            class="inline-flex items-center justify-center p-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition-colors"
                                            title="Preview {{ $file['name'] }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="lg:col-span-4">
                    @php
                        $selectedFile = request('file') 
                            ? $files->firstWhere('path', request('file')) 
                            : $files->first();
                    @endphp
                    
                    @if($selectedFile)
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">{{ $selectedFile['name'] }}</div>
                                <div class="text-xs text-gray-500 mt-1">File {{ $selectedFile['index'] + 1 }} dari {{ $files->count() }}</div>
                            </div>
                            <a class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                               href="{{ $selectedFile['downloadUrl'] }}"
                               download="{{ $selectedFile['name'] }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Unduh {{ $selectedFile['name'] }}
                            </a>
                        </div>
                        
                        <div class="border rounded-lg overflow-hidden bg-gray-50 shadow-sm" style="min-height: 80vh; height: calc(100vh - 300px);">
                            <iframe src="{{ $selectedFile['streamUrl'] }}" 
                                    class="w-full h-full" 
                                    frameborder="0"
                                    style="min-height: 80vh; height: calc(100vh - 300px);"
                                    name="preview-{{ $selectedFile['name'] }}"
                                    title="Preview {{ $selectedFile['name'] }}"></iframe>
                        </div>
                        
                        <div class="mt-4 flex items-center justify-between">
                            @if($selectedFile['index'] > 0)
                                <a href="?file={{ urlencode($files->get($selectedFile['index'] - 1)['path']) }}" 
                                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    Sebelumnya
                                </a>
                            @else
                                <div></div>
                            @endif
                            
                            @if($selectedFile['index'] < $files->count() - 1)
                                <a href="?file={{ urlencode($files->get($selectedFile['index'] + 1)['path']) }}" 
                                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    Selanjutnya
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            @else
                                <div></div>
                            @endif
                        </div>
                    @else
                        <div class="p-8 text-center text-sm text-gray-600 bg-gray-50 rounded-lg">
                            File tidak ditemukan.
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
    
    @if($selectedFile)
        <script>
            // Update page title with file name
            document.title = 'Preview: {{ $selectedFile['name'] }} - SIDAPIL';
        </script>
    @endif
</x-filament-panels::page>

