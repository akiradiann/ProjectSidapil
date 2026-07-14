<?php

$files = glob('app/Filament/Resources/*Resource.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'Checklist Persyaratan') !== false) {
        if (strpos($content, '') !== false) {
            $content = str_replace(
                '->visible(fn () =>  &&  !== null)',
                '->visible(fn () => ( || ) &&  !== null)',
                $content
            );
            file_put_contents($file, $content);
            echo "Updated: " . basename($file) . "\n";
        } else {
            echo "Warning: No \ found in " . basename($file) . "\n";
        }
    }
}
