<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Menu awal sistem. Item yang route-nya belum dibuat tetap aman
     * didaftarkan — sidebar otomatis menyembunyikannya.
     */
    public function run(): void
    {
        $menus = [
            ['title' => 'Dashboard', 'icon' => 'squares', 'type' => 'route', 'route' => 'dashboard'],

            ['title' => 'Manajemen Pengguna', 'type' => 'header', 'children' => [
                ['title' => 'Pengguna', 'icon' => 'users', 'type' => 'route', 'route' => 'user.index', 'permission' => 'user.view'],
                ['title' => 'Role & Hak Akses', 'icon' => 'shield', 'type' => 'route', 'route' => 'role.index', 'permission' => 'role.view'],
            ]],

            ['title' => 'Data Tahfidz', 'type' => 'header', 'children' => [
                ['title' => 'Angkatan', 'icon' => 'list', 'type' => 'route', 'route' => 'angkatan.index', 'permission' => 'angkatan.view'],
                ['title' => 'Peserta', 'icon' => 'users', 'type' => 'route', 'route' => 'peserta.index', 'permission' => 'peserta.view'],
            ]],

            ['title' => 'Pengaturan', 'type' => 'header', 'children' => [
                ['title' => 'Menu', 'icon' => 'list', 'type' => 'route', 'route' => 'menu.index', 'permission' => 'menu.view'],
                ['title' => 'Aplikasi', 'icon' => 'cog', 'type' => 'route', 'route' => 'setting.edit', 'permission' => 'setting.view'],
                ['title' => 'Log Aktivitas', 'icon' => 'info', 'type' => 'route', 'route' => 'activity.index', 'permission' => 'activity.view'],
            ]],
        ];

        // Header dipakai sebagai judul kelompok: anaknya ikut jadi menu level atas,
        // persis seperti yang dirender sidebar.
        $order = 0;

        foreach ($menus as $item) {
            $children = $item['children'] ?? [];
            unset($item['children']);

            $this->store($item, $order++);

            foreach ($children as $child) {
                $this->store($child, $order++);
            }
        }

        Menu::flushCache();

        $this->command?->info(Menu::count().' menu tersedia.');
    }

    protected function store(array $attributes, int $order): void
    {
        Menu::updateOrCreate(
            [
                'title' => $attributes['title'],
                'parent_id' => $attributes['parent_id'] ?? null,
            ],
            array_merge($attributes, ['order' => $order, 'is_active' => true])
        );
    }
}
