<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

class BackupController extends Controller
{
    protected string $diskName = 'local';
    
    public function index(): View
    {
        $backupName = env('APP_NAME', 'laravel-backup');
        $disk = Storage::disk($this->diskName);
        $files = $disk->files($backupName);

        $backups = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $backups[] = [
                    'file_path' => $file,
                    'file_name' => basename($file),
                    'file_size' => $this->humanFilesize($disk->size($file)),
                    'last_modified' => Carbon::createFromTimestamp($disk->lastModified($file)),
                ];
            }
        }

        // Urutkan dari yang paling baru
        usort($backups, function ($a, $b) {
            return $b['last_modified']->timestamp <=> $a['last_modified']->timestamp;
        });

        return view('backup.index', compact('backups'));
    }

    public function create()
    {
        // Jalankan artisan command di background
        Artisan::call('backup:run', ['--only-db' => false]);
        
        return back()->with('success', 'Proses pencadangan (backup) berhasil dijalankan.');
    }

    public function download($file_name)
    {
        $backupName = env('APP_NAME', 'laravel-backup');
        $file = $backupName . '/' . $file_name;
        $disk = Storage::disk($this->diskName);

        if ($disk->exists($file)) {
            return Storage::disk($this->diskName)->download($file);
        }

        return back()->with('error', 'File backup tidak ditemukan.');
    }

    public function destroy($file_name)
    {
        $backupName = env('APP_NAME', 'laravel-backup');
        $file = $backupName . '/' . $file_name;
        $disk = Storage::disk($this->diskName);

        if ($disk->exists($file)) {
            $disk->delete($file);
            return back()->with('success', 'File backup berhasil dihapus.');
        }

        return back()->with('error', 'File backup tidak ditemukan.');
    }

    protected function humanFilesize($bytes, $decimals = 2)
    {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }
}
