<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Penyedia data menu sidebar.
 *
 * Sampai Sprint 3 sumbernya config/menu.php. Di Sprint 4 method items()
 * diganti menjadi query ke tabel `menus` (plus cache per user) tanpa
 * mengubah view yang memakainya.
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
        return static::filter(config('menu.items', []));
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

        // user.index ikut aktif saat berada di user.create, user.edit, dst.
        $pattern = Str::contains($route, '.')
            ? Str::beforeLast($route, '.').'.*'
            : $route;

        return request()->routeIs($pattern);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected static function filter(array $items): array
    {
        $visible = [];

        foreach ($items as $item) {
            $item['children'] = static::filter($item['children'] ?? []);

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

            if ($type === 'header' && ! static::hasEntryAfter($items, $index)) {
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
