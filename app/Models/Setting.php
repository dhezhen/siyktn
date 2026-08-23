<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pengaturan aplikasi (key-value) yang bisa diubah lewat UI.
 *
 * Seluruh baris di-cache sekali, jadi memanggil setting() berkali-kali
 * dalam satu request tidak menambah query.
 */
class Setting extends Model
{
    use RecordsActivity;

    protected array $activityFields = ['value'];

    protected string $activityLabel = 'Pengaturan';

    public const CACHE_KEY = 'settings.all';

    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all_cached()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::flush();
    }

    public static function flush(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public static function all_cached(): array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return Cache::rememberForever(
                static::CACHE_KEY,
                fn () => static::query()->pluck('value', 'key')->all()
            );
        } catch (Throwable) {
            // Database belum siap (mis. saat `migrate` pertama kali dijalankan).
            return [];
        }
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }
}
