<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class KirimEmailPendaftaran implements ShouldQueue
{
    use Queueable;

    public $pendaftaran;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\Pendaftaran $pendaftaran)
    {
        $this->pendaftaran = $pendaftaran;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $peserta = $this->pendaftaran->peserta;

        if ($peserta && $peserta->email) {
            \Illuminate\Support\Facades\Mail::to($peserta->email)
                ->send(new \App\Mail\PendaftaranBerhasilMail($this->pendaftaran));
        }
    }
}
