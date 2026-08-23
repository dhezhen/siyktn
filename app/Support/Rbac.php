<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Pembantu untuk membaca katalog hak akses di config/rbac.php.
 */
class Rbac
{
    /**
     * Seluruh nama permission yang seharusnya ada, mis. ["user.view", ...].
     *
     * @return array<int, string>
     */
    public static function allPermissions(): array
    {
        $names = [];

        foreach (config('rbac.modules', []) as $module => $definition) {
            foreach ($definition['actions'] ?? [] as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }

    /**
     * Terjemahkan pola seperti "user.*" menjadi daftar permission sebenarnya.
     *
     * @param  array<int, string>  $patterns
     * @return array<int, string>
     */
    public static function expand(array $patterns): array
    {
        $all = static::allPermissions();
        $result = [];

        foreach ($patterns as $pattern) {
            foreach ($all as $permission) {
                if (Str::is($pattern, $permission)) {
                    $result[] = $permission;
                }
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Struktur untuk halaman matriks: grup > modul > aksi.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public static function matrix(): array
    {
        $labels = config('rbac.action_labels', []);
        $matrix = [];

        foreach (config('rbac.modules', []) as $module => $definition) {
            $group = $definition['group'] ?? 'Lainnya';
            $entries = [];

            foreach ($definition['actions'] ?? [] as $action) {
                $entries["{$module}.{$action}"] = $labels[$action] ?? Str::headline($action);
            }

            $matrix[$group][$definition['label'] ?? $module] = $entries;
        }

        return $matrix;
    }
}
