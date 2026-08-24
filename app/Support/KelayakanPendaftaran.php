<?php

namespace App\Support;

use App\Models\Peserta;

/**
 * Menjawab satu pertanyaan: NIK ini boleh mendaftar ke angkatan itu atau tidak?
 *
 * Aturannya sengaja dikumpulkan di satu kelas supaya formulir publik dan
 * input petugas memakai keputusan yang sama persis.
 */
class KelayakanPendaftaran
{
    public function __construct(
        public bool $boleh,
        public string $alasan = '',
        public ?Peserta $peserta = null,
        public bool $pendaftaranUlang = false,
    ) {}

    public static function periksa(?string $nik, int $angkatanId): self
    {
        $peserta = Peserta::cariBerdasarkanNik($nik);

        // Belum pernah tercatat: pendaftar baru.
        if (! $peserta) {
            return new self(boleh: true);
        }

        if (! $peserta->boleh_mendaftar_lagi) {
            return new self(
                boleh: false,
                alasan: 'Pendaftaran atas NIK ini tidak dapat diproses. Silakan hubungi pihak lembaga.',
                peserta: $peserta,
            );
        }

        $pendaftaran = $peserta->pendaftaran()->get();

        if ($pendaftaran->firstWhere('angkatan_id', $angkatanId)) {
            return new self(
                boleh: false,
                alasan: 'Anda sudah terdaftar di angkatan ini.',
                peserta: $peserta,
            );
        }

        if ($pendaftaran->firstWhere('status_pendaftaran', 'menunggu')) {
            return new self(
                boleh: false,
                alasan: 'Pendaftaran Anda sebelumnya masih dalam proses verifikasi. '
                    .'Mohon tunggu hasilnya sebelum mendaftar lagi.',
                peserta: $peserta,
            );
        }

        $masihAktif = $pendaftaran->first(
            fn ($p) => $p->status_pendaftaran === 'disetujui' && $p->status === 'aktif'
        );

        if ($masihAktif) {
            return new self(
                boleh: false,
                alasan: 'Anda masih tercatat aktif di angkatan berjalan, '
                    .'sehingga belum dapat mendaftar ke angkatan lain.',
                peserta: $peserta,
            );
        }

        // Sisanya — alumni (lulus), pernah keluar, atau pernah ditolak — boleh
        // mendaftar lagi. Datanya dipakai ulang, tidak perlu diketik dari awal.
        return new self(boleh: true, peserta: $peserta, pendaftaranUlang: true);
    }
}
