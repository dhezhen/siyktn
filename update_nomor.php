<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = \App\Models\Pendaftaran::whereNotNull('nomor_induk')->get();
$count = 0;
foreach($p as $item) {
    if (str_starts_with($item->nomor_induk, 'YKTN.')) {
        continue;
    }
    
    $urutan = (int) \Illuminate\Support\Str::afterLast($item->nomor_induk, '-');
    $angkatan = $item->angkatan;
    
    $batch = \Illuminate\Support\Str::after($angkatan->kode, '-'); 
    if (!$batch || !is_numeric($batch)) {
        preg_match('/\d+/', $angkatan->nama, $matches);
        $batch = $matches[0] ?? $angkatan->kode;
    }
    
    $newNomor = sprintf('YKTN.%d.%s.%04d', $angkatan->tahun, $batch, $urutan);
    $item->nomor_induk = $newNomor;
    $item->saveQuietly();
    $count++;
}

echo "Done updating {$count} records.\n";
