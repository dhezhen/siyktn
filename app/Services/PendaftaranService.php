<?php

namespace App\Services;

use App\Models\Angkatan;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\User;
use App\Notifications\PendaftaranBaruMasuk;
use App\Notifications\PendaftaranDisetujui;
use App\Notifications\PendaftaranDiterima;
use App\Notifications\PendaftaranDitolak;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Satu tempat untuk seluruh alur pendaftaran.
 *
 * Dipakai bersama oleh formulir pendaftaran mandiri (publik) dan oleh petugas
 * yang menginput dari dalam sistem, supaya aturan penomoran, pengenalan
 * pendaftar lama, dan pengiriman email tidak ditulis dua kali.
 */
class PendaftaranService
{
    protected const PERMISSION_PENINJAU = 'peserta.approve';

    /**
     * Simpan berkas KTP ke disk privat.
     *
     * KTP adalah data pribadi, jadi TIDAK disimpan di disk `public`.
     * Berkasnya hanya bisa dibuka lewat route yang dijaga permission.
     */
    public function simpanKtp(UploadedFile $file): string
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
     * Bila NIK-nya sudah dikenal, baris peserta yang lama dipakai ulang dan
     * datanya diperbarui — sehingga alumni tidak menghasilkan orang kembar.
     *
     * @param  array<string, mixed>  $dataPeserta
     * @param  array<string, mixed>  $dataPendaftaran
     */
    public function daftarkan(array $dataPeserta, array $dataPendaftaran, string $sumber = 'mandiri'): Pendaftaran
    {
        return DB::transaction(function () use ($dataPeserta, $dataPendaftaran, $sumber) {
            $peserta = Peserta::cariBerdasarkanNik($dataPeserta['nik'] ?? null);

            if ($peserta) {
                // Berkas lama dipertahankan bila kali ini tidak mengunggah ulang.
                $peserta->fill(array_filter(
                    $dataPeserta,
                    fn ($nilai, $kolom) => ! in_array($kolom, ['foto', 'ktp_path'], true) || $nilai !== null,
                    ARRAY_FILTER_USE_BOTH
                ))->save();
            } else {
                $peserta = Peserta::create($dataPeserta);
            }

            $pendaftaran = Pendaftaran::create(array_merge($dataPendaftaran, [
                'peserta_id' => $peserta->id,
                'kode_pendaftaran' => Pendaftaran::kodePendaftaranBerikutnya(),
                'sumber_pendaftaran' => $sumber,
                'didaftarkan_pada' => now(),
            ]));

            $this->kirimNotifikasiPendaftaranBaru($pendaftaran);

            return $pendaftaran;
        });
    }

    /**
     * Setujui pendaftaran: beri nomor induk, aktifkan, lalu kabari pendaftar.
     */
    public function setujui(Pendaftaran $pendaftaran, User $peninjau): Pendaftaran
    {
        if (! $pendaftaran->isMenunggu()) {
            return $pendaftaran;
        }

        $pendaftaran->forceFill([
            'nomor_induk' => $pendaftaran->nomor_induk
                ?: Pendaftaran::nomorIndukBerikutnya($pendaftaran->angkatan),
            'status_pendaftaran' => 'disetujui',
            'status' => 'aktif',
            'tanggal_masuk' => $pendaftaran->tanggal_masuk ?? now()->toDateString(),
            'ditinjau_pada' => now(),
            'ditinjau_oleh' => $peninjau->id,
            'alasan_penolakan' => null,
        ])->save();

        $this->kirimKePendaftar(
            $pendaftaran,
            new PendaftaranDisetujui($pendaftaran->fresh(['peserta', 'angkatan']))
        );

        return $pendaftaran;
    }

    /**
     * Tolak pendaftaran dengan alasan, lalu kabari pendaftar.
     */
    public function tolak(Pendaftaran $pendaftaran, User $peninjau, string $alasan): Pendaftaran
    {
        if (! $pendaftaran->isMenunggu()) {
            return $pendaftaran;
        }

        $pendaftaran->forceFill([
            'status_pendaftaran' => 'ditolak',
            'status' => 'keluar',
            'ditinjau_pada' => now(),
            'ditinjau_oleh' => $peninjau->id,
            'alasan_penolakan' => $alasan,
        ])->save();

        $this->kirimKePendaftar($pendaftaran, new PendaftaranDitolak($pendaftaran->load('peserta')));

        return $pendaftaran;
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
            ->filter(function (Angkatan $a) {
                $putraOpen = $a->sisa_kuota_putra === null || $a->sisa_kuota_putra > 0;
                $putriOpen = $a->sisa_kuota_putri === null || $a->sisa_kuota_putri > 0;
                $totalOpen = $a->sisa_kuota === null || $a->sisa_kuota > 0;

                return $totalOpen && ($putraOpen || $putriOpen);
            })
            ->values();
    }

    /**
     * Email ke pendaftar (bila mengisi email) dan ke seluruh peninjau.
     */
    public function kirimNotifikasiPendaftaranBaru(Pendaftaran $pendaftaran): void
    {
        $pendaftaran->loadMissing(['peserta', 'angkatan']);

        $this->kirimKePendaftar($pendaftaran, new PendaftaranDiterima($pendaftaran));

        $peninjau = $this->peninjau();

        if ($peninjau->isNotEmpty()) {
            $this->kirim(
                fn () => Notification::send($peninjau, new PendaftaranBaruMasuk($pendaftaran)),
                'peninjau pendaftaran'
            );
        }

        // Salinan ke email resmi lembaga, bila diisi di Pengaturan Aplikasi.
        $emailLembaga = setting('email');

        if ($emailLembaga) {
            $this->kirim(
                fn () => Notification::route('mail', $emailLembaga)
                    ->notify(new PendaftaranBaruMasuk($pendaftaran)),
                'email lembaga'
            );
        }
    }

    protected function kirimKePendaftar(Pendaftaran $pendaftaran, $notification): void
    {
        $email = $pendaftaran->peserta?->email;

        if (! $email) {
            return;
        }

        $this->kirim(
            fn () => Notification::route('mail', [$email => $pendaftaran->peserta->nama])
                ->notify($notification),
            'pendaftar '.$pendaftaran->kode_pendaftaran
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
