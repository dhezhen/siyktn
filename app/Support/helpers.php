<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Ambil satu nilai pengaturan aplikasi.
     *
     * Aman dipanggil sebelum tabel `settings` ada (mis. saat migrate awal),
     * nilai default yang dikembalikan.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}
