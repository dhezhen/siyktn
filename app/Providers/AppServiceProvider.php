<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Supaya layout bisa dipakai sebagai <x-layouts::app> di Blade biasa,
        // sekaligus tetap menjadi layout bawaan komponen Livewire.
        Blade::anonymousComponentNamespace('layouts', 'layouts');

        // Super admin lolos semua pengecekan permission tanpa perlu didaftarkan
        // satu per satu. Dikembalikan null (bukan false) agar role lain tetap
        // diperiksa seperti biasa.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(config('permission.super_admin_role'))
                ? true
                : null;
        });
    }
}
