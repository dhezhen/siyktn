<x-mail::message>
# Assalamu'alaikum, {{ $name }}

Akun Anda untuk masuk ke sistem {{ config('app.name') }} telah berhasil dibuat. Berikut adalah rincian login Anda:

- **Username:** {{ $username }}
- **Kata Sandi Sementara:** {{ $password }}

<x-mail::panel>
Harap segera masuk ke sistem dan mengganti kata sandi sementara Anda demi keamanan akun.
</x-mail::panel>

<x-mail::button :url="route('login')">
Masuk ke Sistem
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
