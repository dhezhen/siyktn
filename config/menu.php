<?php

/*
|--------------------------------------------------------------------------
| Sumber Menu Sidebar (sementara)
|--------------------------------------------------------------------------
|
| Dipakai sampai Sprint 3. Bentuk arraynya sengaja disamakan dengan kolom
| tabel `menus` yang dibuat di Sprint 4, sehingga nanti cukup mengganti
| sumber data di App\Support\Menu tanpa menyentuh view sidebar.
|
| type        : route | url | header | divider
| route       : nama route (bukan URL). Menu otomatis disembunyikan bila
|               route-nya belum ada, jadi item di bawah boleh didaftarkan
|               lebih dulu sebelum modulnya dibangun.
| permission  : null = tampil untuk semua; selain itu dicek ke user aktif.
|
*/

return [

    'items' => [
        [
            'title' => 'Dashboard',
            'icon' => 'squares',
            'type' => 'route',
            'route' => 'dashboard',
            'permission' => null,
        ],

        [
            'title' => 'Manajemen Pengguna',
            'type' => 'header',
        ],
        [
            'title' => 'Pengguna',
            'icon' => 'users',
            'type' => 'route',
            'route' => 'user.index',
            'permission' => 'user.view',
        ],
        [
            'title' => 'Role & Hak Akses',
            'icon' => 'shield',
            'type' => 'route',
            'route' => 'role.index',
            'permission' => 'role.view',
        ],

        [
            'title' => 'Pengaturan',
            'type' => 'header',
        ],
        [
            'title' => 'Menu',
            'icon' => 'list',
            'type' => 'route',
            'route' => 'menu.index',
            'permission' => 'menu.view',
        ],
        [
            'title' => 'Aplikasi',
            'icon' => 'cog',
            'type' => 'route',
            'route' => 'setting.index',
            'permission' => 'setting.view',
        ],
    ],

];
