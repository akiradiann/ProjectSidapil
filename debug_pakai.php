<?php
$c = file_get_contents(__DIR__ . '/app/Filament/Resources/KiaResource.php');
preg_match('/Pakai.*?\$search/', $c, $m);
if ($m) {
    echo 'Found: ' . bin2hex($m[0]) . PHP_EOL;
    echo 'Text: ' . $m[0] . PHP_EOL;
} else {
    echo 'Pattern not found' . PHP_EOL;
    // Search for any occurrence near "Pakai"
    $pos = strpos($c, 'Pakai');
    if ($pos !== false) {
        $snippet = substr($c, $pos - 5, 50);
        echo 'Snippet hex: ' . bin2hex($snippet) . PHP_EOL;
        echo 'Snippet: ' . $snippet . PHP_EOL;
    } else {
        echo '"Pakai" not found in file at all' . PHP_EOL;
    }
}
