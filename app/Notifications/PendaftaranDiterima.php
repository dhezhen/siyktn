<?php

namespace App\Notifications;

use App\Models\Peserta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke pendaftar segera setelah formulir dikirim.
 */
class PendaftaranDiterima extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Peserta $peserta) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lembaga = setting('organization', setting('app_name', config('app.name')));

        return (new MailMessage)
            ->subject('Pendaftaran Diterima — '.$this->peserta->kode_pendaftaran)
            ->greeting('Assalamualaikum, '.$this->peserta->nama)
            ->line("Terima kasih, pendaftaran Anda di {$lembaga} sudah kami terima.")
            ->line('**Kode pendaftaran Anda: '.$this->peserta->kode_pendaftaran.'**')
            ->line('Simpan kode ini untuk menanyakan status berkas Anda.')
            ->line('Berkas Anda akan diverifikasi oleh petugas kami. Anda akan menerima email
                    lanjutan begitu hasil verifikasi keluar, biasanya dalam 1–3 hari kerja.')
            ->line('**Ringkasan data Anda**')
            ->line('Nama: '.$this->peserta->nama)
            ->line('Angkatan yang dituju: '.($this->peserta->angkatan?->nama ?? '—'))
            ->line('Nomor HP: '.($this->peserta->no_hp ?: '—'))
            ->salutation('Wassalamualaikum, '.PHP_EOL.$lembaga);
    }
}
