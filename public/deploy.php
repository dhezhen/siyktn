<?php
// Secret token untuk melindungi URL ini dari eksekusi orang asing
$secret = 'RAHASIA-WEBHOOK-12345';

// Verifikasi token
if (!isset($_GET['token']) || $_GET['token'] !== $secret) {
    http_response_code(403);
    die('Akses ditolak.');
}

// Pastikan skrip berjalan di dalam folder root Laravel (bukan public)
$rootDir = realpath(__DIR__ . '/..');
chdir($rootDir);

echo "<pre>";
echo "Memulai Auto-Deploy CI/CD...\n\n";

// 1. Menarik kode terbaru dari GitHub
echo ">>> git pull origin master 2>&1\n";
$output1 = shell_exec('git pull origin master 2>&1');
echo $output1 . "\n\n";

// 2. Menginstal/Memperbarui pustaka jika ada perubahan di composer.json
echo ">>> ea-php84 /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader 2>&1\n";
$output2 = shell_exec('ea-php84 /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader 2>&1');
echo $output2 . "\n\n";

// 3. Membersihkan dan membangun ulang cache Laravel
echo ">>> ea-php84 artisan optimize:clear 2>&1\n";
$output3 = shell_exec('ea-php84 artisan optimize:clear 2>&1');
echo $output3 . "\n\n";

echo "Deploy Selesai!";
echo "</pre>";
