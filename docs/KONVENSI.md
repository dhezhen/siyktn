# Konvensi Pengembangan SI YKTN

Dokumen singkat supaya setiap modul baru terasa konsisten dengan modul sebelumnya.

## Stack

Laravel 13 · Livewire 4 · Tailwind CSS 4 · spatie/laravel-permission ·
spatie/laravel-activitylog · MySQL (Laragon, **port 3307**).

## Penamaan

| Objek | Pola | Contoh |
|---|---|---|
| Tabel | bahasa Indonesia dibiarkan tunggal, bahasa Inggris dijamakkan | `angkatan`, `peserta`, `users`, `menus` |
| Model | tunggal, StudlyCase, `$table` diisi bila tidak cocok otomatis | `Angkatan`, `Peserta`, `User` |
| Controller | `{Model}Controller` | `PesertaController` |
| Route name | `{modul}.{aksi}` | `peserta.index`, `peserta.store` |
| Permission | `{modul}.{aksi}` | `peserta.view`, `peserta.delete` |
| View | `{modul}/{aksi}.blade.php` | `peserta/index.blade.php` |
| Komponen Livewire | `{Modul}{Peran}` | `UserTable`, `PesertaTable`, `MenuManager` |

Aksi baku: `view`, `create`, `update`, `delete`.
Aksi tambahan bila modulnya butuh: `export`, `import`, `reset-password`, `approve`, `verify`, `print`.

Nama permission **harus** sama persis di tiga tempat: `config/rbac.php`,
middleware `permission:`/`@can` di kode, dan kolom `permission` pada tabel `menus`.

## Menambah modul baru

1. Tambahkan modulnya ke `config/rbac.php` (`modules` dan, bila perlu, `roles`).
2. `php artisan db:seed --class=PermissionSeeder` lalu `--class=RoleSeeder`.
3. Buat migration, model (pakai trait `RecordsActivity`), controller, dan view.
4. Daftarkan route dengan nama `{modul}.{aksi}` dan middleware `permission:{modul}.{aksi}`.
5. Tambahkan menunya lewat halaman **Pengaturan → Menu** (tidak perlu ubah kode).

## Hak akses

- Role `super-admin` otomatis lolos semua pengecekan lewat `Gate::before` di `AppServiceProvider`.
  Jangan memberi permission satu per satu ke role ini.
- Pemeriksaan memakai middleware `permission:` (lihat `HasMiddleware` di tiap controller)
  dan `@can` di Blade — **bukan** `if ($user->role == 'admin')`.
- Satu user boleh punya lebih dari satu role.
- Akun `super-admin` hanya bisa dikelola sesama super admin.

## Menu

- Sumber menu ada di tabel `menus`, diakses lewat `App\Support\Menu::items()`.
- Menu memakai **nama route**, bukan URL. Menu yang route-nya belum ada otomatis
  disembunyikan, jadi menu boleh didaftarkan sebelum modulnya jadi.
- Menu hanya tampil bila `permission`-nya `null` atau dimiliki user aktif.
- Cache menu di-flush otomatis setiap tabel `menus` berubah (lihat `Menu::booted()`).

## Data

- Data yang punya relasi ke transaksi memakai `SoftDeletes`, bukan hapus permanen.
- Status aktif/nonaktif memakai kolom `is_active` atau `status`, bukan menghapus baris.
- Setiap tabel utama punya seeder agar `php artisan migrate:fresh --seed` selalu
  menghasilkan sistem yang siap dipakai.
- Perubahan data penting dicatat otomatis lewat trait `App\Models\Concerns\RecordsActivity`.

## UI

Komponen Blade di `resources/views/components`:

`<x-page-header>` `<x-card>` `<x-button>` `<x-input>` `<x-select>` `<x-badge>`
`<x-alert>` `<x-modal>` `<x-confirm-delete>` `<x-empty-state>` `<x-icon>`

- Layout halaman: `<x-layouts::app>` (dalam sistem) dan `<x-layouts::guest>` (login dsb).
- Pesan sukses/gagal lewat `session()->flash('success'|'error'|'warning'|'info', ...)`,
  otomatis dirender `<x-alert>` di layout.
- Konfirmasi hapus memakai `<x-confirm-delete :action="..." />`, bukan `confirm()` browser.
- Tabel besar memakai komponen Livewire dengan paginasi server-side, bukan render semua baris.

## Alur kerja

```bash
php artisan migrate:fresh --seed   # bangun ulang database beserta data contoh
npm run dev                        # Vite, wajib jalan saat mengembangkan tampilan
php artisan serve                  # atau langsung buka http://siyktn.test (Laragon)
```

Akun bawaan (kata sandi semua: `password`):

| Username | Role | Untuk |
|---|---|---|
| `superadmin` | super-admin | akses penuh |
| `admin` | admin | mengelola pengguna & data |
| `operator` | operator | input data harian |

**Ganti kata sandi ketiga akun ini sebelum sistem dipakai sungguhan.**

## Status pengerjaan

| Sprint | Isi | Status |
|---|---|---|
| 0 | Fondasi: Laravel, layout, komponen, konvensi | ✅ |
| 1 | Autentikasi, profil, reset & ganti kata sandi | ✅ |
| 2 | Role & permission, matriks hak akses | ✅ |
| 3 | CRUD pengguna, ekspor/impor CSV | ✅ |
| 4 | Manajemen menu dinamis (drag & drop) | ✅ |
| 5 | Log aktivitas, pengaturan aplikasi, dashboard | ✅ |
| 6 | Modul pertama: Angkatan & Peserta | ✅ |

### Belum dikerjakan (kandidat sprint berikutnya)

- Impor CSV untuk peserta (ekspor sudah ada).
- Cetak PDF (kartu peserta, rekap angkatan) — perlu `barryvdh/laravel-dompdf`.
- 2FA dan riwayat login per perangkat.
- Backup database terjadwal (`spatie/laravel-backup`).
- Modul lanjutan domain tahfidz: muhaffizh, hafalan, pembayaran, syahadah.
- Automated test (belum ada satu pun test ditulis).
