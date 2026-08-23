<?php

namespace App\Notifications;

use App\Models\Peserta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendaftaranDisetujui extends Notification implements ShouldQueue
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
            ->subject('Selamat! Pendaftaran Anda Disetujui')
            ->greeting('Assalamualaikum, '.$this->peserta->nama)
            ->line("Alhamdulillah, pendaftaran Anda di {$lembaga} telah **disetujui**.")
            ->line('**Nomor induk Anda: '.$this->peserta->nomor_induk.'**')
            ->line('Angkatan: '.($this->peserta->angkatan?->nama ?? '—'))
            ->line('Simpan nomor induk ini, karena akan dipakai pada seluruh kegiatan
                    dan dokumen Anda selama mengikuti program.')
            ->line('Informasi jadwal dan tahapan berikutnya akan kami sampaikan menyusul.')
            ->salutation('Wassalamualaikum, '.PHP_EOL.$lembaga);
    }
}
