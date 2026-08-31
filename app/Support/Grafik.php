<?php

namespace App\Support;

/**
 * Parameter grafik: palet, skala, dan pemformatan angka.
 *
 * Warnanya bukan pilihan selera — seluruh set di bawah sudah diuji terhadap
 * permukaan kartu (#ffffff) untuk jarak warna pada penglihatan normal maupun
 * buta warna, lalu dicatat hasilnya di sini. Jangan mengganti hex-nya tanpa
 * menguji ulang; kombinasi yang "kelihatan beda" sering kali tidak.
 *
 * Catatan uji (mode terang, permukaan #ffffff):
 *
 *   Ziyadah + Muraja'ah  #059669 / #c2410c
 *     ΔE buta warna 9,4 (deutan) · penglihatan normal 27,1 · kontras ≥ 3:1 — LOLOS
 *
 *   Sebaran kualitas     #047857 / #10b981 / #f59e0b / #e11d48
 *     ΔE buta warna 8,9 · penglihatan normal 19,3 — LOLOS
 *     Dua warna tengah berada di bawah kontras 3:1, sehingga angkanya WAJIB
 *     ditulis (legenda + label), tidak boleh mengandalkan warna saja.
 *
 * Yang GAGAL dan sengaja tidak dipakai: emerald + sky (#047857 / #0369a1),
 * ΔE penglihatan normal hanya 14,0 — di bawah ambang 15. Pasangan ini terasa
 * wajar karena badge aplikasi memakai emerald dan sky, tetapi sebagai dua garis
 * di satu grafik keduanya sulit dibedakan.
 */
class Grafik
{
    /** Hafalan baru — memakai warna utama aplikasi. */
    public const ZIYADAH = '#059669';

    /** Mengulang hafalan lama. */
    public const MURAJAAH = '#c2410c';

    /** Satu warna untuk seluruh batang: panjangnya yang mengukur, bukan warnanya. */
    public const BATANG = '#059669';

    /**
     * Sebaran kualitas, berurut dari terbaik ke yang perlu perhatian.
     *
     * @var array<string, string>
     */
    public const KUALITAS = [
        'mumtaz' => '#047857',
        'jayyid' => '#10b981',
        'maqbul' => '#f59e0b',
        'perlu_diulang' => '#e11d48',
    ];

    // Kroma rendah, sengaja tidak menuntut perhatian.
    public const GARIS_BANTU = '#e2e8f0';

    public const SUMBU = '#cbd5e1';

    public const TINTA_REDUP = '#64748b';

    public const PERMUKAAN = '#ffffff';

    /**
     * Batas atas sumbu yang bulat, mis. 7,5 menjadi 10.
     */
    public static function batasAtas(float $maks): float
    {
        if ($maks <= 0) {
            return 1;
        }

        $magnitudo = 10 ** floor(log10($maks));

        foreach ([1, 2, 2.5, 5, 10] as $kelipatan) {
            if ($kelipatan * $magnitudo >= $maks) {
                return $kelipatan * $magnitudo;
            }
        }

        return 10 * $magnitudo;
    }

    /**
     * Nilai-nilai tanda pada sumbu, dari 0 sampai batas atas.
     *
     * @return array<int, float>
     */
    public static function tanda(float $batas, int $jumlah = 4): array
    {
        return array_map(fn ($i) => $batas / $jumlah * $i, range(0, $jumlah));
    }

    /**
     * Angka gaya Indonesia tanpa nol di belakang koma: 1,5 · 12 · 1.240.
     */
    public static function angka(float $nilai, int $desimal = 1): string
    {
        $teks = number_format($nilai, $desimal, ',', '.');

        return str_contains($teks, ',') ? rtrim(rtrim($teks, '0'), ',') : $teks;
    }

    /**
     * Jalur batang mendatar dengan ujung data membulat 4px dan pangkal siku
     * di garis dasar.
     */
    public static function jalurBatang(float $x0, float $y, float $x1, float $tinggi, float $r = 4): string
    {
        $lebar = $x1 - $x0;

        // Batang yang lebih pendek dari radiusnya digambar siku, agar
        // lengkungnya tidak melipat balik dan menyesatkan panjangnya.
        if ($lebar <= $r) {
            return sprintf('M%.2f,%.2f h%.2f v%.2f h%.2f Z', $x0, $y, max($lebar, 0.5), $tinggi, -max($lebar, 0.5));
        }

        return sprintf(
            'M%.2f,%.2f H%.2f A%.2f,%.2f 0 0 1 %.2f,%.2f V%.2f A%.2f,%.2f 0 0 1 %.2f,%.2f H%.2f Z',
            $x0, $y,
            $x1 - $r,
            $r, $r, $x1, $y + $r,
            $y + $tinggi - $r,
            $r, $r, $x1 - $r, $y + $tinggi,
            $x0
        );
    }
}
