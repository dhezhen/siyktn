<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AngkatanController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HalaqahController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MuhaffizhController;
use App\Http\Controllers\PendaftaranAdminController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SetoranController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publik — pendaftaran mandiri peserta
|--------------------------------------------------------------------------
| Terbuka tanpa login. Pengiriman formulir dibatasi agar tidak bisa dibanjiri
| kiriman berulang dari satu sumber.
*/
Route::get('/pendaftaran', [PendaftaranController::class, 'create'])->name('pendaftaran.create');
Route::post('/pendaftaran', [PendaftaranController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('pendaftaran.store');
Route::get('/pendaftaran/terkirim', [PendaftaranController::class, 'sukses'])->name('pendaftaran.sukses');

/*
|--------------------------------------------------------------------------
| Tamu (belum login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/lupa-kata-sandi', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/lupa-kata-sandi', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-kata-sandi/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-kata-sandi', [NewPasswordController::class, 'store'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Sudah login
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Boleh diakses meski user masih wajib ganti kata sandi.
    Route::get('/ganti-kata-sandi', [PasswordController::class, 'edit'])->name('password.change');
    Route::put('/ganti-kata-sandi', [PasswordController::class, 'update'])->name('password.change.update');
});

/*
|--------------------------------------------------------------------------
| Sudah login DAN kata sandi sementara sudah diganti
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {

    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profil/foto', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.destroy');

    /*
     | Manajemen pengguna
     | Rute khusus didaftarkan sebelum resource agar tidak tertangkap {user}.
     */
    Route::get('user/ekspor', [UserController::class, 'export'])->name('user.export');
    Route::get('user/impor', [UserController::class, 'importForm'])->name('user.import.form');
    Route::post('user/impor', [UserController::class, 'import'])->name('user.import');
    Route::put('user/{user}/reset-password', [UserController::class, 'resetPassword'])->name('user.reset-password');
    Route::post('user/{id}/pulihkan', [UserController::class, 'restore'])->name('user.restore');
    Route::resource('user', UserController::class)->except('show');

    Route::resource('role', RoleController::class)->except('show');

    /*
     | Data tahfidz
     */
    Route::resource('angkatan', AngkatanController::class)->parameters(['angkatan' => 'angkatan']);
    Route::resource('program', ProgramController::class)->except(['show']);

    Route::get('peserta/ekspor', [PesertaController::class, 'export'])->name('peserta.export');
    Route::resource('peserta', PesertaController::class)->parameters(['peserta' => 'peserta']);

    Route::get('muhaffizh/ekspor', [MuhaffizhController::class, 'export'])->name('muhaffizh.export');
    Route::post('muhaffizh/{muhaffizh}/akun', [MuhaffizhController::class, 'buatkanAkun'])->name('muhaffizh.akun');
    Route::resource('muhaffizh', MuhaffizhController::class)->parameters(['muhaffizh' => 'muhaffizh']);

    /*
     | Halaqah beserta keanggotaan santrinya.
     | Rute anggota didaftarkan sebelum resource agar "halaqah/anggota/…"
     | tidak tertangkap sebagai {halaqah}.
     */
    Route::post('halaqah/{halaqah}/anggota', [HalaqahController::class, 'tempatkan'])->name('halaqah.anggota.store');
    Route::put('halaqah/anggota/{anggota}/pindah', [HalaqahController::class, 'pindahkan'])->name('halaqah.anggota.pindah');
    Route::delete('halaqah/anggota/{anggota}', [HalaqahController::class, 'keluarkan'])->name('halaqah.anggota.keluar');
    Route::get('halaqah/{halaqah}/laporan', [HalaqahController::class, 'laporan'])->name('halaqah.laporan');
    Route::get('halaqah/{halaqah}/ekspor-laporan', [HalaqahController::class, 'eksporLaporan'])->name('halaqah.eksporLaporan');
    Route::get('halaqah/anggota/{anggota}/syahadah', [HalaqahController::class, 'cetakSyahadah'])->name('halaqah.syahadah.cetak');
    Route::get('halaqah/{halaqah}/laporan/ekspor', [HalaqahController::class, 'eksporLaporan'])->name('halaqah.laporan.export');
    Route::resource('halaqah', HalaqahController::class)->parameters(['halaqah' => 'halaqah']);

    /*
     | Setoran hafalan. Satuannya halaman.
     */
    Route::get('setoran/ekspor', [SetoranController::class, 'export'])->name('setoran.export');
    Route::resource('setoran', SetoranController::class)
        ->parameters(['setoran' => 'setoran'])
        ->except('show');

    /*
     | Peninjauan pendaftaran.
     | Nama parameter harus sama dengan nama variabel di controller, jika tidak
     | route model binding gagal tanpa pesan apa pun.
     */
    Route::get('pendaftaran-masuk/presensi', [PendaftaranAdminController::class, 'presensi'])->name('pendaftaran.presensi');
    Route::post('pendaftaran-masuk/konfirmasi-kehadiran/{pendaftaran?}', [PendaftaranAdminController::class, 'konfirmasiKehadiran'])->name('pendaftaran.konfirmasi-kehadiran');
    Route::get('pendaftaran-masuk', [PendaftaranAdminController::class, 'index'])->name('pendaftaran.index');
    Route::post('pendaftaran-masuk/{pendaftaran}/setujui', [PendaftaranAdminController::class, 'setujui'])->name('pendaftaran.setujui');
    Route::post('pendaftaran-masuk/{pendaftaran}/tolak', [PendaftaranAdminController::class, 'tolak'])->name('pendaftaran.tolak');
    Route::get('peserta/{peserta}/ktp', [PendaftaranAdminController::class, 'ktp'])->name('pendaftaran.ktp');

    /*
     | Keuangan
     */
    Route::get('keuangan', [KeuanganController::class, 'index'])->name('keuangan.index');
    Route::put('keuangan/{keuangan}', [KeuanganController::class, 'update'])->name('keuangan.update');

    /*
     | Pengaturan sistem
     */
    Route::get('menu', [MenuController::class, 'index'])->name('menu.index');

    Route::get('pengaturan', [SettingController::class, 'edit'])->name('setting.edit');
    Route::put('pengaturan', [SettingController::class, 'update'])->name('setting.update');

    Route::get('log-aktivitas', [ActivityController::class, 'index'])->name('activity.index');
});
