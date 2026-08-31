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

### Pengelompokan

Setiap menu adalah **anak dari headernya** (`parent_id`), bukan sekadar menu yang
kebetulan berada di bawahnya. Di sidebar, header yang punya anak dirender sebagai
**kelompok yang bisa dibuka-tutup** — lengkap dengan ikon dan tanda panah — dan
terbuka sendiri bila halaman yang sedang dibuka ada di dalamnya.

- Karena header kini berupa tombol, ia **wajib punya ikon**; tanpa itu jatuh ke
  ikon `dot` dan terlihat janggal.
- Header ikut hilang begitu **seluruh isinya** tidak boleh dilihat user, sehingga
  tidak pernah tersisa judul kelompok kosong.
- Menu baru dari seeder selalu ditaruh di **akhir kelompoknya**. Jangan kembali
  memakai penghitung urutan global: pada database yang sudah terisi, nomornya
  bertabrakan dengan data lama dan menu baru mendarat di kelompok tetangga.
- Susunan datar gaya lama (header dan isinya sama-sama di level atas) masih
  didukung `App\Support\Menu`, supaya menu yang pernah diatur manual tidak rusak.
  Header tanpa anak tetap dirender sebagai label kecil, bukan dropdown.

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
`download` ekspor, `upload` impor, `check` setujui, `x-mark` tolak,
`switch` pindahkan, `logout` keluarkan dari kelompok,
`academic` muhaffizh, `book` halaqah.

### Grafik

Komponen `<x-grafik.garis>`, `<x-grafik.batang>`, dan `<x-grafik.tumpuk>` —
SVG yang dirender server, tanpa pustaka grafik tambahan. Parameternya terkumpul
di `App\Support\Grafik`.

- **Warnanya sudah diuji, jangan diganti tanpa menguji ulang.** Hex-nya bukan
  pilihan selera: tiap set diperiksa jarak warnanya terhadap kartu putih untuk
  penglihatan normal maupun buta warna. Hasil dan kegagalannya dicatat di
  docblock `Grafik`. Contoh nyata: emerald + sky terasa wajar karena badge
  aplikasi memakai keduanya, tetapi sebagai dua garis ia **gagal** (ΔE 14,0,
  di bawah ambang 15) dan sudah diganti emerald + oranye.
- **Satu sumbu, satu satuan.** Jangan memasang dua skala Y di satu grafik —
  perbandingannya jadi karangan. Dua ukuran berbeda berarti dua grafik.
- Batang memakai **satu warna**; panjangnya yang mengukur, bukan warnanya.
  Meragamkan warna per batang membuang satu-satunya kanal yang tersisa.
- **Setiap angka harus terjangkau tanpa hover**: tiap grafik menyertakan
  "Lihat sebagai tabel", dan sebaran kualitas memakai legenda berangka karena
  dua warnanya di bawah kontras 3:1.
- Hindari bentuk sebaris `@php(...)` di dalam komponen SVG. Dengan pemanggilan
  fungsi bersarang, Blade mengompilasinya menjadi blok PHP tanpa penutup dan
  menelan sisa berkasnya. Hitung geometri di satu blok `@php … @endphp` di atas.

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

## Dashboard

Isinya **mengikuti peran**, bukan sekadar menyembunyikan kartu yang tidak boleh
dilihat.

| Peran | Yang tampil |
|---|---|
| super-admin / admin | angka sistem (pengguna, role, menu, peserta) + grafik seluruh halaqah + log aktivitas |
| operator | angka operasional (peserta, muhaffizh, halaqah) + grafik seluruh halaqah |
| muhaffizh | **hanya bimbingannya**: Halaqah Saya, Santri Binaan, Setoran Pekan Ini, Ziyadah Terkumpul + grafik halaqah asuhannya |
| pengguna | kartu sambutan beserta pintasan seadanya |

- Kartu yang kosong **karena izin** tidak ditampilkan sama sekali. "Aktivitas
  Terakhir" yang kosong terbaca sebagai "belum ada aktivitas", padahal
  sebenarnya "Anda tidak boleh melihatnya".
- Muhaffizh tidak diberi angka pesantren. "Total Peserta 34" tidak menjawab
  pertanyaan apa pun yang ia punya pagi itu.
- Setelah mengubah `config/rbac.php`, **jalankan ulang** `PermissionSeeder` lalu
  `RoleSeeder`. Role yang menyimpang dari katalog pernah membuat muhaffizh
  memegang `setoran.view-all` dan melihat data seluruh pesantren.

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
| `muhaffizh` | muhaffizh | pembimbing hafalan (dibuat `DemoDataSeeder`) |

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
| 10 | Modul Muhaffizh & Halaqah beserta penempatan santri | ✅ |
| 11 | Setoran hafalan (satuan halaman) + pembatasan data muhaffizh | ✅ |
| 12 | Grafik hafalan & dashboard yang menyesuaikan peran | ✅ |

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

## Model data halaqah

| Tabel | Artinya | Aturan |
|---|---|---|
| `muhaffizh` | **pembimbing hafalan** | `user_id` nullable & unik — boleh didata sebelum punya akun |
| `halaqah` | **kelompok binaan** dalam satu angkatan | `unique(angkatan_id, kode)`, diampu seorang muhaffizh |
| `anggota_halaqah` | **keanggotaan** seorang santri di sebuah halaqah | menunjuk `pendaftaran_id`, bukan `peserta_id` |

Tiga hal yang gampang salah:

1. **Keanggotaan melekat pada pendaftaran, bukan pada orangnya.** Satu orang bisa
   ikut karantina berkali-kali; kalau keanggotaan melekat pada `peserta`, halaqah
   angkatan lalu dan angkatan sekarang tercampur dan seluruh rekap hafalan ikut salah.
2. **Santri yang pindah tidak dihapus barisnya**, melainkan ditutup dengan
   `tanggal_keluar` lewat `AnggotaHalaqah::tutup()` — supaya riwayat setorannya tetap
   bisa ditelusuri ke muhaffizh yang membimbingnya saat itu.
3. **"Satu santri hanya aktif di satu halaqah"** ditegakkan database, bukan hanya
   controller. MySQL tidak punya *partial unique index*, jadi dipakai kolom bayangan
   `kunci_aktif`: berisi `pendaftaran_id` selama aktif, `NULL` setelah ditutup — dan
   nilainya diisi otomatis di `AnggotaHalaqah::booted()`, tidak pernah diisi tangan.
   Karena kolom itu unik se-tabel, urutan saat memindahkan santri **wajib** tutup
   dulu baru buka yang baru.

Syarat seorang santri boleh ditempatkan di sebuah halaqah — semuanya diperiksa
ulang di server lewat `HalaqahController::calonSantri()`, karena daftar di layar
bisa saja sudah basi:

| Syarat | Alasan |
|---|---|
| pendaftaran `disetujui` + `aktif` | yang belum diverifikasi belum jadi santri |
| angkatannya sama dengan angkatan halaqah | halaqah selalu milik satu angkatan |
| jenis kelamin cocok | halaqah ikhwan dan akhwat dipisah |
| belum punya keanggotaan aktif | dijaga juga oleh `kunci_aktif` |
| halaqahnya `is_aktif` dan kuotanya cukup | `kuota = 0` berarti tidak dibatasi |

Penempatan yang melebihi kuota **ditolak seluruhnya**, bukan sebagian, supaya
petugas tahu persis siapa yang masuk dan siapa yang belum.

## Akun login muhaffizh

Muhaffizh tidak wajib punya akun — ia tetap bisa didata dan mengampu halaqah.
Akun hanya diperlukan bila ia ingin masuk sendiri ke sistem.

- **Role mengikuti tautan akun, bukan diingat petugas.** Mengisi `muhaffizh.user_id`
  otomatis memberi role `muhaffizh`, dan melepasnya otomatis mencabut role itu —
  diatur di `Muhaffizh::booted()` supaya berlaku untuk semua jalur: form, seeder,
  maupun tinker. Role lain milik akun itu tidak ikut tersentuh, dan akun
  **super admin sengaja dilewati**.
- Pakai event `created` dan `updated` terpisah, bukan `saved`: pada baris yang baru
  dibuat `wasChanged()` tidak menandai apa pun, sehingga muhaffizh yang langsung
  dibuat lengkap dengan `user_id` akan terlewat.
- Tombol **Buatkan Akun** di halaman detail membuat pengguna + role + kata sandi
  sementara sekaligus. Kata sandinya ditampilkan **sekali** di pesan sukses, dan
  `must_change_password` memaksa penggantian saat login pertama.
- Email muhaffizh wajib diisi lebih dulu, karena dipakai untuk masuk dan
  memulihkan kata sandi.
- Dropdown **Akun Pengguna** hanya menawarkan akun aktif yang belum memikul peran
  lain, supaya akun admin atau super admin tidak bisa tertaut karena salah klik.

## Setoran hafalan

Satuannya **halaman**, sesuai kebiasaan pencatatan di YKTN. `jumlah_halaman`
(kelipatan 0,5) adalah satu-satunya angka yang dijumlahkan saat rekap; juz,
surah, dan ayat hanya konteks. Jangan menambahkan kolom rentang halaman di
samping jumlahnya — dua angka yang bisa saling bertentangan merusak rekap.

### Penyimak bukan pencatat

| Kolom | Artinya |
|---|---|
| `muhaffizh_id` | siapa yang **menyimak** setoran |
| `dicatat_oleh` | siapa yang **mengetik** ke sistem |

Pemisahan ini yang membuat muhaffizh **tidak wajib berakun**: setorannya dicatat
di kartu, lalu dientri operator — `muhaffizh_id` tetap menunjuk orang yang benar
sehingga rekap per muhaffizh akurat. Karena itu role `operator` sengaja diberi
`setoran.create`; tanpa itu, halaqah yang pengampunya tidak berakun tidak akan
pernah punya data.

`muhaffizh_id` **disimpan eksplisit**, bukan diturunkan dari
`halaqah.muhaffizh_id`, dan tidak ikut diperbarui saat setoran diedit. Kalau
pengampu sebuah halaqah diganti di tengah program, seluruh setoran lama akan
diam-diam berpindah atas nama pengampu baru — dan itu baru ketahuan saat rekap
akhir.

### Pembatasan data

Muhaffizh hanya melihat halaqah asuhannya. Batasnya ditentukan permission
**`{modul}.view-all`**, bukan nama role — lihat trait
`App\Http\Controllers\Concerns\MembatasiKeMuhaffizh`.

- `admin` dan `operator` memiliki `halaqah.view-all` dan `setoran.view-all`,
  jadi melihat seluruh data. Operator memang perlu, karena ia yang mengentri
  kartu milik muhaffizh yang tidak berakun.
- `muhaffizh` tidak memilikinya, sehingga datanya dipersempit ke dirinya sendiri
  dan halaman daftarnya berjudul **Halaqah Saya**.
- Id yang dikirim formulir **selalu diperiksa ulang** di server
  (`anggotaBolehDisentuh`), bukan sekadar disaring saat menampilkan pilihan.
- Pembatasan berlaku di **seluruh** permukaan, termasuk yang mudah terlupa:
  daftar, dropdown filter, **ekspor CSV**, dan **kartu dashboard**. Dashboard
  muhaffizh menampilkan angkanya sendiri (Halaqah Saya, Santri Binaan, Setoran
  Pekan Ini, Ziyadah Terkumpul), bukan angka seluruh pesantren.

Yang **belum** dibatasi: daftar Peserta dan daftar Muhaffizh masih tampil utuh
bagi muhaffizh, mengikuti permission `peserta.view` dan `muhaffizh.view` yang
memang dimilikinya. Ia perlu itu untuk menelusuri data santri dari halaman
halaqah.

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
