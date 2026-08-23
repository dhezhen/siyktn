<?php

/*
|--------------------------------------------------------------------------
| Katalog Hak Akses
|--------------------------------------------------------------------------
|
| Satu-satunya sumber daftar permission. PermissionSeeder membangkitkan
| permission dari sini, dan halaman matriks role mengelompokkannya dari sini
| juga. Menambah modul baru cukup menambah satu baris, lalu jalankan:
|
|     php artisan db:seed --class=PermissionSeeder
|
| Nama permission yang terbentuk: "{modul}.{aksi}", mis. "user.view".
|
*/

return [

    'action_labels' => [
        'view' => 'Lihat',
        'create' => 'Tambah',
        'update' => 'Ubah',
        'delete' => 'Hapus',
        'export' => 'Ekspor',
        'import' => 'Impor',
        'reset-password' => 'Reset Sandi',
        'approve' => 'Verifikasi',
    ],

    'modules' => [
        'user' => [
            'label' => 'Pengguna',
            'group' => 'Manajemen Pengguna',
            'actions' => ['view', 'create', 'update', 'delete', 'export', 'import', 'reset-password'],
        ],
        'role' => [
            'label' => 'Role & Hak Akses',
            'group' => 'Manajemen Pengguna',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'menu' => [
            'label' => 'Menu',
            'group' => 'Pengaturan',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'setting' => [
            'label' => 'Pengaturan Aplikasi',
            'group' => 'Pengaturan',
            'actions' => ['view', 'update'],
        ],
        'activity' => [
            'label' => 'Log Aktivitas',
            'group' => 'Pengaturan',
            'actions' => ['view'],
        ],
        'angkatan' => [
            'label' => 'Angkatan',
            'group' => 'Data Tahfidz',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'peserta' => [
            'label' => 'Peserta',
            'group' => 'Data Tahfidz',
            'actions' => ['view', 'create', 'update', 'delete', 'export', 'import', 'approve'],
        ],
    ],

    /*
     | Role bawaan beserta permission-nya.
     | super-admin sengaja dikosongkan: ia lolos lewat Gate::before.
     */
    'roles' => [
        'super-admin' => [
            'description' => 'Akses penuh ke seluruh sistem.',
            'permissions' => [],
        ],
        'admin' => [
            'description' => 'Mengelola pengguna dan seluruh data operasional.',
            'permissions' => [
                'user.*', 'role.view', 'menu.*', 'setting.*', 'activity.view',
                'angkatan.*', 'peserta.*',
            ],
        ],
        'operator' => [
            'description' => 'Menginput dan memperbarui data harian.',
            'permissions' => [
                'angkatan.view', 'peserta.view', 'peserta.create', 'peserta.update', 'peserta.export',
            ],
        ],
        'pengguna' => [
            'description' => 'Akses baca terbatas.',
            'permissions' => [
                'angkatan.view', 'peserta.view',
            ],
        ],
    ],

];
