<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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
     | Pengaturan sistem
     */
    Route::get('menu', [MenuController::class, 'index'])->name('menu.index');

    Route::get('pengaturan', [SettingController::class, 'edit'])->name('setting.edit');
    Route::put('pengaturan', [SettingController::class, 'update'])->name('setting.update');

    Route::get('log-aktivitas', [ActivityController::class, 'index'])->name('activity.index');
});
