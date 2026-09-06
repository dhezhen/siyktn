<?php
$c = file_get_contents('c:\laragon\www\siyktn\resources\views\peserta\show.blade.php');
$lines = explode("\n", $c);
foreach ($lines as $i => $line) {
    if (strpos($line, '@php') !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
        echo "Hex: " . bin2hex(trim($line)) . "\n";
    }
}
