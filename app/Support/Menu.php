<?php

namespace App\Support;

use App\Models\Menu as MenuModel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Penyedia data menu sidebar.
 *
 * Sumbernya tabel `menus` (lihat App\Models\Menu::tree(), sudah di-cache).
 * Kelas ini hanya menyaring: menu disembunyikan bila route-nya belum ada
 * atau permission-nya tidak dimiliki user yang sedang login.
 */
class Menu
{
    /**
     * Item menu yang boleh dilihat user saat ini.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function items(): array
    {
        return static::filter(MenuModel::tree());
    }

    /**
     * URL tujuan sebuah item menu.
     */
    public static function url(array $item): string
    {
        if (($item['type'] ?? 'route') === 'url') {
            return $item['url'] ?? '#';
        }

        return route($item['route']);
    }

    /**
     * Apakah item (atau salah satu anaknya) sedang dibuka.
     */
    public static function isActive(array $item): bool
    {
        foreach ($item['children'] ?? [] as $child) {
            if (static::isActive($child)) {
                return true;
            }
        }

        $route = $item['route'] ?? null;

        if ($route === null) {
            return false;
        }

        if (request()->routeIs($route)) {
            return true;
        }

        // user.index ikut aktif saat berada di user.create, user.edit, dst.
        // Kita batasi wildcard hanya untuk suffix resource standar Laravel 
        // agar tidak bentrok dengan menu lain yang berbagi prefix yang sama
        // (seperti pendaftaran.index dan pendaftaran.presensi).
        if (Str::endsWith($route, '.index')) {
            $base = Str::beforeLast($route, '.');
            
            $patterns = [
                $base . '.create',
                $base . '.edit',
                $base . '.show',
                $base . '.import.form', // khusus form impor
            ];

            return request()->routeIs($patterns);
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected static function filter(array $items): array
    {
        $visible = [];

        foreach ($items as $item) {
            $mengelompokkan = ($item['children'] ?? []) !== [];

            $item['children'] = static::filter($item['children'] ?? []);

            // Header yang menampung menu lain ikut hilang begitu seluruh isinya
            // tidak boleh dilihat user ini — jangan sampai tersisa judul kosong.
            if (($item['type'] ?? 'route') === 'header' && $mengelompokkan && $item['children'] === []) {
                continue;
            }

            if (static::allowed($item)) {
                $visible[] = $item;
            }
        }

        return static::tidy($visible);
    }

    protected static function allowed(array $item): bool
    {
        $type = $item['type'] ?? 'route';

        if (in_array($type, ['header', 'divider'], true)) {
            return true;
        }

        // Induk tetap tampil selama masih menyisakan anak yang boleh dilihat.
        if ($item['children'] !== []) {
            return true;
        }

        if ($type === 'route' && ! Route::has($item['route'] ?? '')) {
            return false;
        }

        $permission = $item['permission'] ?? null;

        if ($permission === null) {
            return true;
        }

        return auth()->user()?->can($permission) ?? false;
    }

    /**
     * Buang header yang tidak lagi punya isi, serta divider yang menggantung.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected static function tidy(array $items): array
    {
        $result = [];

        foreach ($items as $index => $item) {
            $type = $item['type'] ?? 'route';

            // Header yang isinya menjadi anaknya sendiri sudah lolos seleksi di
            // filter(); yang diperiksa di sini hanya header gaya lama, yang
            // "memiliki" menu sesudahnya tanpa hubungan induk-anak.
            if ($type === 'header' && $item['children'] === [] && ! static::hasEntryAfter($items, $index)) {
                continue;
            }

            if ($type === 'divider' && ($result === [] || (end($result)['type'] ?? '') === 'divider')) {
                continue;
            }

            $result[] = $item;
        }

        while ($result !== [] && (end($result)['type'] ?? '') === 'divider') {
            array_pop($result);
        }

        return array_values($result);
    }

    /**
     * Adakah menu sungguhan setelah posisi ini, sebelum header berikutnya?
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected static function hasEntryAfter(array $items, int $index): bool
    {
        foreach (array_slice($items, $index + 1) as $item) {
            $type = $item['type'] ?? 'route';

            if ($type === 'header') {
                return false;
            }

            if ($type !== 'divider') {
                return true;
            }
        }

        return false;
    }
}
