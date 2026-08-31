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
        'view-all' => 'Lihat Semua',
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
        'program' => [
            'label' => 'Paket Program',
            'group' => 'Data Tahfidz',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'peserta' => [
            'label' => 'Peserta',
            'group' => 'Data Tahfidz',
            'actions' => ['view', 'create', 'update', 'delete', 'export', 'import', 'approve'],
        ],
        'muhaffizh' => [
            'label' => 'Muhaffizh',
            'group' => 'Data Tahfidz',
            'actions' => ['view', 'create', 'update', 'delete', 'export'],
        ],
        'halaqah' => [
            'label' => 'Halaqah',
            'group' => 'Modul Halaqah',
            'actions' => ['view', 'view-all', 'create', 'update', 'delete'],
        ],
        'setoran' => [
            'label' => 'Setoran Hafalan',
            'group' => 'Modul Halaqah',
            'actions' => ['view', 'view-all', 'create', 'update', 'delete', 'export'],
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
                'angkatan.*', 'peserta.*', 'muhaffizh.*', 'halaqah.*', 'setoran.*',
            ],
        ],
        'operator' => [
            /*
             | Operator ikut boleh mencatat setoran. Ini bukan kelonggaran:
             | sebagian muhaffizh tidak memakai aplikasi, setorannya dicatat di
             | kartu lalu dientri petugas. Tanpa izin ini, halaqah yang
             | pengampunya tidak berakun tidak akan pernah punya data.
             */
            'description' => 'Menginput dan memperbarui data harian, termasuk verifikasi kehadiran dan setoran dari kartu.',
            'permissions' => [
                'angkatan.view', 'peserta.view', 'peserta.create', 'peserta.update', 'peserta.approve', 'peserta.export',
                'muhaffizh.view', 'halaqah.view', 'halaqah.view-all',
                'setoran.view', 'setoran.view-all', 'setoran.create', 'setoran.update', 'setoran.export',
            ],
        ],
        'muhaffizh' => [
            'description' => 'Pembimbing hafalan. Mengelola halaqah yang diampu beserta setoran santrinya.',
            'permissions' => [
                'angkatan.view', 'peserta.view', 'muhaffizh.view', 'halaqah.view',
                'setoran.view', 'setoran.create', 'setoran.update', 'setoran.delete', 'setoran.export',
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
