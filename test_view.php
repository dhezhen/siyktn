<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $peserta = App\Models\Peserta::first();
    if (!$peserta) {
        $peserta = new App\Models\Peserta(['nama' => 'Test', 'jenis_kelamin' => 'L']);
    }
    echo view('peserta.show', ['peserta' => $peserta])->render();
} catch (Throwable $e) {
    echo "ERROR:\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
}
