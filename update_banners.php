<?php
/**
 * Script to update warning banners to use inline styles instead of purged Tailwind classes
 */

$files = [
    __DIR__ . '/app/Filament/Resources/AktaKelahiranResource.php',
    __DIR__ . '/app/Filament/Resources/AktaKematianResource.php',
    __DIR__ . '/app/Filament/Resources/ServiceRequestResource.php',
];

$oldSnippet = <<<'EOD'
                        <div class="bg-amber-100 border-l-8 border-amber-500 p-5 rounded-r-lg shadow-sm dark:bg-amber-950/40 dark:border-amber-400">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 pt-0.5">
                                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-bold text-amber-900 dark:text-amber-200 uppercase tracking-wide">
                                        AJUAN REVISI
                                    </h3>
                                    <p class="text-sm font-medium text-amber-800 dark:text-amber-300 mt-1">
                                        Ajuan ini adalah perbaikan dokumen dari ajuan yang sebelumnya DITOLAK.
                                    </p>
                                    ' . ($record->catatan ? '<div class="mt-3 p-3 bg-amber-50 rounded-md border border-amber-200 dark:bg-amber-950/20 dark:border-amber-800/50"><p class="text-sm text-amber-800 dark:text-amber-300"><strong>Catatan Penolakan Sebelumnya:</strong> ' . e($record->catatan) . '</p></div>' : '') . '
                                </div>
                            </div>
                        </div>
EOD;

$newSnippet = <<<'EOD'
                        <div style="background-color: #fffbeb; border-left: 6px solid #d97706; padding: 1.25rem; border-radius: 0.375rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); margin-bottom: 1rem;">
                            <div style="display: flex; align-items: flex-start;">
                                <div style="flex-shrink: 0; padding-top: 0.125rem;">
                                    <svg style="height: 1.5rem; width: 1.5rem; color: #d97706;" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div style="margin-left: 1rem;">
                                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #78350f; text-transform: uppercase; letter-spacing: 0.025em; margin: 0;">
                                        AJUAN REVISI
                                    </h3>
                                    <p style="font-size: 0.875rem; font-weight: 500; color: #92400e; margin-top: 0.25rem; margin-bottom: 0;">
                                        Ajuan ini adalah perbaikan dokumen dari ajuan yang sebelumnya DITOLAK.
                                    </p>
                                    ' . ($record->catatan ? '<div style="margin-top: 0.75rem; padding: 0.75rem; background-color: #fff9e6; border: 1px solid #fef3c7; border-radius: 0.375rem;"><p style="font-size: 0.875rem; color: #92400e; margin: 0;"><strong>Catatan Penolakan Sebelumnya:</strong> ' . e($record->catatan) . '</p></div>' : '') . '
                                </div>
                            </div>
                        </div>
EOD;

$count = 0;
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    // Normalize to handle CRLF/LF mismatch
    $normalizedContent = str_replace("\r\n", "\n", $content);
    $normalizedSearch = str_replace("\r\n", "\n", $oldSnippet);
    
    if (strpos($normalizedContent, $normalizedSearch) !== false) {
        $replaced = str_replace($normalizedSearch, $newSnippet, $normalizedContent);
        if (strpos($content, "\r\n") !== false) {
            $replaced = str_replace("\n", "\r\n", $replaced);
        }
        file_put_contents($file, $replaced);
        echo "Updated banner in: " . basename($file) . PHP_EOL;
        $count++;
    } else {
        echo "Pattern not found in: " . basename($file) . PHP_EOL;
    }
}
echo "Done updating $count files" . PHP_EOL;
