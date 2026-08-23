<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    public const CACHE_KEY = 'menus.tree';

    protected $fillable = [
        'parent_id', 'title', 'icon', 'type', 'route', 'url',
        'target', 'permission', 'order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Seluruh menu aktif dalam bentuk array bertingkat, di-cache.
     *
     * Sengaja tidak menyaring permission di sini: hasil cache dipakai bersama
     * semua user, penyaringan hak akses dilakukan di App\Support\Menu.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function tree(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $all = self::query()->active()->orderBy('order')->orderBy('id')->get();

            return self::buildTree($all);
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Apakah $candidate merupakan turunan dari $menu?
     * Dipakai untuk mencegah menu dijadikan anak dari dirinya sendiri.
     */
    public function isAncestorOf(Menu $candidate): bool
    {
        $parent = $candidate->parent;

        while ($parent) {
            if ($parent->id === $this->id) {
                return true;
            }

            $parent = $parent->parent;
        }

        return false;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Menu>  $items
     * @return array<int, array<string, mixed>>
     */
    protected static function buildTree($items, ?int $parentId = null): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->map(fn (Menu $menu) => [
                'id' => $menu->id,
                'title' => $menu->title,
                'icon' => $menu->icon,
                'type' => $menu->type,
                'route' => $menu->route,
                'url' => $menu->url,
                'target' => $menu->target,
                'permission' => $menu->permission,
                'children' => self::buildTree($items, $menu->id),
            ])
            ->values()
            ->all();
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }
}
