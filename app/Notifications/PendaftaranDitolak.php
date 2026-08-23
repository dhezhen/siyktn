<?php

namespace App\Notifications;

use App\Models\Peserta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendaftaranDitolak extends Notification implements ShouldQueue
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

        $mail = (new MailMessage)
            ->subject('Hasil Verifikasi Pendaftaran — '.$this->peserta->kode_pendaftaran)
            ->greeting('Assalamualaikum, '.$this->peserta->nama)
            ->line("Terima kasih telah mendaftar di {$lembaga}.")
            ->line('Setelah kami periksa, pendaftaran Anda dengan kode
                    **'.$this->peserta->kode_pendaftaran.'** belum dapat kami setujui.');

        if ($this->peserta->alasan_penolakan) {
            $mail->line('**Alasan:** '.$this->peserta->alasan_penolakan);
        }

        return $mail
            ->line('Anda dipersilakan mendaftar kembali setelah melengkapi atau memperbaiki data
                    yang dimaksud. Bila ada yang ingin ditanyakan, silakan hubungi kami.')
            ->salutation('Wassalamualaikum, '.PHP_EOL.$lembaga);
    }
}
