<?php

namespace App\Notifications;

use App\Models\Peserta;
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

    public function __construct(public Peserta $peserta) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sumber = $this->peserta->sumber_pendaftaran === 'mandiri'
            ? 'pendaftaran mandiri'
            : 'input petugas';

        $sapaan = $notifiable instanceof \App\Models\User
            ? 'Halo '.$notifiable->name
            : 'Halo';

        return (new MailMessage)
            ->subject('Pendaftaran baru: '.$this->peserta->nama)
            ->greeting($sapaan)
            ->line("Ada pendaftaran peserta baru lewat {$sumber} yang perlu ditinjau.")
            ->line('Kode: '.$this->peserta->kode_pendaftaran)
            ->line('Nama: '.$this->peserta->nama)
            ->line('Angkatan: '.($this->peserta->angkatan?->nama ?? '—'))
            ->line('Nomor HP: '.($this->peserta->no_hp ?: '—'))
            ->action('Tinjau Pendaftaran', route('pendaftaran.index'))
            ->line('Berkas KTP tersimpan di sistem dan hanya bisa dibuka oleh petugas berwenang.');
    }
}
