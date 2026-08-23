<?php

namespace App\Services;

use App\Models\Angkatan;
use App\Models\Peserta;
use App\Models\User;
use App\Notifications\PendaftaranBaruMasuk;
use App\Notifications\PendaftaranDisetujui;
use App\Notifications\PendaftaranDiterima;
use App\Notifications\PendaftaranDitolak;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Satu tempat untuk seluruh alur pendaftaran peserta.
 *
 * Dipakai bersama oleh formulir pendaftaran mandiri (publik) dan oleh
 * petugas yang menginput peserta dari dalam sistem, supaya aturan
 * penomoran dan pengiriman email tidak ditulis dua kali.
 */
class PendaftaranService
{
    /**
     * Permission yang menentukan siapa saja yang diberi tahu saat ada
     * pendaftaran baru.
     */
    protected const PERMISSION_PENINJAU = 'peserta.approve';

    /**
     * Simpan berkas KTP ke disk privat.
     *
     * KTP adalah data pribadi, jadi TIDAK disimpan di disk `public`.
     * Berkasnya hanya bisa dibuka lewat route yang dijaga permission.
     */
    public function simpanKtp(\Illuminate\Http\UploadedFile $file): string
    {
        return $file->store('ktp', 'local');
    }

    public function hapusKtp(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Catat pendaftaran baru, lalu kirim email ke pendaftar dan peninjau.
     *
     * @param  array<string, mixed>  $data
     */
    public function daftarkan(array $data, string $sumber = 'mandiri'): Peserta
    {
        $peserta = Peserta::create(array_merge($data, [
            'kode_pendaftaran' => Peserta::kodePendaftaranBerikutnya(),
            'sumber_pendaftaran' => $sumber,
            'didaftarkan_pada' => now(),
        ]));

        $this->kirimNotifikasiPendaftaranBaru($peserta);

        return $peserta;
    }

    /**
     * Setujui pendaftaran: beri nomor induk, aktifkan, lalu kabari pendaftar.
     */
    public function setujui(Peserta $peserta, User $peninjau): Peserta
    {
        if (! $peserta->isMenunggu()) {
            return $peserta;
        }

        $peserta->forceFill([
            'nomor_induk' => $peserta->nomor_induk
                ?: Peserta::nomorIndukBerikutnya($peserta->angkatan),
            'status_pendaftaran' => 'disetujui',
            'status' => 'aktif',
            'tanggal_masuk' => $peserta->tanggal_masuk ?? now()->toDateString(),
            'ditinjau_pada' => now(),
            'ditinjau_oleh' => $peninjau->id,
            'alasan_penolakan' => null,
        ])->save();

        $this->kirimKePendaftar($peserta, new PendaftaranDisetujui($peserta->fresh('angkatan')));

        return $peserta;
    }

    /**
     * Tolak pendaftaran dengan alasan, lalu kabari pendaftar.
     */
    public function tolak(Peserta $peserta, User $peninjau, string $alasan): Peserta
    {
        if (! $peserta->isMenunggu()) {
            return $peserta;
        }

        $peserta->forceFill([
            'status_pendaftaran' => 'ditolak',
            'status' => 'keluar',
            'ditinjau_pada' => now(),
            'ditinjau_oleh' => $peninjau->id,
            'alasan_penolakan' => $alasan,
        ])->save();

        $this->kirimKePendaftar($peserta, new PendaftaranDitolak($peserta));

        return $peserta;
    }

    /**
     * Angkatan yang masih menerima pendaftaran: belum selesai dan kuotanya belum habis.
     *
     * @return Collection<int, Angkatan>
     */
    public function angkatanTerbuka(): Collection
    {
        return Angkatan::query()
            ->whereIn('status', ['persiapan', 'berjalan'])
            ->orderBy('tahun')
            ->get()
            ->filter(fn (Angkatan $a) => $a->sisa_kuota === null || $a->sisa_kuota > 0)
            ->values();
    }

    /**
     * Email ke pendaftar (bila mengisi email) dan ke seluruh peninjau.
     */
    public function kirimNotifikasiPendaftaranBaru(Peserta $peserta): void
    {
        $peserta->loadMissing('angkatan');

        $this->kirimKePendaftar($peserta, new PendaftaranDiterima($peserta));

        $peninjau = $this->peninjau();

        if ($peninjau->isNotEmpty()) {
            $this->kirim(
                fn () => Notification::send($peninjau, new PendaftaranBaruMasuk($peserta)),
                'peninjau pendaftaran'
            );
        }

        // Salinan ke email resmi lembaga, bila diisi di Pengaturan Aplikasi.
        $emailLembaga = setting('email');

        if ($emailLembaga) {
            $this->kirim(
                fn () => Notification::route('mail', $emailLembaga)
                    ->notify(new PendaftaranBaruMasuk($peserta)),
                'email lembaga'
            );
        }
    }

    protected function kirimKePendaftar(Peserta $peserta, $notification): void
    {
        if (! $peserta->email) {
            return;
        }

        $this->kirim(
            fn () => Notification::route('mail', [$peserta->email => $peserta->nama])
                ->notify($notification),
            'pendaftar '.$peserta->kode_pendaftaran
        );
    }

    /**
     * Kegagalan kirim email tidak boleh menggagalkan pendaftaran itu sendiri —
     * datanya sudah tersimpan, jadi cukup dicatat di log.
     */
    protected function kirim(callable $aksi, string $tujuan): void
    {
        try {
            $aksi();
        } catch (Throwable $e) {
            Log::error("Gagal mengirim email pendaftaran ke {$tujuan}: ".$e->getMessage());
        }
    }

    /**
     * Petugas yang berhak meninjau pendaftaran: pemilik permission
     * peserta.approve, ditambah seluruh super admin.
     *
     * @return Collection<int, User>
     */
    protected function peninjau(): Collection
    {
        $superAdmin = config('permission.super_admin_role');

        return User::query()
            ->active()
            ->whereNotNull('email')
            ->where(function ($query) use ($superAdmin) {
                $query
                    ->whereHas('roles', fn ($r) => $r->where('name', $superAdmin))
                    ->orWhereHas('permissions', fn ($p) => $p->where('name', self::PERMISSION_PENINJAU))
                    ->orWhereHas('roles.permissions', fn ($p) => $p->where('name', self::PERMISSION_PENINJAU));
            })
            ->get();
    }
}
