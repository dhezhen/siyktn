<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Berapa kali percobaan gagal sebelum dikunci sementara.
     */
    protected const MAX_ATTEMPTS = 5;

    protected const DECAY_SECONDS = 60;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'login' => 'email atau username',
            'password' => 'kata sandi',
        ];
    }

    /**
     * Coba autentikasi. Kolom `login` boleh diisi email maupun username.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identity = (string) $this->input('login');
        $field = Str::contains($identity, '@') ? 'email' : 'username';

        $credentials = [
            $field => $identity,
            'password' => $this->input('password'),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'login' => 'Email/username atau kata sandi tidak cocok.',
            ]);
        }

        // Akun nonaktif tetap ada datanya, tapi tidak boleh masuk.
        if (! Auth::user()->is_active) {
            Auth::logout();
            $this->session()->invalidate();

            throw ValidationException::withMessages([
                'login' => 'Akun Anda dinonaktifkan. Hubungi administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * Kunci throttle digabung dengan IP agar satu penyerang tidak bisa
     * mengunci akun orang lain dari jaringan berbeda.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('login')).'|'.$this->ip());
    }
}
