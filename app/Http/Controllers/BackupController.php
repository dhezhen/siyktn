<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    public function create()
    {
        // Run the backup command in the background
        Artisan::call('backup:run', ['--only-db' => false]);
        
        return back()->with('success', 'Proses pencadangan (backup) berhasil dijalankan.');
    }

    public function download()
    {
        $disk = Storage::disk('local');
        $backupName = env('APP_NAME', 'laravel-backup');
        $backupPath = "{$backupName}";
        
        if (!$disk->exists($backupPath)) {
            return back()->with('error', 'Belum ada file backup yang tersedia.');
        }

        // Get the latest file in the directory
        $files = $disk->files($backupPath);
        
        if (empty($files)) {
            return back()->with('error', 'Belum ada file backup yang tersedia.');
        }

        // Sort files by last modified time descending
        usort($files, function ($a, $b) use ($disk) {
            return $disk->lastModified($b) <=> $disk->lastModified($a);
        });

        $latestBackup = $files[0];

        return response()->download($disk->path($latestBackup));
    }
}
