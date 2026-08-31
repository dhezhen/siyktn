<?php

namespace App\Notifications;

use App\Models\Pendaftaran;
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

    public function __construct(public Pendaftaran $pendaftaran) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lembaga = setting('organization', setting('app_name', config('app.name')));
        $peserta = $this->pendaftaran->peserta;
        $ulang = $this->pendaftaran->isPendaftaranUlang();

        $mail = (new MailMessage)
            ->subject('Pendaftaran Diterima — '.$this->pendaftaran->kode_pendaftaran)
            ->greeting(($ulang ? 'Selamat datang kembali, ' : 'Assalamualaikum, ').$peserta->nama);

        $mail->line($ulang
            ? "Pendaftaran ulang Anda di {$lembaga} sudah kami terima. Data Anda sebelumnya kami pakai kembali, jadi tidak perlu mengisi dari awal."
            : "Terima kasih, pendaftaran Anda di {$lembaga} sudah kami terima.");

        $mail->line('**Kode pendaftaran Anda: '.$this->pendaftaran->kode_pendaftaran.'**')
            ->line('Simpan kode ini untuk menanyakan status berkas Anda.')
            ->line('Berkas Anda akan diverifikasi oleh petugas kami. Anda akan menerima email
                    lanjutan begitu hasil verifikasi keluar, biasanya dalam 1–3 hari kerja.')
            ->line('**Ringkasan data Anda**')
            ->line('Nama: '.$peserta->nama)
            ->line('Angkatan yang dituju: '.($this->pendaftaran->angkatan?->nama ?? '—'))
            ->line('Nomor HP: '.($peserta->no_hp ?: '—'))
            ->salutation('Wassalamualaikum, '.PHP_EOL.$lembaga);

        // Generate PDF Instruksi Pembayaran
        $pdfInstruksi = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.instruksi_pembayaran', [
            'pendaftaran' => $this->pendaftaran
        ]);

        $mail->attachData($pdfInstruksi->output(), 'Instruksi_Pembayaran_'.$this->pendaftaran->kode_pendaftaran.'.pdf', [
            'mime' => 'application/pdf',
        ]);

        // Generate PDF Bukti Pendaftaran (with QR)
        $pdfBukti = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.bukti_pendaftaran', [
            'pendaftaran' => $this->pendaftaran
        ]);

        $mail->attachData($pdfBukti->output(), 'Bukti_Pendaftaran_'.$this->pendaftaran->kode_pendaftaran.'.pdf', [
            'mime' => 'application/pdf',
        ]);

        return $mail;
    }
}
