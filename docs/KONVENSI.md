# Konvensi Pengembangan SI YKTN

Dokumen singkat supaya setiap modul baru terasa konsisten dengan modul sebelumnya.

## Penamaan

| Objek | Pola | Contoh |
|---|---|---|
| Tabel | jamak, snake_case | `users`, `menus`, `pesertas` |
| Model | tunggal, StudlyCase | `User`, `Menu`, `Peserta` |
| Controller | `{Model}Controller` | `UserController` |
| Route name | `{modul}.{aksi}` | `user.index`, `user.store` |
| Permission | `{modul}.{aksi}` | `user.view`, `user.delete` |
| View | `{modul}/{aksi}.blade.php` | `user/index.blade.php` |
| Komponen Livewire | `{Modul}{Peran}` | `UserTable`, `MenuTree` |

Aksi baku: `view`, `create`, `update`, `delete`.
Aksi tambahan dipakai bila modulnya butuh: `export`, `import`, `approve`, `verify`, `print`.

Nama permission **harus** sama dengan yang dipakai di:
- `can:` pada route/middleware,
- `@can` pada Blade,
- kolom `permission` pada tabel `menus`.

## Hak akses

- Role `super-admin` otomatis lolos semua pengecekan lewat `Gate::before` di `AppServiceProvider`.
  Jangan memberi permission satu per satu ke role ini.
- Pemeriksaan di controller memakai `authorize()` atau middleware `can:`, **bukan** `if ($user->role == 'admin')`.
- Satu user boleh punya lebih dari satu role (dukungan bawaan spatie/laravel-permission).

## Menu

- Sumber menu ada di tabel `menus`, diakses lewat `App\Support\Menu::items()`.
- Menu memakai **nama route**, bukan URL. Menu yang route-nya belum ada otomatis disembunyikan,
  jadi menu boleh didaftarkan lebih dulu sebelum modulnya jadi.
- Menu hanya tampil bila `permission`-nya `null` atau dimiliki user aktif.
- Cache menu di-clear otomatis setiap tabel `menus` berubah.

## Data

- Data yang punya relasi ke transaksi memakai `SoftDeletes`, bukan hapus permanen.
- Status aktif/nonaktif memakai kolom `is_active`, bukan menghapus baris.
- Setiap tabel utama diberi seeder agar `php artisan migrate:fresh --seed` selalu menghasilkan
  sistem yang siap dipakai.

## UI

Komponen Blade yang sudah tersedia di `resources/views/components`:

`<x-page-header>` `<x-card>` `<x-button>` `<x-input>` `<x-select>` `<x-badge>`
`<x-alert>` `<x-modal>` `<x-confirm-delete>` `<x-empty-state>` `<x-icon>`

- Pesan sukses/gagal dikirim lewat `session()->flash('success'|'error'|'warning'|'info', ...)`
  dan otomatis dirender oleh `<x-alert>` di layout.
- Konfirmasi hapus memakai `<x-confirm-delete :action="..." />`, bukan `confirm()` bawaan browser.
- Tabel dengan potensi ribuan baris memakai paginasi server-side, bukan render semua baris.

## Alur kerja

```bash
php artisan migrate:fresh --seed   # bangun ulang database contoh
php artisan serve                  # atau akses http://siyktn.test bila pakai Laragon
npm run dev                        # Vite (wajib jalan saat mengembangkan tampilan)
```

Akun bawaan hasil seeder ada di `database/seeders/UserSeeder.php`.
