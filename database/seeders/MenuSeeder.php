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

            ['title' => 'Dashboard Pimpinan', 'icon' => 'chart-bar', 'type' => 'route', 'route' => 'pimpinan.index', 'permission' => 'pimpinan.view'],

            ['title' => 'Manajemen Pengguna', 'icon' => 'users', 'type' => 'header', 'children' => [
                ['title' => 'Pengguna', 'icon' => 'users', 'type' => 'route', 'route' => 'user.index', 'permission' => 'user.view'],
                ['title' => 'Role & Hak Akses', 'icon' => 'shield', 'type' => 'route', 'route' => 'role.index', 'permission' => 'role.view'],
            ]],

            ['title' => 'Data Tahfidz', 'icon' => 'identification', 'type' => 'header', 'children' => [
                ['title' => 'Pendaftaran', 'icon' => 'info', 'type' => 'route', 'route' => 'pendaftaran.index', 'permission' => 'peserta.approve'],
                ['title' => 'Presensi Kehadiran', 'icon' => 'camera', 'type' => 'route', 'route' => 'pendaftaran.presensi', 'permission' => 'peserta.approve'],
                ['title' => 'Angkatan', 'icon' => 'list', 'type' => 'route', 'route' => 'angkatan.index', 'permission' => 'angkatan.view'],
                ['title' => 'Paket Program', 'icon' => 'document-text', 'type' => 'route', 'route' => 'program.index', 'permission' => 'program.view'],
                ['title' => 'Peserta', 'icon' => 'users', 'type' => 'route', 'route' => 'peserta.index', 'permission' => 'peserta.view'],
                ['title' => 'Muhaffizh', 'icon' => 'academic', 'type' => 'route', 'route' => 'muhaffizh.index', 'permission' => 'muhaffizh.view'],
            ]],

            ['title' => 'Modul Halaqah', 'icon' => 'academic', 'type' => 'header', 'children' => [
                ['title' => 'Halaqah', 'icon' => 'book', 'type' => 'route', 'route' => 'halaqah.index', 'permission' => 'halaqah.view'],
                ['title' => 'Setoran Hafalan', 'icon' => 'check-circle', 'type' => 'route', 'route' => 'setoran.index', 'permission' => 'setoran.view'],
            ]],

            ['title' => 'Keuangan', 'icon' => 'currency-dollar', 'type' => 'header', 'children' => [
                ['title' => 'Rekap Pendaftaran', 'icon' => 'document-text', 'type' => 'route', 'route' => 'keuangan.index', 'permission' => 'keuangan.view'],
            ]],

            ['title' => 'Pengaturan', 'icon' => 'cog', 'type' => 'header', 'children' => [
                ['title' => 'Menu', 'icon' => 'list', 'type' => 'route', 'route' => 'menu.index', 'permission' => 'menu.view'],
                ['title' => 'Aplikasi', 'icon' => 'cog', 'type' => 'route', 'route' => 'setting.edit', 'permission' => 'setting.view'],
                ['title' => 'Log Aktivitas', 'icon' => 'info', 'type' => 'route', 'route' => 'activity.index', 'permission' => 'activity.view'],
            ]],
        ];

        $this->simpanBanyak($menus, null);

        Menu::flushCache();

        $this->command?->info(Menu::count().' menu tersedia.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function simpanBanyak(array $items, ?int $parentId): void
    {
        foreach ($items as $item) {
            $children = $item['children'] ?? [];
            unset($item['children']);

            $menu = $this->store($item, $parentId);

            if ($children !== []) {
                $this->simpanBanyak($children, $menu->id);
            }
        }
    }

    /**
     * Tambahkan menu bila belum ada, dan JANGAN sentuh yang sudah ada.
     *
     * Identitas menu diambil dari nama route (untuk menu tautan) atau judul+tipe
     * (untuk header/divider) — bukan dari parent_id atau order, karena keduanya
     * memang dirancang untuk diubah petugas lewat halaman Manajemen Menu.
     * Seeder yang menimpa susunan itu akan menghapus pekerjaan orang lain.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function store(array $attributes, ?int $parentId): Menu
    {
        $type = $attributes['type'] ?? 'route';

        $kunci = in_array($type, ['header', 'divider'], true)
            ? ['title' => $attributes['title'], 'type' => $type]
            : ['route' => $attributes['route']];

        if ($menu = Menu::where($kunci)->first()) {
            return $menu;
        }

        /*
         | Urutan dihitung dari isi kelompoknya sendiri, bukan dari penghitung
         | global. Dulu nomornya diambil dari urutan berjalan seeder, sehingga
         | pada database yang sudah terisi menu baru bisa mendarat di tengah
         | kelompok lain — "Muhaffizh" sempat nyangkut di bawah "Pengaturan"
         | karena itu. Menu baru sekarang selalu ditaruh di akhir kelompoknya.
         */
        $order = (Menu::where('parent_id', $parentId)->max('order') ?? -1) + 1;

        return Menu::create(array_merge($attributes, [
            'parent_id' => $parentId,
            'order' => $order,
            'is_active' => true,
        ]));
    }
}
