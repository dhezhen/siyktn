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

`<x-page-header>` `<x-card>` `<x-button>` `<x-icon-button>` `<x-input>` `<x-select>`
`<x-badge>` `<x-alert>` `<x-toast>` `<x-modal>` `<x-confirm-delete>` `<x-empty-state>` `<x-icon>`

### Aksi di dalam tabel

Semua aksi baris memakai `<x-icon-button>`, bukan tautan teks:

```blade
<x-icon-button icon="eye"    label="Lihat detail" :href="route('peserta.show', $item)" />
<x-icon-button icon="pencil" label="Ubah data"    :href="route('peserta.edit', $item)" />
<x-confirm-delete :action="route('peserta.destroy', $item)" icon-only label="Hapus peserta" />
```

`label` wajib diisi: dipakai sebagai tooltip sekaligus `aria-label` untuk pembaca layar.
Tooltipnya di-*teleport* ke `<body>` dan diposisikan dengan `x-anchor`, sehingga tidak
terpotong oleh tabel yang memakai `overflow-x-auto`.

Ikon baku: `eye` lihat, `pencil` ubah, `trash` hapus, `plus` tambah, `key` hak akses,
`restore` pulihkan, `eye-slash` sembunyikan/nonaktifkan, `identification` berkas KTP,
`download` ekspor, `upload` impor, `check` setujui, `x-mark` tolak.

### Notifikasi

- Pesan setelah pindah halaman: `session()->flash(...)` &rarr; dirender `<x-alert>`
  (yang bertipe `success` menutup sendiri setelah 6 detik).
- Pesan dari aksi Livewire tanpa pindah halaman:
  `$this->dispatch('notify', type: 'success', message: '…')` &rarr; ditangkap
  `<x-toast>` yang sudah dipasang sekali di layout. Jangan menulis ulang markup
  toast di dalam komponen.

### Umpan balik saat menunggu

- Tombol yang memicu method Livewire: `<x-button busy-target="save">` — ikon berganti
  spinner dan tombol dinonaktifkan selama proses.
- Pembungkus tabel diberi `class="memuat-halus" wire:loading.class="opacity-55"`
  supaya isinya meredup saat filter/paginasi diproses, bukan berkedip kosong.

- Layout halaman: `<x-layouts::app>` (dalam sistem) dan `<x-layouts::guest>` (login dsb).
- Pesan sukses/gagal lewat `session()->flash('success'|'error'|'warning'|'info', ...)`,
  otomatis dirender `<x-alert>` di layout.
- Konfirmasi hapus memakai `<x-confirm-delete :action="..." />`, bukan `confirm()` browser.
- Tabel besar memakai komponen Livewire dengan paginasi server-side, bukan render semua baris.

## Bahasa

Pesan validasi, paginasi, dan autentikasi diterjemahkan di `lang/id/` dan `lang/id.json`.
Bila menambah aturan validasi baru yang belum ada terjemahannya, tambahkan ke
`lang/id/validation.php` — jangan biarkan pesan bawaan berbahasa Inggris muncul
berdampingan dengan label berbahasa Indonesia.

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
| 7 | Pendaftaran mandiri peserta + verifikasi + email | ✅ |
| 8 | Pemisahan peserta (orang) dan pendaftaran (per angkatan) | ✅ |
| 9 | Perbaikan UI/UX: tombol aksi berikon, transisi, bahasa Indonesia | ✅ |

## Model data peserta

Dua hal yang mudah tertukar, sengaja dipisah:

| Tabel | Artinya | Aturan |
|---|---|---|
| `peserta` | **orang**, satu baris seumur hidup | NIK unik, KTP & foto melekat di sini |
| `pendaftaran` | **keikutsertaan** pada satu angkatan | `unique(peserta_id, angkatan_id)` |

Satu orang boleh punya banyak pendaftaran. Alumni yang ikut angkatan berikutnya
tidak menghasilkan orang kembar — barisnya dipakai ulang dan datanya diperbarui.

### Kapan seseorang boleh mendaftar lagi

Aturan tunggalnya ada di `App\Support\KelayakanPendaftaran`, dipakai bersama oleh
formulir publik dan input petugas.

| Kondisi NIK di sistem | Boleh? | Alasan |
|---|---|---|
| Belum pernah ada | ✅ | pendaftar baru |
| Punya pendaftaran `menunggu` | ❌ | berkas sedang diproses |
| Punya pendaftaran `disetujui` + `aktif` | ❌ | masih aktif di angkatan berjalan |
| Pernah `lulus` | ✅ | alumni, boleh ambil program berikutnya |
| Pernah `keluar` | ✅ | ditandai di antrean supaya petugas tahu |
| Pernah `ditolak` | ✅ | memang diminta mendaftar ulang |
| Sudah terdaftar di angkatan yang sama | ❌ | duplikat sungguhan |
| `boleh_mendaftar_lagi = false` | ❌ | dicekal, alasannya tercatat internal |

### Pengaman privasi formulir publik

Formulir tidak pernah memberi tahu siapa pemilik sebuah NIK. Untuk memakai ulang
data lama, pendaftar harus mengisi **NIK dan tanggal lahir yang keduanya cocok**.
Bila NIK cocok tapi tanggal lahir tidak, pendaftaran ditolak dengan pesan netral.
Jangan menambahkan endpoint pencarian NIK di halaman publik — itu membuat siapa
pun bisa memancing nama orang lain.

## Pendaftaran peserta

Alur lengkapnya:

1. Calon peserta membuka **`/pendaftaran`** (publik, tanpa login), mengisi data
   dan melampirkan KTP.
2. Bila NIK dikenal (dan tanggal lahirnya cocok), data orangnya dipakai ulang dan
   KTP tidak perlu diunggah lagi. Sistem membuat kode tanda terima
   `REG-{tahun}-{urut}` dan mengirim email:
   satu ke pendaftar, satu ke tiap petugas pemilik permission `peserta.approve`
   (plus seluruh super admin), dan salinan ke *Email Resmi* di Pengaturan Aplikasi
   bila kolomnya diisi.
3. Petugas meninjau di **Pendaftaran** (`/pendaftaran-masuk`), membuka berkas KTP,
   lalu **Setujui** atau **Tolak**.
4. Disetujui &rarr; nomor induk dibuat otomatis, status jadi aktif, pendaftar
   dikabari lewat email.
   Ditolak &rarr; alasan penolakan wajib diisi dan ikut dikirim ke pendaftar.

Peserta yang diinput petugas lewat **Peserta &rarr; Tambah** melewati alur yang
sama (`PendaftaranService::daftarkan`), hanya saja langsung berstatus disetujui —
sehingga emailnya tetap terkirim dan aturan kelayakan tetap berlaku.

Pendaftaran ulang ditandai di antrean peninjauan (`pendaftaran ke-N`) beserta
tautan ke riwayat orangnya, supaya petugas tidak memeriksa ulang berkas yang
sudah pernah diverifikasi.

### Berkas KTP

KTP disimpan di disk **`local`** (`storage/app/private/ktp`), **bukan** `public`.
Berkas hanya bisa dibuka lewat route `pendaftaran.ktp` yang dijaga permission
`peserta.view`. Jangan pernah memindahkannya ke `storage/app/public` — folder itu
ter-symlink ke `public/` dan isinya bisa diakses siapa saja yang menebak URL-nya.

### Email

Notifikasi memakai `ShouldQueue`, jadi pengiriman tidak menahan proses simpan.

| Lingkungan | `QUEUE_CONNECTION` | Yang perlu dijalankan |
|---|---|---|
| Lokal | `sync` | tidak ada — email langsung terkirim |
| Server | `database` | `php artisan queue:work` (pakai Supervisor) |

`MAIL_MAILER=log` menulis email ke `storage/logs/laravel.log`, berguna untuk
menguji tanpa SMTP. Ganti ke `smtp` beserta kredensialnya sebelum dipakai sungguhan.

Kegagalan kirim email **tidak** menggagalkan pendaftaran — datanya tetap
tersimpan dan errornya dicatat di log.

### Belum dikerjakan (kandidat sprint berikutnya)

- Impor CSV untuk peserta (ekspor sudah ada).
- Halaman cek status pendaftaran mandiri memakai kode `REG-…`.
- Nomor induk seumur hidup per orang (saat ini nomor induk melekat per angkatan).
- Cetak PDF (kartu peserta, rekap angkatan) — perlu `barryvdh/laravel-dompdf`.
- 2FA dan riwayat login per perangkat.
- Backup database terjadwal (`spatie/laravel-backup`).
- Modul lanjutan domain tahfidz: muhaffizh, hafalan, pembayaran, syahadah.
- Automated test (belum ada satu pun test ditulis).
