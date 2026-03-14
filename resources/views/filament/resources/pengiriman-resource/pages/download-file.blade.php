<x-filament-panels::page>
    <script>
        // Automatically trigger download
        window.location.href = @js($downloadUrl);
    </script>
    <div class="flex items-center justify-center min-h-screen">
        <div class="text-center">
            <p class="text-gray-600">Mengunduh file...</p>
        </div>
    </div>
</x-filament-panels::page>

