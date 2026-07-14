<?php

$files = glob(__DIR__ . '/app/Filament/Resources/*Resource.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'Checklist Persyaratan') !== false) {
        $search = '->visible(fn ($record) => $isOperator && $record !== null)';
        $replace = '->visible(fn ($record) => ($isOperator || $isAdmin) && $record !== null)';
        
        // Also handle CatatanPinggirResource just in case it has different spacing or escaping
        $search2 = '->visible(fn ($record) => $isOperator && $record !== null)';
        
        if (strpos($content, $search) !== false || strpos($content, $search2) !== false) {
            $content = str_replace($search, $replace, $content);
            file_put_contents($file, $content);
            echo "Successfully Updated: " . basename($file) . "\n";
        } else {
            echo "Target string not found in " . basename($file) . "\n";
        }
    }
}
