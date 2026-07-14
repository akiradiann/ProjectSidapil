<?php
// hex of the full string including prefix chars before "Pakai"
// Found: "Pakai: ' . $search" = 50616b61693a2027202e2024736561726368
// Need to find what comes before "Pakai" - the ✎ symbol and space

$count = 0;
foreach (glob(__DIR__ . '/app/Filament/Resources/*.php') as $file) {
    $content = file_get_contents($file);
    
    // Use regex to find and replace the full pattern regardless of what symbol precedes "Pakai"
    // Pattern: anything => 'XYZ Pakai: ' . $search (the whole value in the array)
    $new = str_replace("Pakai: ' . \$search", "' . \$search", $content);
    
    // More targeted: replace ['sometext Pakai: ' . $search] with [$search => $search]
    // Actually just replace the entire value part
    $pattern = '/(\$search\s*=>\s*)[\'"][^\'"]*Pakai:\s*[\'"][\s]*\.\s*\$search/';
    $replaced = preg_replace($pattern, '$1$search', $content);
    
    if ($replaced !== $content) {
        file_put_contents($file, $replaced);
        echo 'Updated: ' . basename($file) . PHP_EOL;
        $count++;
    }
}
echo "Total: $count files updated" . PHP_EOL;
