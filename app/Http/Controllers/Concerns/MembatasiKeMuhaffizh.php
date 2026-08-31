<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Pembatasan data untuk muhaffizh: ia hanya melihat halaqah asuhannya sendiri.
 *
 * Batasnya ditentukan permission "{modul}.view-all", bukan nama role. Admin dan
 * operator memilikinya sehingga melihat seluruh data — operator memang perlu,
 * karena ia yang mengentri kartu setoran milik muhaffizh yang tidak berakun.
 * Muhaffizh tidak memilikinya, jadi datanya dipersempit ke dirinya sendiri.
 */
trait MembatasiKeMuhaffizh
{
    /**
     * Id muhaffizh yang boleh dilihat user ini, atau null bila boleh semuanya.
     *
     * Mengembalikan 0 bila user tidak berhak melihat seluruh data dan juga
     * bukan seorang muhaffizh — id itu tidak pernah cocok dengan baris mana
     * pun, sehingga hasilnya kosong. Itu memang jawaban yang benar: ia tidak
     * punya halaqah untuk dilihat.
     */
    protected function lingkupMuhaffizh(string $izinLihatSemua): ?int
    {
        $user = Auth::user();

        if ($user->can($izinLihatSemua)) {
            return null;
        }

        return $user->muhaffizh?->id ?? 0;
    }

    /**
     * Apakah user ini sedang dibatasi ke halaqahnya sendiri?
     */
    protected function dibatasi(string $izinLihatSemua): bool
    {
        return $this->lingkupMuhaffizh($izinLihatSemua) !== null;
    }
}
