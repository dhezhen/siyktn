<?php

namespace App\Notifications;

use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke petugas yang berhak memverifikasi pendaftaran.
 */
class PendaftaranBaruMasuk extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Pendaftaran $pendaftaran) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $peserta = $this->pendaftaran->peserta;

        $sumber = $this->pendaftaran->sumber_pendaftaran === 'mandiri'
            ? 'pendaftaran mandiri'
            : 'input petugas';

        $sapaan = $notifiable instanceof User ? 'Halo '.$notifiable->name : 'Halo';

        $mail = (new MailMessage)
            ->subject('Pendaftaran baru: '.$peserta->nama)
            ->greeting($sapaan)
            ->line("Ada pendaftaran peserta baru lewat {$sumber} yang perlu ditinjau.");

        if ($this->pendaftaran->isPendaftaranUlang()) {
            $mail->line('**Ini pendaftaran ulang.** Orang ini sudah pernah terdaftar, '
                .'sehingga berkas identitasnya kemungkinan sudah pernah diverifikasi.');
        }

        return $mail
            ->line('Kode: '.$this->pendaftaran->kode_pendaftaran)
            ->line('Nama: '.$peserta->nama)
            ->line('Angkatan: '.($this->pendaftaran->angkatan?->nama ?? '—'))
            ->line('Nomor HP: '.($peserta->no_hp ?: '—'))
            ->action('Tinjau Pendaftaran', route('pendaftaran.index'))
            ->line('Berkas KTP/KK tersimpan di sistem dan hanya bisa dibuka oleh petugas berwenang.');
    }
}
